<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_comercio_conecta\Service\CartService;
use Drupal\jaraba_comercio_conecta\Service\CheckoutService;
use Drupal\jaraba_comercio_conecta\Service\StripePaymentRetailService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CheckoutController extends ControllerBase {

  public function __construct(
    protected CartService $cartService,
    protected CheckoutService $checkoutService,
    protected StripePaymentRetailService $paymentService,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_comercio_conecta.cart'),
      $container->get('jaraba_comercio_conecta.checkout'),
      $container->get('jaraba_comercio_conecta.stripe_payment'),
    );
  }

  public function checkoutPage(Request $request): array {
    $cart = $this->cartService->getOrCreateCart($request->getSession()->getId());
    $items = $this->cartService->getCartItems($cart);

    if (empty($items)) {
      $this->messenger()->addWarning($this->t('Tu carrito esta vacio.'));
      return [
        '#theme' => 'comercio_checkout',
        '#cart' => NULL,
        '#items' => [],
        '#empty' => TRUE,
        '#attached' => [
          'library' => ['jaraba_comercio_conecta/checkout'],
        ],
      ];
    }

    $products = [];
    foreach ($items as $item) {
      $product = $item->get('product_id')->entity;
      $products[] = [
        'item' => $item,
        'product' => $product,
        'quantity' => (int) $item->get('quantity')->value,
        'unit_price' => (float) $item->get('unit_price')->value,
        'total' => (int) $item->get('quantity')->value * (float) $item->get('unit_price')->value,
      ];
    }

    return [
      '#theme' => 'comercio_checkout',
      '#cart' => $cart,
      '#items' => $products,
      '#empty' => FALSE,
      '#subtotal' => (float) $cart->get('subtotal')->value,
      '#discount' => (float) $cart->get('discount_amount')->value,
      '#shipping' => (float) $cart->get('shipping_cost')->value,
      '#total' => (float) $cart->get('total')->value,
      '#attached' => [
        'library' => ['jaraba_comercio_conecta/checkout'],
      ],
      '#cache' => [
        'contexts' => ['user', 'session'],
        'max-age' => 0,
      ],
    ];
  }

  public function processPayment(Request $request): JsonResponse {
    $cart = $this->cartService->getOrCreateCart($request->getSession()->getId());
    $items = $this->cartService->getCartItems($cart);

    if (empty($items)) {
      return new JsonResponse(['error' => $this->t('Carrito vacio.')], 400);
    }

    $data = json_decode($request->getContent(), TRUE) ?? [];

    $checkout_data = [
      'payment_method' => $data['payment_method'] ?? 'stripe',
      'shipping_address' => $data['shipping_address'] ?? [],
      'billing_address' => $data['billing_address'] ?? [],
      'shipping_method' => $data['shipping_method'] ?? 'standard',
      'notes' => $data['notes'] ?? '',
    ];

    $result = $this->checkoutService->processCheckout($cart, $checkout_data);

    if (!$result['success']) {
      return // AUDIT-CONS-N08: Standardized JSON envelope.
        new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => $result['message']]], 400);
    }

    $payment_result = $this->paymentService->createPaymentIntent($result['order']);

    if (!$payment_result['success']) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => $payment_result['message']]], 500);
    }

    return new JsonResponse([
      'data' => [
        'order_number' => $result['order_number'],
        'order_id' => (int) $result['order']->id(),
        'payment_intent_id' => $payment_result['payment_intent_id'],
        'client_secret' => $payment_result['client_secret'],
        'amount' => $payment_result['amount'],
        'currency' => $payment_result['currency'],
      ],
    ]);
  }

  public function confirmationPage(int $order_id): array {
    $order = $this->entityTypeManager()->getStorage('order_retail')->load($order_id);
    if (!$order) {
      throw new AccessDeniedHttpException($this->t('Pedido no encontrado.'));
    }

    $customer_uid = (int) $order->get('customer_uid')->target_id;
    if ($customer_uid !== (int) $this->currentUser()->id() &&
        !$this->currentUser()->hasPermission('manage comercio orders')) {
      throw new AccessDeniedHttpException($this->t('No tienes acceso a este pedido.'));
    }

    return [
      '#theme' => 'comercio_checkout_confirmation',
      '#order' => $order,
      '#order_number' => $order->get('order_number')->value,
      '#total' => (float) $order->get('total')->value,
      '#status' => $order->get('status')->value,
      '#payment_status' => $order->get('payment_status')->value,
      '#cache' => [
        'contexts' => ['user'],
        'tags' => ['order_retail:' . $order->id()],
        'max-age' => 60,
      ],
    ];
  }

  public function webhookStripe(Request $request): JsonResponse {
    $payload = $request->getContent();
    $data = json_decode($payload, TRUE);

    if (!$data || !isset($data['type'])) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Invalid payload']], 400);
    }

    switch ($data['type']) {
      case 'payment_intent.succeeded':
        $payment_intent_id = $data['data']['object']['id'] ?? '';
        if ($payment_intent_id) {
          $this->paymentService->confirmPayment($payment_intent_id);
        }
        break;
    }

    return new JsonResponse(['received' => TRUE]);
  }

}
