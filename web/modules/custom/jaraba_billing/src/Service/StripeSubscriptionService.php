<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\jaraba_foc\Service\StripeConnectService;
use Psr\Log\LoggerInterface;

/**
 * Gestiona suscripciones Stripe reales.
 *
 * Orquesta TenantSubscriptionService (lógica local) + API Stripe (lógica remota).
 *
 * AUDIT-PERF-002: Usa LockBackendInterface para prevenir race conditions
 * en operaciones financieras concurrentes contra la API de Stripe.
 */
class StripeSubscriptionService {

  public function __construct(
    protected StripeConnectService $stripeConnect,
    protected TenantSubscriptionService $tenantSubscription,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected LockBackendInterface $lock,
  ) {}

  /**
   * Crea una suscripción en Stripe.
   *
   * @param string $customerId
   *   ID del customer de Stripe.
   * @param string $priceId
   *   ID del precio de Stripe.
   * @param array $options
   *   Opciones adicionales (trial_period_days, metadata, etc.).
   *
   * @return array
   *   Datos de la suscripción de Stripe.
   */
  public function createSubscription(string $customerId, string $priceId, array $options = []): array {
    // AUDIT-PERF-002: Lock por customer para prevenir creación de
    // suscripciones duplicadas en Stripe por peticiones concurrentes.
    $lockId = 'jaraba_billing:subscription_create:' . $customerId;
    if (!$this->lock->acquire($lockId, 30)) {
      throw new \RuntimeException('Subscription creation already in progress for customer ' . $customerId);
    }

    try {
      $params = [
        'customer' => $customerId,
        'items' => [
          ['price' => $priceId],
        ],
      ];

      if (!empty($options['trial_period_days'])) {
        $params['trial_period_days'] = $options['trial_period_days'];
      }

      if (!empty($options['default_payment_method'])) {
        $params['default_payment_method'] = $options['default_payment_method'];
      }

      if (!empty($options['metadata'])) {
        $params['metadata'] = $options['metadata'];
      }

      $subscription = $this->stripeConnect->stripeRequest('POST', '/subscriptions', $params,
        "create-sub-{$customerId}-{$priceId}-" . bin2hex(random_bytes(8)));

      $this->logger->info('Suscripción Stripe creada: @id para customer @cus', [
        '@id' => $subscription['id'],
        '@cus' => $customerId,
      ]);

      return $subscription;
    }
    finally {
      $this->lock->release($lockId);
    }
  }

  /**
   * Cancela una suscripción en Stripe.
   *
   * @param string $subscriptionId
   *   ID de la suscripción de Stripe.
   * @param bool $immediately
   *   TRUE para cancelar inmediatamente, FALSE para al final del periodo.
   *
   * @return array
   *   Suscripción cancelada.
   */
  public function cancelSubscription(string $subscriptionId, bool $immediately = FALSE): array {
    // AUDIT-PERF-002: Lock por suscripción para prevenir cancelaciones
    // concurrentes que podrían dejar estado inconsistente.
    $lockId = 'jaraba_billing:subscription_cancel:' . $subscriptionId;
    if (!$this->lock->acquire($lockId, 30)) {
      throw new \RuntimeException('Subscription cancellation already in progress: ' . $subscriptionId);
    }

    try {
      if ($immediately) {
        $result = $this->stripeConnect->stripeRequest('DELETE', '/subscriptions/' . $subscriptionId);
      }
      else {
        $result = $this->stripeConnect->stripeRequest('POST', '/subscriptions/' . $subscriptionId, [
          'cancel_at_period_end' => 'true',
        ], "cancel-sub-{$subscriptionId}-" . bin2hex(random_bytes(8)));
      }

      $this->logger->info('Suscripción @id cancelada (inmediata: @imm)', [
        '@id' => $subscriptionId,
        '@imm' => $immediately ? 'sí' : 'no',
      ]);

      return $result;
    }
    finally {
      $this->lock->release($lockId);
    }
  }

  /**
   * Actualiza una suscripción en Stripe (cambio de plan).
   *
   * @param string $subscriptionId
   *   ID de la suscripción.
   * @param string $newPriceId
   *   Nuevo ID de precio.
   *
   * @return array
   *   Suscripción actualizada.
   */
  public function updateSubscription(string $subscriptionId, string $newPriceId): array {
    // AUDIT-PERF-002: Lock por suscripción para prevenir que cambios de plan
    // concurrentes lean item IDs obsoletos (read-modify-write race condition).
    $lockId = 'jaraba_billing:subscription_update:' . $subscriptionId;
    if (!$this->lock->acquire($lockId, 30)) {
      throw new \RuntimeException('Subscription update already in progress: ' . $subscriptionId);
    }

    try {
      // Get current subscription to find the item ID.
      $subscription = $this->stripeConnect->stripeRequest('GET', '/subscriptions/' . $subscriptionId);
      $itemId = $subscription['items']['data'][0]['id'] ?? NULL;

      if (!$itemId) {
        throw new \RuntimeException('No se encontró item en la suscripción ' . $subscriptionId);
      }

      $result = $this->stripeConnect->stripeRequest('POST', '/subscriptions/' . $subscriptionId, [
        'items' => [
          [
            'id' => $itemId,
            'price' => $newPriceId,
          ],
        ],
        'proration_behavior' => 'create_prorations',
      ], "update-sub-{$subscriptionId}-{$newPriceId}-" . bin2hex(random_bytes(8)));

      $this->logger->info('Suscripción @id actualizada a precio @price', [
        '@id' => $subscriptionId,
        '@price' => $newPriceId,
      ]);

      return $result;
    }
    finally {
      $this->lock->release($lockId);
    }
  }

  /**
   * Pausa una suscripción.
   *
   * @param string $subscriptionId
   *   ID de la suscripción.
   *
   * @return array
   *   Suscripción pausada.
   */
  public function pauseSubscription(string $subscriptionId): array {
    $result = $this->stripeConnect->stripeRequest('POST', '/subscriptions/' . $subscriptionId, [
      'pause_collection' => [
        'behavior' => 'mark_uncollectible',
      ],
    ], "pause-sub-{$subscriptionId}-" . bin2hex(random_bytes(8)));

    $this->logger->info('Suscripción @id pausada', ['@id' => $subscriptionId]);
    return $result;
  }

  /**
   * Reanuda una suscripción pausada.
   *
   * @param string $subscriptionId
   *   ID de la suscripción.
   *
   * @return array
   *   Suscripción reanudada.
   */
  public function resumeSubscription(string $subscriptionId): array {
    $result = $this->stripeConnect->stripeRequest('POST', '/subscriptions/' . $subscriptionId, [
      'pause_collection' => '',
    ], "resume-sub-{$subscriptionId}-" . bin2hex(random_bytes(8)));

    $this->logger->info('Suscripción @id reanudada', ['@id' => $subscriptionId]);
    return $result;
  }

  /**
   * Sincroniza el estado de una suscripción de Stripe con el tenant local.
   *
   * @param string $subscriptionId
   *   ID de la suscripción de Stripe.
   * @param int $tenantId
   *   ID del tenant local.
   *
   * @return array
   *   Estado sincronizado.
   */
  public function syncSubscriptionStatus(string $subscriptionId, int $tenantId): array {
    $subscription = $this->stripeConnect->stripeRequest('GET', '/subscriptions/' . $subscriptionId);

    $stripeStatus = $subscription['status'] ?? 'unknown';

    $this->logger->info('Sync suscripción @id: estado Stripe = @status, tenant @tenant', [
      '@id' => $subscriptionId,
      '@status' => $stripeStatus,
      '@tenant' => $tenantId,
    ]);

    return [
      'stripe_status' => $stripeStatus,
      'current_period_end' => $subscription['current_period_end'] ?? NULL,
      'cancel_at_period_end' => $subscription['cancel_at_period_end'] ?? FALSE,
      'trial_end' => $subscription['trial_end'] ?? NULL,
    ];
  }

}
