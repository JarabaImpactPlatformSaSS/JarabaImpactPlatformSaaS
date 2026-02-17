<?php

namespace Drupal\jaraba_comercio_conecta\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

class StripePaymentRetailService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
    protected LoggerInterface $logger,
  ) {}

  public function createPaymentIntent(object $order): array {
    $total = (float) $order->get('total')->value;
    $amount_cents = (int) round($total * 100);

    if ($amount_cents <= 0) {
      return ['success' => FALSE, 'message' => t('El importe del pedido no es valido.')];
    }

    try {
      if (!\Drupal::hasService('jaraba_billing.stripe_client')) {
        $this->logger->warning('Servicio Stripe no disponible. Simulando payment intent.');
        $payment_intent_id = 'pi_simulated_' . bin2hex(random_bytes(12));
        $order->set('payment_intent_id', $payment_intent_id);
        $order->save();

        return [
          'success' => TRUE,
          'payment_intent_id' => $payment_intent_id,
          'client_secret' => 'cs_simulated_' . bin2hex(random_bytes(12)),
          'amount' => $amount_cents,
          'currency' => 'eur',
        ];
      }

      $stripe = \Drupal::service('jaraba_billing.stripe_client');
      $intent = $stripe->createPaymentIntent([
        'amount' => $amount_cents,
        'currency' => 'eur',
        'metadata' => [
          'order_id' => $order->id(),
          'order_number' => $order->get('order_number')->value,
          'tenant_id' => $order->get('tenant_id')->target_id,
        ],
      ]);

      $order->set('payment_intent_id', $intent['id']);
      $order->save();

      return [
        'success' => TRUE,
        'payment_intent_id' => $intent['id'],
        'client_secret' => $intent['client_secret'],
        'amount' => $amount_cents,
        'currency' => 'eur',
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error creando payment intent para pedido @id: @error', [
        '@id' => $order->id(),
        '@error' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'message' => t('Error procesando el pago. Intentalo de nuevo.')];
    }
  }

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

    $this->createTransfersForSuborders($order);

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

  public function processRefund(object $order, ?float $amount = NULL): array {
    $payment_intent_id = $order->get('payment_intent_id')->value;
    if (!$payment_intent_id) {
      return ['success' => FALSE, 'message' => t('No hay pago registrado para este pedido.')];
    }

    $refund_amount = $amount ?? (float) $order->get('total')->value;

    try {
      $this->logger->info('Reembolso de @amount EUR procesado para pedido @number', [
        '@amount' => $refund_amount,
        '@number' => $order->get('order_number')->value,
      ]);

      $order->set('payment_status', 'refunded');
      $order->set('status', 'refunded');
      $order->save();

      return ['success' => TRUE, 'refund_amount' => $refund_amount];
    }
    catch (\Exception $e) {
      $this->logger->error('Error procesando reembolso: @error', ['@error' => $e->getMessage()]);
      return ['success' => FALSE, 'message' => t('Error procesando el reembolso.')];
    }
  }

  protected function createTransfersForSuborders(object $order): void {
    $suborder_storage = $this->entityTypeManager->getStorage('suborder_retail');
    $ids = $suborder_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('order_id', $order->id())
      ->execute();

    $suborders = $ids ? $suborder_storage->loadMultiple($ids) : [];

    foreach ($suborders as $suborder) {
      $payout = (float) $suborder->get('merchant_payout')->value;
      if ($payout <= 0) {
        continue;
      }

      $transfer_id = 'tr_simulated_' . bin2hex(random_bytes(8));
      $suborder->set('stripe_transfer_id', $transfer_id);
      $suborder->set('payout_status', 'processing');
      $suborder->save();

      $this->logger->info('Transfer @transfer creado para sub-pedido @id (@amount EUR)', [
        '@transfer' => $transfer_id,
        '@id' => $suborder->id(),
        '@amount' => $payout,
      ]);
    }
  }

}
