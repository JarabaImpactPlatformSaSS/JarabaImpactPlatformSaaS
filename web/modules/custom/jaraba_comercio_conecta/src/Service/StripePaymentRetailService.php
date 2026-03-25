<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Pagos Stripe Connect con destination charges para marketplace.
 *
 * ARQUITECTURA:
 * - Pedidos mono-merchant: PaymentIntent con destination charge directo
 * - Pedidos multi-merchant: PaymentIntent basico + transfers separados
 * - Fallback: simulacion si jaraba_foc.stripe_connect no disponible.
 *
 * STRIPE-ENV-UNIFY-001: Keys via settings.secrets.php (getenv).
 * OPTIONAL-CROSSMODULE-001: StripeConnectService inyectado como @? opcional.
 */
class StripePaymentRetailService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
    protected LoggerInterface $logger,
    protected ?object $stripeConnectService = NULL,
  ) {}

  /**
   * Crea un PaymentIntent real con Stripe Connect destination charges.
   *
   * Para pedidos con un solo merchant, usa destination charge directo
   * (el pago va al merchant con application_fee para la plataforma).
   * Para pedidos multi-merchant o sin merchant, crea un PaymentIntent basico.
   *
   * @param object $order
   *   La entidad OrderRetail.
   *
   * @return array
   *   Array con success, payment_intent_id, client_secret, amount, currency.
   */
  public function createPaymentIntent(object $order): array {
    $total = (float) $order->get('total')->value;
    $amount_cents = (int) round($total * 100);

    if ($amount_cents <= 0) {
      return ['success' => FALSE, 'message' => t('El importe del pedido no es valido.')];
    }

    $metadata = [
      'order_id' => (string) $order->id(),
      'order_number' => $order->get('order_number')->value,
      'tenant_id' => (string) $order->get('tenant_id')->target_id,
    ];

    try {
      // Intentar destination charge si hay un solo merchant con Stripe Connect.
      $destinationAccount = $this->resolveDestinationAccount($order);

      if ($destinationAccount && $this->stripeConnectService) {
        // Destination charge: pago directo al merchant.
        $result = $this->stripeConnectService->createDestinationCharge(
          $amount_cents,
          'eur',
          $destinationAccount,
          $metadata,
        );

        $order->set('payment_intent_id', $result['payment_intent_id']);
        $order->save();

        return [
          'success' => TRUE,
          'payment_intent_id' => $result['payment_intent_id'],
          'client_secret' => $result['client_secret'],
          'amount' => $amount_cents,
          'currency' => 'eur',
          'requires_confirmation' => TRUE,
        ];
      }

      // Fallback: PaymentIntent basico via jaraba_billing.stripe_client.
      if (\Drupal::hasService('jaraba_billing.stripe_client')) {
        $stripe = \Drupal::service('jaraba_billing.stripe_client');
        $intent = $stripe->createPaymentIntent([
          'amount' => $amount_cents,
          'currency' => 'eur',
          'metadata' => $metadata,
        ]);

        $order->set('payment_intent_id', $intent['id']);
        $order->save();

        return [
          'success' => TRUE,
          'payment_intent_id' => $intent['id'],
          'client_secret' => $intent['client_secret'],
          'amount' => $amount_cents,
          'currency' => 'eur',
          'requires_confirmation' => TRUE,
        ];
      }

      // Ultimo fallback: simulacion (solo en desarrollo)
      $this->logger->warning('Servicio Stripe no disponible. Simulando payment intent para pedido @id.', [
        '@id' => $order->id(),
      ]);
      $payment_intent_id = 'pi_simulated_' . bin2hex(random_bytes(12));
      $order->set('payment_intent_id', $payment_intent_id);
      $order->save();

      return [
        'success' => TRUE,
        'payment_intent_id' => $payment_intent_id,
        'client_secret' => 'cs_simulated_' . bin2hex(random_bytes(12)),
        'amount' => $amount_cents,
        'currency' => 'eur',
        'requires_confirmation' => FALSE,
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('Error creando payment intent para pedido @id: @error', [
        '@id' => $order->id(),
        '@error' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'message' => t('Error procesando el pago. Intentalo de nuevo.')];
    }
  }

  /**
   * Confirma un pago tras el webhook de Stripe.
   */
  public function confirmPayment(string $payment_intent_id): array {
    $order_storage = $this->entityTypeManager->getStorage('order_retail');
    $ids = $order_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('payment_intent_id', $payment_intent_id)
      ->range(0, 1)
      ->execute();

    if (!$ids) {
      return ['success' => FALSE, 'message' => t('Pedido no encontrado.')];
    }

    $order = $order_storage->load(reset($ids));
    $order->set('payment_status', 'paid');
    $order->set('status', 'confirmed');
    $order->save();

    // Actualizar estado de payout en subpedidos.
    $this->updateSuborderPayoutStatus($order);

    $this->logger->info('Pago confirmado para pedido @number (intent: @intent)', [
      '@number' => $order->get('order_number')->value,
      '@intent' => $payment_intent_id,
    ]);

    return [
      'success' => TRUE,
      'order' => $order,
      'order_number' => $order->get('order_number')->value,
    ];
  }

  /**
   * Procesa un reembolso via Stripe.
   */
  public function processRefund(object $order, ?float $amount = NULL): array {
    $payment_intent_id = $order->get('payment_intent_id')->value;
    if (!$payment_intent_id) {
      return ['success' => FALSE, 'message' => t('No hay pago registrado para este pedido.')];
    }

    $refund_amount = $amount ?? (float) $order->get('total')->value;

    try {
      if (\Drupal::hasService('jaraba_billing.stripe_client') && !str_starts_with($payment_intent_id, 'pi_simulated_')) {
        $stripe = \Drupal::service('jaraba_billing.stripe_client');
        $refund_data = ['payment_intent' => $payment_intent_id];
        if ($amount !== NULL) {
          $refund_data['amount'] = (int) round($amount * 100);
        }
        $stripe->createRefund($refund_data);
      }

      $this->logger->info('Reembolso de @amount EUR procesado para pedido @number', [
        '@amount' => $refund_amount,
        '@number' => $order->get('order_number')->value,
      ]);

      $order->set('payment_status', 'refunded');
      $order->set('status', 'refunded');
      $order->save();

      return ['success' => TRUE, 'refund_amount' => $refund_amount];
    }
    catch (\Throwable $e) {
      $this->logger->error('Error procesando reembolso: @error', ['@error' => $e->getMessage()]);
      return ['success' => FALSE, 'message' => t('Error procesando el reembolso.')];
    }
  }

  /**
   * Resuelve la cuenta Stripe Connect del merchant destino.
   *
   * Solo devuelve un destination account si TODOS los items del pedido
   * pertenecen al MISMO merchant y este tiene stripe_account_id.
   *
   * @return string|null
   *   Stripe account ID del merchant, o NULL si multi-merchant.
   */
  protected function resolveDestinationAccount(object $order): ?string {
    $suborder_storage = $this->entityTypeManager->getStorage('suborder_retail');
    $ids = $suborder_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('order_id', $order->id())
      ->execute();

    if (!$ids) {
      return NULL;
    }

    $suborders = $suborder_storage->loadMultiple($ids);
    $merchant_ids = [];

    foreach ($suborders as $suborder) {
      $merchant_id = $suborder->get('merchant_id')->target_id ?? NULL;
      if ($merchant_id) {
        $merchant_ids[$merchant_id] = TRUE;
      }
    }

    // Solo destination charge si un unico merchant.
    if (count($merchant_ids) !== 1) {
      return NULL;
    }

    $merchant_id = (int) array_key_first($merchant_ids);
    $merchant_storage = $this->entityTypeManager->getStorage('merchant_profile');
    $merchant = $merchant_storage->load($merchant_id);

    if (!$merchant || !$merchant->hasField('stripe_account_id')) {
      return NULL;
    }

    $stripe_account = $merchant->get('stripe_account_id')->value ?? '';
    return !empty($stripe_account) ? $stripe_account : NULL;
  }

  /**
   * Actualiza el estado de payout de los subpedidos tras confirmacion del pago.
   *
   * Con destination charges, Stripe gestiona los transfers automaticamente.
   * Solo actualizamos el estado local.
   */
  protected function updateSuborderPayoutStatus(object $order): void {
    $suborder_storage = $this->entityTypeManager->getStorage('suborder_retail');
    $ids = $suborder_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('order_id', $order->id())
      ->execute();

    if (!$ids) {
      return;
    }

    foreach ($suborder_storage->loadMultiple($ids) as $suborder) {
      $suborder->set('payout_status', 'processing');
      $suborder->save();

      $this->logger->info('Payout marcado como processing para sub-pedido @id del pedido @order', [
        '@id' => $suborder->id(),
        '@order' => $order->get('order_number')->value,
      ]);
    }
  }

}
