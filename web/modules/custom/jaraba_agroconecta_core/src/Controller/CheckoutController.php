<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\jaraba_agroconecta_core\Service\OrderService;
use Drupal\jaraba_agroconecta_core\Service\StripePaymentService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador del flujo de checkout de AgroConecta.
 *
 * PROPÓSITO:
 * Renderiza la página de checkout, procesa la creación de pedidos
 * y gestiona la confirmación post-pago con Stripe Payment Element.
 *
 * RUTAS:
 * - GET /checkout → Página de checkout (render Twig)
 * - POST /checkout/process → Crea pedido + PaymentIntent (AJAX)
 * - POST /checkout/confirm → Confirma pedido tras pago (AJAX)
 * - GET /pedido/{order_number}/confirmacion → Página de éxito
 */
class CheckoutController extends ControllerBase implements ContainerInjectionInterface
{

    /**
     * Constructor del controlador.
     */
    public function __construct(
        protected OrderService $orderService,
        protected StripePaymentService $stripePaymentService,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_agroconecta.order_service'),
            $container->get('jaraba_agroconecta.stripe_payment_service'),
        );
    }

    /**
     * Renderiza la página de checkout.
     *
     * @return array
     *   Render array con el template de checkout.
     */
    public function checkout(): array
    {
        $config = $this->config('jaraba_agroconecta_core.settings');

        return [
            '#theme' => 'agro_checkout',
            '#marketplace_name' => $config->get('marketplace_name') ?? 'AgroConecta',
            '#attached' => [
                'library' => ['jaraba_agroconecta_core/agroconecta.checkout'],
                'drupalSettings' => [
                    'agroconecta' => [
                        'checkoutProcessUrl' => '/checkout/process',
                        'checkoutConfirmUrl' => '/checkout/confirm',
                    ],
                ],
            ],
        ];
    }

    /**
     * Procesa la creación del pedido y genera PaymentIntent.
     *
     * Endpoint AJAX: POST /checkout/process
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP con datos del carrito y cliente.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con client_secret del PaymentIntent.
     */
    public function processCheckout(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['items']) || empty($data['customer'])) {
            return new JsonResponse([
                'error' => 'Datos del carrito o cliente incompletos.',
            ], 400);
        }

        // Crear pedido.
        $order = $this->orderService->createOrderFromCart(
            $data['items'],
            $data['customer']
        );

        if (!$order) {
            return new JsonResponse([
                'error' => 'Error al crear el pedido. Inténtelo de nuevo.',
            ], 500);
        }

        // Crear PaymentIntent.
        $paymentIntent = $this->stripePaymentService->createPaymentIntent(
            (int) $order->id()
        );

        if (!$paymentIntent) {
            return new JsonResponse([
                'error' => 'Error al inicializar el pago. Inténtelo de nuevo.',
            ], 500);
        }

        return new JsonResponse([
            'order_number' => $order->get('order_number')->value,
            'client_secret' => $paymentIntent['client_secret'],
            'amount' => $paymentIntent['amount'],
            'currency' => $paymentIntent['currency'],
        ]);
    }

    /**
     * Confirma el pedido tras pago exitoso.
     *
     * Endpoint AJAX: POST /checkout/confirm
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP con payment_intent_id.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con URL de confirmación.
     */
    public function confirmOrder(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);
        $paymentIntentId = $data['payment_intent_id'] ?? '';

        if (empty($paymentIntentId)) {
            return new JsonResponse([
                'error' => 'ID de pago no proporcionado.',
            ], 400);
        }

        $success = $this->stripePaymentService->handlePaymentConfirmation($paymentIntentId);

        if (!$success) {
            return new JsonResponse([
                'error' => 'Error al confirmar el pedido.',
            ], 500);
        }

        // Buscar el order_number para redirigir.
        $orderStorage = $this->entityTypeManager()->getStorage('order_agro');
        $orderIds = $orderStorage->getQuery()
            ->accessCheck(TRUE)
            ->condition('stripe_payment_intent', $paymentIntentId)
            ->execute();

        $orderNumber = '';
        if (!empty($orderIds)) {
            $order = $orderStorage->load(reset($orderIds));
            $orderNumber = $order ? $order->get('order_number')->value : '';
        }

        return new JsonResponse([
            'success' => TRUE,
            'redirect_url' => '/pedido/' . $orderNumber . '/confirmacion',
            'order_number' => $orderNumber,
        ]);
    }

    /**
     * Renderiza la página de confirmación de pedido.
     *
     * @param string $order_number
     *   Número del pedido confirmado.
     *
     * @return array
     *   Render array con el template de confirmación.
     */
    public function orderConfirmation(string $order_number): array
    {
        $orderStorage = $this->entityTypeManager()->getStorage('order_agro');
        $orderIds = $orderStorage->getQuery()
            ->accessCheck(TRUE)
            ->condition('order_number', $order_number)
            ->execute();

        $order = NULL;
        $orderData = [];

        if (!empty($orderIds)) {
            $order = $orderStorage->load(reset($orderIds));
            if ($order) {
                $orderData = $this->orderService->serializeOrder($order);
            }
        }

        return [
            '#theme' => 'agro_order_confirmation',
            '#order' => $orderData,
            '#order_number' => $order_number,
            '#attached' => [
                'library' => ['jaraba_agroconecta_core/agroconecta.frontend'],
            ],
        ];
    }

}
