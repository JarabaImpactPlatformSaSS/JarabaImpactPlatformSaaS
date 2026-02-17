<?php

namespace Drupal\jaraba_comercio_conecta\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

class CheckoutService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
    protected CartService $cartService,
    protected LoggerInterface $logger,
  ) {}

  public function initiateCheckout(object $cart): array {
    $items = $this->cartService->getCartItems($cart);
    if (empty($items)) {
      return ['success' => FALSE, 'message' => t('El carrito esta vacio.')];
    }

    $cart->set('status', 'checkout');
    $cart->save();

    return [
      'success' => TRUE,
      'cart' => $cart,
      'items' => $items,
      'subtotal' => (float) $cart->get('subtotal')->value,
      'discount' => (float) $cart->get('discount_amount')->value,
      'shipping' => (float) $cart->get('shipping_cost')->value,
      'total' => (float) $cart->get('total')->value,
    ];
  }

  public function processCheckout(object $cart, array $checkout_data): array {
    $items = $this->cartService->getCartItems($cart);
    if (empty($items)) {
      return ['success' => FALSE, 'message' => t('El carrito esta vacio.')];
    }

    $stock_check = $this->validateStock($items);
    if (!$stock_check['valid']) {
      return ['success' => FALSE, 'message' => $stock_check['message']];
    }

    $order_storage = $this->entityTypeManager->getStorage('order_retail');
    $order = $order_storage->create([
      'tenant_id' => $cart->get('tenant_id')->target_id,
      'customer_uid' => $this->currentUser->id(),
      'status' => 'pending',
      'subtotal' => $cart->get('subtotal')->value,
      'tax_amount' => $this->calculateTax($cart),
      'shipping_cost' => $cart->get('shipping_cost')->value,
      'discount_amount' => $cart->get('discount_amount')->value,
      'total' => $cart->get('total')->value,
      'payment_method' => $checkout_data['payment_method'] ?? 'stripe',
      'payment_status' => 'pending',
      'shipping_address' => json_encode($checkout_data['shipping_address'] ?? []),
      'billing_address' => json_encode($checkout_data['billing_address'] ?? []),
      'shipping_method' => $checkout_data['shipping_method'] ?? 'standard',
      'notes' => $checkout_data['notes'] ?? '',
    ]);
    $order->save();

    $this->createOrderItems($order, $items);
    $this->createSuborders($order, $items);
    $this->recordCouponRedemption($cart, $order);

    $this->cartService->clearCart($cart);

    $this->logger->info('Pedido @number creado desde carrito @cart', [
      '@number' => $order->get('order_number')->value,
      '@cart' => $cart->id(),
    ]);

    return [
      'success' => TRUE,
      'order' => $order,
      'order_number' => $order->get('order_number')->value,
    ];
  }

  protected function createOrderItems(object $order, array $cart_items): void {
    $item_storage = $this->entityTypeManager->getStorage('order_item_retail');
    $tenant_id = $order->get('tenant_id')->target_id;

    foreach ($cart_items as $cart_item) {
      $product = $cart_item->get('product_id')->entity;
      $qty = (int) $cart_item->get('quantity')->value;
      $price = (float) $cart_item->get('unit_price')->value;

      $item_storage->create([
        'tenant_id' => $tenant_id,
        'order_id' => $order->id(),
        'product_id' => $cart_item->get('product_id')->target_id,
        'variation_id' => $cart_item->get('variation_id')->target_id,
        'quantity' => $qty,
        'unit_price' => $price,
        'total_price' => $qty * $price,
        'product_title' => $product ? $product->get('title')->value : '',
        'product_sku' => $product ? $product->get('sku')->value : '',
      ])->save();
    }
  }

  protected function createSuborders(object $order, array $cart_items): void {
    $merchants = [];
    foreach ($cart_items as $cart_item) {
      $product = $cart_item->get('product_id')->entity;
      if (!$product) {
        continue;
      }
      $merchant_id = $product->get('merchant_id')->target_id;
      if (!$merchant_id) {
        continue;
      }
      if (!isset($merchants[$merchant_id])) {
        $merchants[$merchant_id] = 0.0;
      }
      $qty = (int) $cart_item->get('quantity')->value;
      $price = (float) $cart_item->get('unit_price')->value;
      $merchants[$merchant_id] += $qty * $price;
    }

    $suborder_storage = $this->entityTypeManager->getStorage('suborder_retail');
    $tenant_id = $order->get('tenant_id')->target_id;
    $commission_rate = 10.0;

    foreach ($merchants as $merchant_id => $subtotal) {
      $commission = $subtotal * ($commission_rate / 100);
      $suborder_storage->create([
        'tenant_id' => $tenant_id,
        'order_id' => $order->id(),
        'merchant_id' => $merchant_id,
        'status' => 'pending',
        'subtotal' => $subtotal,
        'commission_rate' => $commission_rate,
        'commission_amount' => $commission,
        'merchant_payout' => $subtotal - $commission,
        'payout_status' => 'pending',
      ])->save();
    }
  }

  protected function recordCouponRedemption(object $cart, object $order): void {
    $coupon_id = $cart->get('coupon_id')->target_id;
    if (!$coupon_id) {
      return;
    }

    $redemption_storage = $this->entityTypeManager->getStorage('coupon_redemption');
    $redemption_storage->create([
      'coupon_id' => $coupon_id,
      'order_id' => $order->id(),
      'uid' => $this->currentUser->id(),
      'discount_applied' => $cart->get('discount_amount')->value,
    ])->save();

    $coupon = $this->entityTypeManager->getStorage('coupon_retail')->load($coupon_id);
    if ($coupon) {
      $uses = (int) $coupon->get('current_uses')->value;
      $coupon->set('current_uses', $uses + 1);
      $coupon->save();
    }
  }

  protected function validateStock(array $cart_items): array {
    foreach ($cart_items as $item) {
      $product = $item->get('product_id')->entity;
      if (!$product) {
        return ['valid' => FALSE, 'message' => t('Un producto del carrito ya no esta disponible.')];
      }
      $status = $product->get('status')->value;
      if ($status !== 'active') {
        return ['valid' => FALSE, 'message' => t('El producto @name no esta disponible.', ['@name' => $product->get('title')->value])];
      }
      $stock = (int) $product->get('stock_quantity')->value;
      $requested = (int) $item->get('quantity')->value;
      if ($stock >= 0 && $requested > $stock) {
        return ['valid' => FALSE, 'message' => t('Stock insuficiente para @name. Disponible: @stock', [
          '@name' => $product->get('title')->value,
          '@stock' => $stock,
        ])];
      }
    }
    return ['valid' => TRUE];
  }

  protected function calculateTax(object $cart): float {
    $subtotal = (float) $cart->get('subtotal')->value;
    $discount = (float) $cart->get('discount_amount')->value;
    $taxable = $subtotal - $discount;
    return round($taxable * 0.21, 2);
  }

}
