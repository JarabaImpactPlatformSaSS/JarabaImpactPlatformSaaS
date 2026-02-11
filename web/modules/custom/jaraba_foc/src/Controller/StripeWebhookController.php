<?php

declare(strict_types=1);

namespace Drupal\jaraba_foc\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
 * - Gestionar suscripciones y cancelaciones
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
class StripeWebhookController extends ControllerBase implements ContainerInjectionInterface
{

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
        LoggerInterface $logger
    ) {
        $this->stripeConnect = $stripeConnect;
        $this->focLogger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
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
    public function handle(Request $request): Response
    {
        // Obtener payload y firma
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature', '');

        // ═══════════════════════════════════════════════════════════════════════
        // VERIFICACIÓN DE FIRMA
        // ═══════════════════════════════════════════════════════════════════════
        if (!$this->stripeConnect->verifyWebhookSignature($payload, $sigHeader)) {
            $this->focLogger->warning('Webhook de Stripe rechazado: firma inválida.');
            return new JsonResponse(['error' => 'Invalid signature'], 400);
        }

        // Decodificar evento
        $event = json_decode($payload, TRUE);
        if (!$event || !isset($event['type'])) {
            $this->focLogger->warning('Webhook de Stripe rechazado: payload inválido.');
            return new JsonResponse(['error' => 'Invalid payload'], 400);
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
                default => $this->handleUnknownEvent($eventType),
            };
        } catch (\Exception $e) {
            $this->focLogger->error('Error procesando webhook @type: @error', [
                '@type' => $eventType,
                '@error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => 'Processing failed'], 500);
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
    protected function handlePaymentSucceeded(array $data): JsonResponse
    {
        $amount = ($data['amount'] ?? 0) / 100; // Convertir de centavos
        $currency = strtoupper($data['currency'] ?? 'EUR');
        $paymentIntentId = $data['id'] ?? '';
        $applicationFee = ($data['application_fee_amount'] ?? 0) / 100;

        // Extraer metadata
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

        // Registrar comisión de plataforma
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

        return new JsonResponse(['status' => 'processed']);
    }

    /**
     * Procesa factura pagada (suscripciones recurrentes).
     */
    protected function handleInvoicePaid(array $data): JsonResponse
    {
        $amount = ($data['amount_paid'] ?? 0) / 100;
        $currency = strtoupper($data['currency'] ?? 'EUR');
        $invoiceId = $data['id'] ?? '';

        // Las facturas de suscripción son ingresos recurrentes
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

        return new JsonResponse(['status' => 'processed']);
    }

    /**
     * Procesa reembolso.
     */
    protected function handleChargeRefunded(array $data): JsonResponse
    {
        $amountRefunded = ($data['amount_refunded'] ?? 0) / 100;
        $currency = strtoupper($data['currency'] ?? 'EUR');
        $chargeId = $data['id'] ?? '';

        // Registrar transacción negativa (reembolso)
        $this->createFinancialTransaction([
            'amount' => -$amountRefunded, // Negativo para indicar salida
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

        return new JsonResponse(['status' => 'processed']);
    }

    /**
     * Procesa actualización de cuenta de vendedor.
     */
    protected function handleAccountUpdated(array $data): JsonResponse
    {
        $accountId = $data['id'] ?? '';
        $chargesEnabled = $data['charges_enabled'] ?? FALSE;
        $payoutsEnabled = $data['payouts_enabled'] ?? FALSE;

        $this->focLogger->info('Cuenta @id actualizada: charges=@charges, payouts=@payouts', [
            '@id' => $accountId,
            '@charges' => $chargesEnabled ? 'true' : 'false',
            '@payouts' => $payoutsEnabled ? 'true' : 'false',
        ]);

        // TODO: Actualizar entidad de vendedor en Drupal

        return new JsonResponse(['status' => 'processed']);
    }

    /**
     * Procesa creación de suscripción.
     */
    protected function handleSubscriptionCreated(array $data): JsonResponse
    {
        $subscriptionId = $data['id'] ?? '';

        $this->focLogger->info('Suscripción creada: @id', ['@id' => $subscriptionId]);

        return new JsonResponse(['status' => 'processed']);
    }

    /**
     * Procesa cancelación de suscripción.
     */
    protected function handleSubscriptionCanceled(array $data): JsonResponse
    {
        $subscriptionId = $data['id'] ?? '';

        $this->logger->info('Suscripción cancelada: @id', ['@id' => $subscriptionId]);

        // TODO: Actualizar métricas de churn

        return new JsonResponse(['status' => 'processed']);
    }

    /**
     * Maneja eventos no reconocidos.
     */
    protected function handleUnknownEvent(string $eventType): JsonResponse
    {
        $this->focLogger->debug('Evento Stripe ignorado: @type', ['@type' => $eventType]);
        return new JsonResponse(['status' => 'ignored']);
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
    protected function createFinancialTransaction(array $data): ?int
    {
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
                // TODO: Mapear transaction_type a término de taxonomía
            ]);

            $transaction->save();

            return (int) $transaction->id();
        } catch (\Exception $e) {
            $this->focLogger->error('Error creando FinancialTransaction: @error', [
                '@error' => $e->getMessage(),
            ]);
            return NULL;
        }
    }

}
