<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\AgroConectaFeatureGateService;
use Drupal\jaraba_agroconecta_core\Entity\OrderAgro;
use Drupal\jaraba_foc\Service\StripeConnectService;
use Psr\Log\LoggerInterface;

/**
 * Servicio de pagos con Stripe para AgroConecta.
 *
 * PROPÓSITO:
 * Wrapper sobre jaraba_foc.stripe_connect para operaciones específicas
 * del marketplace: PaymentIntents, Transfers por sub-pedido, y Refunds.
 * Integra FeatureGateService para comisiones dinamicas por plan.
 *
 * MODELO:
 * - PaymentIntent: Cobro único al cliente por el total del pedido
 * - Transfers: Distribución posterior a cada productor (Separate Charges & Transfers)
 * - Platform retains: commission_amount de cada sub-pedido (segun plan del productor)
 *
 * @see \Drupal\jaraba_foc\Service\StripeConnectService
 */
class StripePaymentService
{

    /**
     * Constructor del servicio.
     */
    public function __construct(
        protected StripeConnectService $stripeConnect,
        protected EntityTypeManagerInterface $entityTypeManager,
        protected LoggerInterface $logger,
        protected ConfigFactoryInterface $configFactory,
        protected AgroConectaFeatureGateService $featureGate,
    ) {
    }

    /**
     * Crea un PaymentIntent para un pedido.
     *
     * @param int $orderId
     *   ID del pedido.
     *
     * @return array|null
     *   Datos del PaymentIntent (id, client_secret, amount) o NULL si falla.
     */
    public function createPaymentIntent(int $orderId): ?array
    {
        try {
            $orderStorage = $this->entityTypeManager->getStorage('order_agro');
            /** @var \Drupal\jaraba_agroconecta_core\Entity\OrderAgro $order */
            $order = $orderStorage->load($orderId);

            if (!$order) {
                $this->logger->error('Pedido @id no encontrado para crear PaymentIntent.', [
                    '@id' => $orderId,
                ]);
                return NULL;
            }

            $total = (float) $order->get('total')->value;
            $amountCents = (int) round($total * 100);
            $currency = strtolower($order->get('currency')->value ?? 'eur');

            // Crear PaymentIntent sin destination (Separate Charges & Transfers model).
            $secretKey = $this->stripeConnect->getSecretKey();
            if (!$secretKey) {
                $this->logger->error('Stripe secret key no configurada.');
                return NULL;
            }

            $paymentIntentData = [
                'amount' => $amountCents,
                'currency' => $currency,
                'payment_method_types' => ['card'],
                'metadata' => [
                    'order_id' => (string) $order->id(),
                    'order_number' => $order->get('order_number')->value,
                    'platform' => 'agroconecta',
                ],
            ];

            $result = $this->stripeConnect->stripeRequest('POST', '/payment_intents', $paymentIntentData);

            if (!empty($result['id'])) {
                // Guardar PaymentIntent ID en el pedido.
                $order->set('stripe_payment_intent', $result['id']);
                $order->save();

                $this->logger->info('PaymentIntent @pi creado para pedido @order (€@amount).', [
                    '@pi' => $result['id'],
                    '@order' => $order->get('order_number')->value,
                    '@amount' => $total,
                ]);

                return [
                    'id' => $result['id'],
                    'client_secret' => $result['client_secret'] ?? '',
                    'amount' => $amountCents,
                    'currency' => $currency,
                ];
            }

            return NULL;
        } catch (\Exception $e) {
            $this->logger->error('Error al crear PaymentIntent: @message', [
                '@message' => $e->getMessage(),
            ]);
            return NULL;
        }
    }

    /**
     * Procesa la confirmación de pago y crea Transfers para cada productor.
     *
     * Se invoca tras webhook payment_intent.succeeded o confirmación frontend.
     *
     * @param string $paymentIntentId
     *   ID del PaymentIntent de Stripe.
     *
     * @return bool
     *   TRUE si el procesamiento fue exitoso.
     */
    public function handlePaymentConfirmation(string $paymentIntentId): bool
    {
        try {
            $orderStorage = $this->entityTypeManager->getStorage('order_agro');
            $suborderStorage = $this->entityTypeManager->getStorage('suborder_agro');

            // Buscar pedido por PaymentIntent ID.
            $orderIds = $orderStorage->getQuery()
                ->accessCheck(FALSE)
                ->condition('stripe_payment_intent', $paymentIntentId)
                ->execute();

            if (empty($orderIds)) {
                $this->logger->warning('No se encontró pedido para PaymentIntent @pi.', [
                    '@pi' => $paymentIntentId,
                ]);
                return FALSE;
            }

            /** @var \Drupal\jaraba_agroconecta_core\Entity\OrderAgro $order */
            $order = $orderStorage->load(reset($orderIds));
            $order->set('payment_state', 'paid');
            $order->set('state', OrderAgro::STATE_PAID);
            $order->set('placed_at', date('Y-m-d\TH:i:s'));
            $order->save();

            // Cargar sub-pedidos y crear Transfers.
            $suborderIds = $suborderStorage->getQuery()
                ->accessCheck(FALSE)
                ->condition('order_id', $order->id())
                ->execute();

            foreach ($suborderStorage->loadMultiple($suborderIds) as $suborder) {
                $this->createProducerTransfer((int) $suborder->id());
            }

            $this->logger->info('Pago confirmado para pedido @order. @count transfers creados.', [
                '@order' => $order->get('order_number')->value,
                '@count' => count($suborderIds),
            ]);

            return TRUE;
        } catch (\Exception $e) {
            $this->logger->error('Error al procesar confirmación de pago: @message', [
                '@message' => $e->getMessage(),
            ]);
            return FALSE;
        }
    }

    /**
     * Crea un Transfer de Stripe hacia la cuenta Connect del productor.
     *
     * @param int $suborderId
     *   ID del sub-pedido.
     *
     * @return bool
     *   TRUE si el transfer fue exitoso.
     */
    public function createProducerTransfer(int $suborderId): bool
    {
        try {
            $suborderStorage = $this->entityTypeManager->getStorage('suborder_agro');
            $producerStorage = $this->entityTypeManager->getStorage('producer_profile');

            /** @var \Drupal\jaraba_agroconecta_core\Entity\SuborderAgro $suborder */
            $suborder = $suborderStorage->load($suborderId);
            if (!$suborder) {
                return FALSE;
            }

            // Obtener cuenta Stripe del productor.
            $producer = $producerStorage->load($suborder->get('producer_id')->target_id);
            if (!$producer) {
                return FALSE;
            }

            $stripeAccountId = $producer->get('stripe_account_id')->value ?? '';
            if (empty($stripeAccountId)) {
                $this->logger->warning('Productor @id sin cuenta Stripe. Transfer pendiente.', [
                    '@id' => $producer->id(),
                ]);
                $suborder->set('payout_state', 'failed');
                $suborder->save();
                return FALSE;
            }

            $payoutAmount = (float) $suborder->get('producer_payout')->value;
            $amountCents = (int) round($payoutAmount * 100);

            // Obtener PaymentIntent del pedido padre.
            $orderStorage = $this->entityTypeManager->getStorage('order_agro');
            $order = $orderStorage->load($suborder->get('order_id')->target_id);
            $paymentIntentId = $order ? $order->get('stripe_payment_intent')->value : '';

            $transferData = [
                'amount' => $amountCents,
                'currency' => 'eur',
                'destination' => $stripeAccountId,
                'source_transaction' => $paymentIntentId,
                'metadata' => [
                    'suborder_id' => (string) $suborder->id(),
                    'suborder_number' => $suborder->get('suborder_number')->value,
                    'producer_id' => (string) $producer->id(),
                ],
            ];

            $result = $this->stripeConnect->stripeRequest('POST', '/transfers', $transferData);

            if (!empty($result['id'])) {
                $suborder->set('stripe_transfer_id', $result['id']);
                $suborder->set('payout_state', 'transferred');
                $suborder->save();

                $this->logger->info('Transfer @tr creado: €@amount → Productor @producer.', [
                    '@tr' => $result['id'],
                    '@amount' => $payoutAmount,
                    '@producer' => $producer->id(),
                ]);

                return TRUE;
            }

            return FALSE;
        } catch (\Exception $e) {
            $this->logger->error('Error al crear Transfer: @message', [
                '@message' => $e->getMessage(),
            ]);
            return FALSE;
        }
    }

    /**
     * Procesa un reembolso para un pedido.
     *
     * @param int $orderId
     *   ID del pedido.
     * @param float|null $amount
     *   Importe del reembolso (NULL = reembolso total).
     *
     * @return bool
     *   TRUE si el reembolso fue exitoso.
     */
    public function processRefund(int $orderId, ?float $amount = NULL): bool
    {
        try {
            $orderStorage = $this->entityTypeManager->getStorage('order_agro');
            /** @var \Drupal\jaraba_agroconecta_core\Entity\OrderAgro $order */
            $order = $orderStorage->load($orderId);

            if (!$order) {
                return FALSE;
            }

            $paymentIntentId = $order->get('stripe_payment_intent')->value;
            if (empty($paymentIntentId)) {
                return FALSE;
            }

            $refundData = [
                'payment_intent' => $paymentIntentId,
            ];

            if ($amount !== NULL) {
                $refundData['amount'] = (int) round($amount * 100);
            }

            $result = $this->stripeConnect->stripeRequest('POST', '/refunds', $refundData);

            if (!empty($result['id'])) {
                $order->set('payment_state', $amount === NULL ? 'refunded' : 'partially_refunded');
                $order->save();

                $this->logger->info('Reembolso @ref procesado para pedido @order (€@amount).', [
                    '@ref' => $result['id'],
                    '@order' => $order->get('order_number')->value,
                    '@amount' => $amount ?? $order->get('total')->value,
                ]);

                return TRUE;
            }

            return FALSE;
        } catch (\Exception $e) {
            $this->logger->error('Error al procesar reembolso: @message', [
                '@message' => $e->getMessage(),
            ]);
            return FALSE;
        }
    }

}
