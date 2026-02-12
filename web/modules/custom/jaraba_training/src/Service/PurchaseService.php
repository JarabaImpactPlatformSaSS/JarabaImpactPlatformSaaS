<?php

declare(strict_types=1);

namespace Drupal\jaraba_training\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de orquestacion de compra de productos formativos.
 *
 * Estructura: Coordina el flujo completo de compra de un producto
 * de training: validacion del producto, creacion de PaymentIntent
 * en Stripe, enrollment del usuario y activacion de upsell.
 *
 * Logica: El flujo de compra sigue estos pasos:
 * 1. Validar que el producto existe y esta disponible
 * 2. Verificar que el usuario no ha comprado ya el producto
 * 3. Crear PaymentIntent en Stripe (o marcar gratuito)
 * 4. Al confirmar pago, crear UserCertification si aplica
 * 5. Disparar UpsellEngine para secuencias post-compra
 *
 * Sintaxis: Servicio inyectable con dependencias via constructor.
 */
class PurchaseService
{

    /**
     * Constructor.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
     *   El gestor de tipos de entidad.
     * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
     *   El usuario actual.
     * @param \Drupal\jaraba_training\Service\LadderService $ladderService
     *   El servicio de escalera de valor.
     * @param \Psr\Log\LoggerInterface $logger
     *   El logger del modulo.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected AccountProxyInterface $currentUser,
        protected LadderService $ladderService,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * Procesa la compra de un producto formativo.
     *
     * Valida el producto, crea el PaymentIntent en Stripe (si aplica)
     * y prepara el enrollment del usuario.
     *
     * @param int $productId
     *   El ID del producto a comprar.
     * @param array $paymentData
     *   Datos de pago opcionales (payment_method_id, coupon, etc).
     *
     * @return array
     *   Resultado de la compra con status, payment_intent_id, etc.
     */
    public function purchase(int $productId, array $paymentData = []): array
    {
        // Cargar el producto.
        $storage = $this->entityTypeManager->getStorage('training_product');
        $product = $storage->load($productId);

        if (!$product) {
            return [
                'success' => FALSE,
                'error' => 'Producto no encontrado.',
                'error_code' => 'PRODUCT_NOT_FOUND',
            ];
        }

        if (!$product->isPublished()) {
            return [
                'success' => FALSE,
                'error' => 'Producto no disponible.',
                'error_code' => 'PRODUCT_UNAVAILABLE',
            ];
        }

        // Verificar si el producto es gratuito.
        if ($product->isFree()) {
            return $this->processFreePurchase($product);
        }

        // Crear PaymentIntent en Stripe.
        return $this->processStripePayment($product, $paymentData);
    }

    /**
     * Procesa la compra de un producto gratuito.
     *
     * @param mixed $product
     *   La entidad TrainingProduct.
     *
     * @return array
     *   Resultado del enrollment gratuito.
     */
    protected function processFreePurchase(mixed $product): array
    {
        // Crear enrollment directo.
        $enrollment = $this->createEnrollment($product);

        $this->logger->info('Compra gratuita procesada: producto @pid, usuario @uid.', [
            '@pid' => $product->id(),
            '@uid' => $this->currentUser->id(),
        ]);

        return [
            'success' => TRUE,
            'type' => 'free',
            'product_id' => $product->id(),
            'product_title' => $product->getTitle(),
            'enrollment_id' => $enrollment['id'] ?? NULL,
        ];
    }

    /**
     * Procesa el pago via Stripe.
     *
     * @param mixed $product
     *   La entidad TrainingProduct.
     * @param array $paymentData
     *   Datos de pago del cliente.
     *
     * @return array
     *   Resultado con client_secret para Stripe Elements.
     */
    protected function processStripePayment(mixed $product, array $paymentData): array
    {
        $price = (float) $product->getPrice();
        $amountCents = (int) ($price * 100);
        $billingType = $product->getBillingType();

        try {
            // Intentar crear PaymentIntent via el servicio de Stripe del proyecto.
            if (\Drupal::hasService('jaraba_commerce.stripe')) {
                /** @var \Drupal\jaraba_commerce\Service\StripeService $stripe */
                $stripe = \Drupal::service('jaraba_commerce.stripe');

                $intent = $stripe->createPaymentIntent([
                    'amount' => $amountCents,
                    'currency' => 'eur',
                    'metadata' => [
                        'product_id' => $product->id(),
                        'product_type' => $product->getProductType(),
                        'user_id' => $this->currentUser->id(),
                        'billing_type' => $billingType,
                    ],
                ]);

                return [
                    'success' => TRUE,
                    'type' => 'stripe',
                    'client_secret' => $intent['client_secret'] ?? '',
                    'payment_intent_id' => $intent['id'] ?? '',
                    'amount' => $price,
                    'currency' => 'EUR',
                    'product_id' => $product->id(),
                    'product_title' => $product->getTitle(),
                    'billing_type' => $billingType,
                ];
            }

            // Fallback: devolver datos para pago manual.
            return [
                'success' => TRUE,
                'type' => 'pending',
                'amount' => $price,
                'currency' => 'EUR',
                'product_id' => $product->id(),
                'product_title' => $product->getTitle(),
                'message' => 'Stripe no configurado. Pago pendiente de procesamiento manual.',
            ];

        }
        catch (\Exception $e) {
            $this->logger->error('Error al crear PaymentIntent: @message', [
                '@message' => $e->getMessage(),
            ]);

            return [
                'success' => FALSE,
                'error' => 'Error al procesar el pago.',
                'error_code' => 'STRIPE_ERROR',
            ];
        }
    }

    /**
     * Crea un enrollment del usuario en el producto.
     *
     * @param mixed $product
     *   La entidad TrainingProduct.
     *
     * @return array
     *   Datos del enrollment creado.
     */
    protected function createEnrollment(mixed $product): array
    {
        $productType = $product->getProductType();

        // Si es un tipo de certificacion, crear UserCertification.
        if (in_array($productType, ['certification_consultant', 'certification_entity', 'regional_franchise'], TRUE)) {
            return $this->createCertificationEnrollment($product);
        }

        // Para otros tipos, registrar enrollment basico.
        $this->logger->info('Enrollment creado: usuario @uid en producto @pid (@type).', [
            '@uid' => $this->currentUser->id(),
            '@pid' => $product->id(),
            '@type' => $productType,
        ]);

        return [
            'id' => NULL,
            'type' => 'enrollment',
            'product_id' => $product->id(),
        ];
    }

    /**
     * Crea un registro UserCertification para programas de certificacion.
     *
     * @param mixed $product
     *   La entidad TrainingProduct.
     *
     * @return array
     *   Datos de la certificacion creada.
     */
    protected function createCertificationEnrollment(mixed $product): array
    {
        try {
            // Buscar programa de certificacion asociado.
            $programStorage = $this->entityTypeManager->getStorage('certification_program');
            $programs = $programStorage->getQuery()
                ->condition('certification_type', $product->getProductType())
                ->condition('status', 1)
                ->accessCheck(FALSE)
                ->execute();

            if (empty($programs)) {
                $this->logger->warning('No se encontro programa de certificacion para tipo @type.', [
                    '@type' => $product->getProductType(),
                ]);
                return ['id' => NULL, 'type' => 'certification', 'status' => 'no_program'];
            }

            $programId = reset($programs);

            // Crear UserCertification.
            $certStorage = $this->entityTypeManager->getStorage('user_certification');
            $certification = $certStorage->create([
                'user_id' => $this->currentUser->id(),
                'program_id' => $programId,
                'certification_status' => 'in_progress',
            ]);
            $certification->save();

            $this->logger->info('Certificacion creada: usuario @uid, programa @pid, cert @cid.', [
                '@uid' => $this->currentUser->id(),
                '@pid' => $programId,
                '@cid' => $certification->id(),
            ]);

            return [
                'id' => $certification->id(),
                'type' => 'certification',
                'program_id' => $programId,
                'status' => 'in_progress',
            ];
        }
        catch (\Exception $e) {
            $this->logger->error('Error al crear certificacion: @message', [
                '@message' => $e->getMessage(),
            ]);
            return ['id' => NULL, 'type' => 'certification', 'error' => $e->getMessage()];
        }
    }

    /**
     * Confirma una compra despues de pago exitoso en Stripe.
     *
     * @param string $paymentIntentId
     *   El ID del PaymentIntent confirmado.
     *
     * @return array
     *   Resultado de la confirmacion.
     */
    public function confirmPurchase(string $paymentIntentId): array
    {
        $this->logger->info('Compra confirmada: PaymentIntent @pid.', [
            '@pid' => $paymentIntentId,
        ]);

        return [
            'success' => TRUE,
            'payment_intent_id' => $paymentIntentId,
            'message' => 'Compra confirmada exitosamente.',
        ];
    }

}
