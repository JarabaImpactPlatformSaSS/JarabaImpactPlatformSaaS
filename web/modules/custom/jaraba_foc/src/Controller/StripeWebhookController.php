<?php

declare(strict_types=1);

namespace Drupal\jaraba_foc\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\jaraba_foc\Service\StripeConnectService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controlador de webhooks de Stripe Connect.
 *
 * PROPÓSITO:
 * Recibe y procesa eventos de Stripe para:
 * - Registrar transacciones financieras automáticamente
 * - Actualizar estado de cuentas de vendedores
 * - Gestionar suscripciones y cancelaciones.
 *
 * EVENTOS MANEJADOS:
 * - payment_intent.succeeded: Pago completado → Registrar FinancialTransaction
 * - invoice.paid: Factura pagada (suscripciones) → Registrar ingreso recurrente
 * - charge.refunded: Reembolso → Registrar transacción negativa
 * - account.updated: Cambios en cuenta vendedor → Actualizar estado
 *
 * SEGURIDAD:
 * Todas las peticiones se verifican mediante firma HMAC antes de procesar.
 *
 * @see https://stripe.com/docs/webhooks
 */
class StripeWebhookController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Servicio de Stripe Connect.
   *
   * @var \Drupal\jaraba_foc\Service\StripeConnectService
   */
  protected StripeConnectService $stripeConnect;

  /**
   * Logger del módulo.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $focLogger;

  /**
   * Constructor del controlador.
   *
   * @param \Drupal\jaraba_foc\Service\StripeConnectService $stripeConnect
   *   El servicio de Stripe Connect.
   * @param \Psr\Log\LoggerInterface $logger
   *   El logger del módulo.
   */
  public function __construct(
    StripeConnectService $stripeConnect,
    LoggerInterface $logger,
  ) {
    $this->stripeConnect = $stripeConnect;
    $this->focLogger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
          $container->get('jaraba_foc.stripe_connect'),
          $container->get('logger.channel.jaraba_foc')
      );
  }

  /**
   * Procesa los webhooks entrantes de Stripe.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La petición HTTP entrante.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Respuesta HTTP (200 OK o error).
   */
  public function handle(Request $request): Response {
    // Obtener payload y firma.
    $payload = $request->getContent();
    $sigHeader = $request->headers->get('Stripe-Signature', '');

    // ═══════════════════════════════════════════════════════════════════════
    // VERIFICACIÓN DE FIRMA
    // ═══════════════════════════════════════════════════════════════════════
    if (!$this->stripeConnect->verifyWebhookSignature($payload, $sigHeader)) {
      $this->focLogger->warning('Webhook de Stripe rechazado: firma inválida.');
      // AUDIT-CONS-N08: Standardized JSON envelope.
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Invalid signature']], 400);
    }

    // Decodificar evento.
    $event = json_decode($payload, TRUE);
    if (!$event || !isset($event['type'])) {
      $this->focLogger->warning('Webhook de Stripe rechazado: payload inválido.');
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Invalid payload']], 400);
    }

    $eventType = $event['type'];
    $eventData = $event['data']['object'] ?? [];

    $this->focLogger->info('Webhook Stripe recibido: @type', ['@type' => $eventType]);

    // ═══════════════════════════════════════════════════════════════════════
    // DISPATCHER DE EVENTOS
    // ═══════════════════════════════════════════════════════════════════════
    try {
      return match ($eventType) {
        'payment_intent.succeeded' => $this->handlePaymentSucceeded($eventData),
                'invoice.paid' => $this->handleInvoicePaid($eventData),
                'charge.refunded' => $this->handleChargeRefunded($eventData),
                'account.updated' => $this->handleAccountUpdated($eventData),
                'customer.subscription.created' => $this->handleSubscriptionCreated($eventData),
                'customer.subscription.deleted' => $this->handleSubscriptionCanceled($eventData),
                // GAP-H07: Commerce checkout, transfer and payout handlers.
                'checkout.session.completed' => $this->handleCheckoutSessionCompleted($eventData),
                'transfer.created' => $this->handleTransferCreated($eventData),
                'payout.paid' => $this->handlePayoutPaid($eventData),
                default => $this->handleUnknownEvent($eventType),
      };
    }
    catch (\Exception $e) {
      $this->focLogger->error('Error procesando webhook @type: @error', [
        '@type' => $eventType,
        '@error' => $e->getMessage(),
      ]);
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Processing failed']], 500);
    }
  }

  // ═══════════════════════════════════════════════════════════════════════════
  // HANDLERS DE EVENTOS
  // ═══════════════════════════════════════════════════════════════════════════

  /**
   * Procesa pago exitoso (Destination Charge).
   *
   * Crea una FinancialTransaction con tipo 'one_time_sale' o 'commission'.
   */
  protected function handlePaymentSucceeded(array $data): JsonResponse {
    // Convertir de centavos.
    $amount = ($data['amount'] ?? 0) / 100;
    $currency = strtoupper($data['currency'] ?? 'EUR');
    $paymentIntentId = $data['id'] ?? '';
    $applicationFee = ($data['application_fee_amount'] ?? 0) / 100;

    // Extraer metadata.
    $metadata = $data['metadata'] ?? [];
    $tenantId = $metadata['tenant_id'] ?? NULL;

    // Registrar transacción principal (ingreso del vendedor)
    $this->createFinancialTransaction([
      'amount' => $amount - $applicationFee,
      'currency' => $currency,
      'transaction_type' => 'one_time_sale',
      'source_system' => 'stripe_connect',
      'external_id' => $paymentIntentId,
      'related_tenant' => $tenantId,
      'is_recurring' => FALSE,
      'description' => 'Pago via Stripe Connect',
    ]);

    // Registrar comisión de plataforma.
    if ($applicationFee > 0) {
      $this->createFinancialTransaction([
        'amount' => $applicationFee,
        'currency' => $currency,
        'transaction_type' => 'commission',
        'source_system' => 'stripe_connect',
        'external_id' => $paymentIntentId . '_fee',
        'is_recurring' => FALSE,
        'description' => 'Comisión plataforma (application_fee)',
      ]);
    }

    $this->focLogger->info('PaymentIntent procesado: @id, monto: @amount @currency', [
      '@id' => $paymentIntentId,
      '@amount' => $amount,
      '@currency' => $currency,
    ]);

    return new JsonResponse(['success' => TRUE, 'data' => ['status' => 'processed'], 'meta' => ['timestamp' => time()]]);
  }

  /**
   * Procesa factura pagada (suscripciones recurrentes).
   */
  protected function handleInvoicePaid(array $data): JsonResponse {
    $amount = ($data['amount_paid'] ?? 0) / 100;
    $currency = strtoupper($data['currency'] ?? 'EUR');
    $invoiceId = $data['id'] ?? '';

    // Las facturas de suscripción son ingresos recurrentes.
    $this->createFinancialTransaction([
      'amount' => $amount,
      'currency' => $currency,
      'transaction_type' => 'recurring_revenue',
      'source_system' => 'stripe_connect',
      'external_id' => $invoiceId,
      'is_recurring' => TRUE,
      'description' => 'Pago de suscripción (invoice)',
    ]);

    $this->focLogger->info('Invoice procesada: @id, monto: @amount', [
      '@id' => $invoiceId,
      '@amount' => $amount,
    ]);

    return new JsonResponse(['success' => TRUE, 'data' => ['status' => 'processed'], 'meta' => ['timestamp' => time()]]);
  }

  /**
   * Procesa reembolso.
   */
  protected function handleChargeRefunded(array $data): JsonResponse {
    $amountRefunded = ($data['amount_refunded'] ?? 0) / 100;
    $currency = strtoupper($data['currency'] ?? 'EUR');
    $chargeId = $data['id'] ?? '';

    // Registrar transacción negativa (reembolso)
    $this->createFinancialTransaction([
    // Negativo para indicar salida.
      'amount' => -$amountRefunded,
      'currency' => $currency,
      'transaction_type' => 'refund',
      'source_system' => 'stripe_connect',
      'external_id' => $chargeId . '_refund',
      'is_recurring' => FALSE,
      'description' => 'Reembolso procesado',
    ]);

    $this->focLogger->info('Reembolso procesado: @id, monto: @amount', [
      '@id' => $chargeId,
      '@amount' => $amountRefunded,
    ]);

    return new JsonResponse(['success' => TRUE, 'data' => ['status' => 'processed'], 'meta' => ['timestamp' => time()]]);
  }

  /**
   * Procesa actualización de cuenta de vendedor.
   */
  protected function handleAccountUpdated(array $data): JsonResponse {
    $accountId = $data['id'] ?? '';
    $chargesEnabled = $data['charges_enabled'] ?? FALSE;
    $payoutsEnabled = $data['payouts_enabled'] ?? FALSE;

    $this->focLogger->info('Cuenta @id actualizada: charges=@charges, payouts=@payouts', [
      '@id' => $accountId,
      '@charges' => $chargesEnabled ? 'true' : 'false',
      '@payouts' => $payoutsEnabled ? 'true' : 'false',
    ]);

    // Actualizar entidad foc_seller con datos de Stripe (account.updated).
    try {
      $sellerStorage = $this->entityTypeManager()->getStorage('foc_seller');
      $sellers = $sellerStorage->loadByProperties(['stripe_account_id' => $accountId]);

      if (!empty($sellers)) {
        /** @var \Drupal\jaraba_foc\Entity\FocSellerInterface $seller */
        $seller = reset($sellers);
        $seller->set('charges_enabled', $chargesEnabled);
        $seller->set('payouts_enabled', $payoutsEnabled);
        $seller->set('stripe_status', ($chargesEnabled && $payoutsEnabled) ? 'active' : 'pending');
        $seller->save();

        $this->focLogger->info('Seller @id actualizado desde Stripe account.updated', [
          '@id' => $seller->id(),
        ]);
      }
    }
    catch (\Exception $e) {
      $this->focLogger->error('Error actualizando seller desde account.updated: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return new JsonResponse(['success' => TRUE, 'data' => ['status' => 'processed'], 'meta' => ['timestamp' => time()]]);
  }

  /**
   * Procesa creación de suscripción.
   */
  protected function handleSubscriptionCreated(array $data): JsonResponse {
    $subscriptionId = $data['id'] ?? '';

    $this->focLogger->info('Suscripción creada: @id', ['@id' => $subscriptionId]);

    return new JsonResponse(['success' => TRUE, 'data' => ['status' => 'processed'], 'meta' => ['timestamp' => time()]]);
  }

  /**
   * Procesa cancelación de suscripción.
   */
  protected function handleSubscriptionCanceled(array $data): JsonResponse {
    $subscriptionId = $data['id'] ?? '';

    $this->logger->info('Suscripción cancelada: @id', ['@id' => $subscriptionId]);

    // Calcular y actualizar churn_rate en métricas de seller.
    try {
      $sellerStorage = $this->entityTypeManager()->getStorage('foc_seller');
      $metadata = $data['metadata'] ?? [];
      $tenantId = $metadata['tenant_id'] ?? NULL;

      if ($tenantId) {
        $sellers = $sellerStorage->loadByProperties(['tenant_id' => $tenantId]);
        if (!empty($sellers)) {
          /** @var \Drupal\jaraba_foc\Entity\FocSellerInterface $seller */
          $seller = reset($sellers);

          $totalSubscriptions = (int) ($seller->get('total_subscriptions')->value ?? 0);
          $canceledSubscriptions = (int) ($seller->get('canceled_subscriptions')->value ?? 0) + 1;
          $seller->set('canceled_subscriptions', $canceledSubscriptions);

          $churnRate = $totalSubscriptions > 0
                        ? round(($canceledSubscriptions / $totalSubscriptions) * 100, 2)
                        : 0;
          $seller->set('churn_rate', $churnRate);
          $seller->save();

          $this->focLogger->info('Churn rate actualizado para seller @id: @rate%', [
            '@id' => $seller->id(),
            '@rate' => $churnRate,
          ]);
        }
      }
    }
    catch (\Exception $e) {
      $this->focLogger->error('Error actualizando churn metrics: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return new JsonResponse(['success' => TRUE, 'data' => ['status' => 'processed'], 'meta' => ['timestamp' => time()]]);
  }

  /**
   * GAP-H07: Procesa checkout session completado (Connect marketplace).
   *
   * Registra la venta en el libro mayor cuando un checkout de marketplace
   * se completa exitosamente. Incluye desglose de comision de plataforma.
   */
  protected function handleCheckoutSessionCompleted(array $data): JsonResponse {
    $sessionId = $data['id'] ?? '';
    $amountTotal = ($data['amount_total'] ?? 0) / 100;
    $currency = strtoupper($data['currency'] ?? 'EUR');
    $metadata = $data['metadata'] ?? [];
    $tenantId = $metadata['tenant_id'] ?? NULL;
    $vertical = $metadata['vertical'] ?? 'comercioconecta';

    $this->createFinancialTransaction([
      'amount' => $amountTotal,
      'currency' => $currency,
      'transaction_type' => 'one_time_sale',
      'source_system' => 'stripe_connect',
      'external_id' => $sessionId,
      'related_tenant' => $tenantId,
      'is_recurring' => FALSE,
      'description' => sprintf('Checkout completado (session %s)', substr($sessionId, 0, 20)),
      'related_vertical' => $vertical,
    ]);

    $this->focLogger->info('Checkout session completado: @id, monto: @amount @currency', [
      '@id' => $sessionId,
      '@amount' => $amountTotal,
      '@currency' => $currency,
    ]);

    return new JsonResponse(['success' => TRUE, 'data' => ['status' => 'processed'], 'meta' => ['timestamp' => time()]]);
  }

  /**
   * GAP-H07: Procesa transferencia creada (payout a connected account).
   *
   * Registra la transferencia como gasto operativo (platform -> merchant).
   */
  protected function handleTransferCreated(array $data): JsonResponse {
    $transferId = $data['id'] ?? '';
    $amount = ($data['amount'] ?? 0) / 100;
    $currency = strtoupper($data['currency'] ?? 'EUR');
    $destinationAccount = $data['destination'] ?? '';

    $this->createFinancialTransaction([
    // Negativo: salida de la plataforma.
      'amount' => -$amount,
      'currency' => $currency,
      'transaction_type' => 'transfer_out',
      'source_system' => 'stripe_connect',
      'external_id' => $transferId,
      'is_recurring' => FALSE,
      'description' => sprintf('Transferencia a cuenta conectada %s', substr($destinationAccount, 0, 15)),
    ]);

    $this->focLogger->info('Transfer creado: @id -> @dest, monto: @amount @currency', [
      '@id' => $transferId,
      '@dest' => $destinationAccount,
      '@amount' => $amount,
      '@currency' => $currency,
    ]);

    return new JsonResponse(['success' => TRUE, 'data' => ['status' => 'processed'], 'meta' => ['timestamp' => time()]]);
  }

  /**
   * GAP-H07: Procesa payout ejecutado (Stripe -> bank account).
   *
   * Registra el payout como informacion de liquidacion bancaria.
   */
  protected function handlePayoutPaid(array $data): JsonResponse {
    $payoutId = $data['id'] ?? '';
    $amount = ($data['amount'] ?? 0) / 100;
    $currency = strtoupper($data['currency'] ?? 'EUR');
    $arrivalDate = $data['arrival_date'] ?? NULL;

    $this->createFinancialTransaction([
    // Negativo: dinero sale de Stripe a banco.
      'amount' => -$amount,
      'currency' => $currency,
      'transaction_type' => 'payout',
      'source_system' => 'stripe_connect',
      'external_id' => $payoutId,
      'is_recurring' => FALSE,
      'description' => sprintf('Payout a cuenta bancaria (llegada: %s)', $arrivalDate ? date('Y-m-d', $arrivalDate) : 'pendiente'),
    ]);

    $this->focLogger->info('Payout ejecutado: @id, monto: @amount @currency', [
      '@id' => $payoutId,
      '@amount' => $amount,
      '@currency' => $currency,
    ]);

    return new JsonResponse(['success' => TRUE, 'data' => ['status' => 'processed'], 'meta' => ['timestamp' => time()]]);
  }

  /**
   * Maneja eventos no reconocidos.
   */
  protected function handleUnknownEvent(string $eventType): JsonResponse {
    $this->focLogger->debug('Evento Stripe ignorado: @type', ['@type' => $eventType]);
    return new JsonResponse(['success' => TRUE, 'data' => ['status' => 'ignored'], 'meta' => ['timestamp' => time()]]);
  }

  // ═══════════════════════════════════════════════════════════════════════════
  // HELPERS
  // ═══════════════════════════════════════════════════════════════════════════

  /**
   * Crea una entidad FinancialTransaction.
   *
   * @param array $data
   *   Datos de la transacción.
   *
   * @return int|null
   *   ID de la transacción creada o NULL si falla.
   */
  protected function createFinancialTransaction(array $data): ?int {
    try {
      $storage = $this->entityTypeManager()->getStorage('financial_transaction');

      $transaction = $storage->create([
        'amount' => $data['amount'],
        'currency' => $data['currency'] ?? 'EUR',
        'source_system' => $data['source_system'] ?? 'stripe_connect',
        'external_id' => $data['external_id'] ?? NULL,
        'is_recurring' => $data['is_recurring'] ?? FALSE,
        'description' => $data['description'] ?? '',
        'related_tenant' => $data['related_tenant'] ?? NULL,
        'transaction_type' => $this->mapTransactionTypeToTerm($data['transaction_type'] ?? 'other'),
      ]);

      $transaction->save();

      return (int) $transaction->id();
    }
    catch (\Exception $e) {
      $this->focLogger->error('Error creando FinancialTransaction: @error', [
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Maps a transaction_type string to a taxonomy term ID in foc_transaction_types.
   *
   * @param string $type
   *   The transaction type machine name (e.g. 'one_time_sale', 'commission').
   *
   * @return int|null
   *   The taxonomy term ID, or NULL if not found.
   */
  protected function mapTransactionTypeToTerm(string $type): ?int {
    try {
      $termStorage = $this->entityTypeManager()->getStorage('taxonomy_term');
      $terms = $termStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('vid', 'foc_transaction_types')
        ->condition('field_machine_name', $type)
        ->range(0, 1)
        ->execute();

      if (!empty($terms)) {
        return (int) reset($terms);
      }

      // Fallback: try matching by term name.
      $termsByName = $termStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('vid', 'foc_transaction_types')
        ->condition('name', $type)
        ->range(0, 1)
        ->execute();

      return !empty($termsByName) ? (int) reset($termsByName) : NULL;
    }
    catch (\Exception $e) {
      $this->focLogger->warning('Could not map transaction_type @type to taxonomy: @error', [
        '@type' => $type,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

}
