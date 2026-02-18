<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_comercio_conecta\Service\CartService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class CartApiController extends ControllerBase {

  public function __construct(
    protected CartService $cartService,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_comercio_conecta.cart'),
    );
  }

  public function getCart(Request $request): JsonResponse {
    $cart = $this->cartService->getOrCreateCart($request->getSession()->getId());
    $items = $this->cartService->getCartItems($cart);

    $data = [
      'id' => (int) $cart->id(),
      'status' => $cart->get('status')->value,
      'subtotal' => (float) $cart->get('subtotal')->value,
      'discount_amount' => (float) $cart->get('discount_amount')->value,
      'shipping_cost' => (float) $cart->get('shipping_cost')->value,
      'total' => (float) $cart->get('total')->value,
      'item_count' => $this->cartService->getCartItemCount($cart),
      'items' => [],
    ];

    foreach ($items as $item) {
      $product = $item->get('product_id')->entity;
      $data['items'][] = [
        'id' => (int) $item->id(),
        'product_id' => (int) $item->get('product_id')->target_id,
        'product_title' => $product ? $product->get('title')->value : '',
        'variation_id' => $item->get('variation_id')->target_id,
        'quantity' => (int) $item->get('quantity')->value,
        'unit_price' => (float) $item->get('unit_price')->value,
        'total' => (int) $item->get('quantity')->value * (float) $item->get('unit_price')->value,
      ];
    }

    return new JsonResponse(['success' => TRUE, 'data' => $data, 'meta' => ['timestamp' => time()]]);
  }

  public function addItem(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE) ?? [];
    $product_id = (int) ($data['product_id'] ?? 0);
    $quantity = max(1, (int) ($data['quantity'] ?? 1));
    $variation_id = isset($data['variation_id']) ? (int) $data['variation_id'] : NULL;

    if (!$product_id) {
      return new JsonResponse(['error' => $this->t('Campo product_id requerido.')], 400);
    }

    $cart = $this->cartService->getOrCreateCart($request->getSession()->getId());
    $item = $this->cartService->addItem($cart, $product_id, $quantity, $variation_id);

    if (!$item) {
      return new JsonResponse(['error' => $this->t('Producto no encontrado.')], 404);
    }

    return new JsonResponse([
      'data' => [
        'item_id' => (int) $item->id(),
        'cart_total' => (float) $cart->get('total')->value,
        'item_count' => $this->cartService->getCartItemCount($cart),
      ],
    ], 201);
  }

  public function removeItem(int $item_id, Request $request): JsonResponse {
    $cart = $this->cartService->getOrCreateCart($request->getSession()->getId());
    $success = $this->cartService->removeItem($cart, $item_id);

    if (!$success) {
      return new JsonResponse(['error' => $this->t('Item no encontrado en el carrito.')], 404);
    }

    return new JsonResponse([
      'data' => [
        'cart_total' => (float) $cart->get('total')->value,
        'item_count' => $this->cartService->getCartItemCount($cart),
      ],
    ]);
  }

  public function updateQuantity(int $item_id, Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE) ?? [];
    $quantity = (int) ($data['quantity'] ?? 0);

    $cart = $this->cartService->getOrCreateCart($request->getSession()->getId());
    $success = $this->cartService->updateItemQuantity($cart, $item_id, $quantity);

    if (!$success) {
      return new JsonResponse(['error' => $this->t('Item no encontrado en el carrito.')], 404);
    }

    return new JsonResponse([
      'data' => [
        'cart_total' => (float) $cart->get('total')->value,
        'item_count' => $this->cartService->getCartItemCount($cart),
      ],
    ]);
  }

  public function applyCoupon(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE) ?? [];
    $coupon_code = trim($data['coupon_code'] ?? '');

    if (!$coupon_code) {
      return new JsonResponse(['error' => $this->t('Campo coupon_code requerido.')], 400);
    }

    $cart = $this->cartService->getOrCreateCart($request->getSession()->getId());
    $result = $this->cartService->applyCoupon($cart, $coupon_code);

    if (!$result['success']) {
      return // AUDIT-CONS-N08: Standardized JSON envelope.
        new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => $result['message']]], 400);
    }

    return new JsonResponse([
      'data' => [
        'discount' => $result['discount'],
        'cart_total' => (float) $cart->get('total')->value,
        'message' => $result['message'],
      ],
    ]);
  }

}
