<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_foc\Service\StripeConnectService;
use Psr\Log\LoggerInterface;

/**
 * GAP-C04: Sincroniza registros de uso pendientes con Stripe Metered Billing.
 *
 * Lee BillingUsageRecord entities no sincronizadas, agrupa por metrica,
 * y envia Usage Records a Stripe via la API de Subscription Items.
 *
 * Stripe Metered Billing flow:
 * 1. SaaS crea Subscription con Price tipo 'metered'.
 * 2. Periodicamente, el SaaS envia Usage Records con cantidad consumida.
 * 3. Al final del periodo, Stripe calcula el total y genera la factura.
 *
 * STRIPE-ENV-UNIFY-001: Credenciales via StripeConnectService (getenv).
 */
class UsageStripeSyncService {

  /**
   * Maximum records to process per cron run (batch).
   */
  protected const BATCH_SIZE = 100;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected ?StripeConnectService $stripeConnect = NULL,
  ) {}

  /**
   * Sincroniza registros de uso pendientes con Stripe Metered Billing.
   *
   * @return int
   *   Numero de registros sincronizados.
   */
  public function syncUsageToStripe(): int {
    if (!$this->stripeConnect) {
      $this->logger->debug('UsageStripeSyncService: StripeConnectService not available, skipping.');
      return 0;
    }

    // 1. Obtener registros no sincronizados.
    $storage = $this->entityTypeManager->getStorage('billing_usage_record');
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('synced_to_stripe', FALSE)
      ->sort('created', 'ASC')
      ->range(0, self::BATCH_SIZE);

    $ids = $query->execute();
    if (empty($ids)) {
      return 0;
    }

    $records = $storage->loadMultiple($ids);

    // 2. Agrupar por tenant + metrica.
    $grouped = [];
    foreach ($records as $record) {
      $tenantId = (int) $record->get('tenant_id')->target_id;
      $metric = $record->get('metric')->value;
      $key = "{$tenantId}_{$metric}";

      if (!isset($grouped[$key])) {
        $grouped[$key] = [
          'tenant_id' => $tenantId,
          'metric' => $metric,
          'quantity' => 0,
          'records' => [],
        ];
      }
      $grouped[$key]['quantity'] += (int) $record->get('quantity')->value;
      $grouped[$key]['records'][] = $record;
    }

    $syncedCount = 0;

    // 3. Para cada grupo, enviar a Stripe.
    foreach ($grouped as $group) {
      $tenantId = $group['tenant_id'];
      $metric = $group['metric'];
      $quantity = $group['quantity'];

      try {
        // Resolver Stripe Subscription Item ID para esta metrica.
        $itemId = $this->resolveStripeSubscriptionItemId($tenantId, $metric);
        if (!$itemId) {
          $this->logger->warning(
            'No Stripe subscription item for tenant @tid metric @metric — skipping.',
            ['@tid' => $tenantId, '@metric' => $metric]
          );
          continue;
        }

        // Llamada real a Stripe API via StripeConnectService.
        $this->stripeConnect->stripeRequest(
          'POST',
          '/subscription_items/' . $itemId . '/usage_records',
          [
            'quantity' => $quantity,
            'timestamp' => time(),
            'action' => 'increment',
          ],
          "usage-{$tenantId}-{$metric}-" . bin2hex(random_bytes(4))
        );

        // Marcar registros como sincronizados.
        $now = time();
        foreach ($group['records'] as $record) {
          if ($record->hasField('synced_to_stripe')) {
            $record->set('synced_to_stripe', TRUE);
          }
          if ($record->hasField('stripe_synced_at')) {
            $record->set('stripe_synced_at', $now);
          }
          $record->save();
        }

        $syncedCount += count($group['records']);

        $this->logger->info(
          'Stripe usage synced: tenant @tid, metric @metric, qty @qty.',
          ['@tid' => $tenantId, '@metric' => $metric, '@qty' => $quantity]
        );
      }
      catch (\Throwable $e) {
        $this->logger->error(
          'Stripe usage sync failed for tenant @tid metric @metric: @msg',
          ['@tid' => $tenantId, '@metric' => $metric, '@msg' => $e->getMessage()]
        );
        // No marcar como sincronizado — se reintentara en el proximo cron.
      }
    }

    return $syncedCount;
  }

  /**
   * Resuelve el Stripe Subscription Item ID para una metrica de uso.
   *
   * Busca en el Tenant entity el campo correspondiente a la metrica,
   * o busca en AddonSubscription si la metrica es un addon.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param string $metric
   *   Nombre de la metrica (api_calls, storage, ai_tokens, emails).
   *
   * @return string|null
   *   Stripe Subscription Item ID, o NULL si no se encontro.
   */
  protected function resolveStripeSubscriptionItemId(int $tenantId, string $metric): ?string {
    try {
      $tenant = $this->entityTypeManager->getStorage('tenant')->load($tenantId);
      if (!$tenant) {
        return NULL;
      }

      // Check if tenant has a usage subscription item field for this metric.
      $fieldName = 'stripe_usage_item_' . $metric;
      if ($tenant->hasField($fieldName)) {
        $value = $tenant->get($fieldName)->value;
        if ($value) {
          return $value;
        }
      }

      // Fallback: check stripe_subscription_id and resolve via Stripe API.
      $subId = $tenant->hasField('stripe_subscription_id')
        ? $tenant->get('stripe_subscription_id')->value
        : NULL;

      if ($subId && $this->stripeConnect) {
        $subscription = $this->stripeConnect->stripeRequest('GET', '/subscriptions/' . $subId);
        foreach ($subscription['items']['data'] ?? [] as $item) {
          $priceMetadata = $item['price']['metadata'] ?? [];
          if (($priceMetadata['usage_metric'] ?? '') === $metric) {
            return $item['id'];
          }
          // Also check if price is metered and matches by lookup_key.
          if (($item['price']['lookup_key'] ?? '') === "usage_{$metric}") {
            return $item['id'];
          }
        }
      }

      return NULL;
    }
    catch (\Throwable $e) {
      $this->logger->warning(
        'Error resolving Stripe subscription item for tenant @tid metric @metric: @msg',
        ['@tid' => $tenantId, '@metric' => $metric, '@msg' => $e->getMessage()]
      );
      return NULL;
    }
  }

}
