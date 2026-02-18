<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

class CartService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
    protected LoggerInterface $logger,
  ) {}

  public function getOrCreateCart(?string $session_id = NULL, ?int $tenant_id = NULL): object {
    $uid = (int) $this->currentUser->id();
    $storage = $this->entityTypeManager->getStorage('comercio_cart');

    if ($uid > 0) {
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('uid', $uid)
        ->condition('status', 'active')
        ->sort('changed', 'DESC')
        ->range(0, 1)
        ->execute();

      if ($ids) {
        return $storage->load(reset($ids));
      }
    }
    elseif ($session_id) {
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('session_id', $session_id)
        ->condition('status', 'active')
        ->sort('changed', 'DESC')
        ->range(0, 1)
        ->execute();

      if ($ids) {
        return $storage->load(reset($ids));
      }
    }

    $cart = $storage->create([
      'uid' => $uid > 0 ? $uid : 0,
      'session_id' => $session_id,
      'tenant_id' => $tenant_id ?? $this->getTenantId(),
      'status' => 'active',
    ]);
    $cart->save();

    return $cart;
  }

  public function addItem(object $cart, int $product_id, int $quantity = 1, ?int $variation_id = NULL): ?object {
    $item_storage = $this->entityTypeManager->getStorage('comercio_cart_item');

    $existing = $item_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('cart_id', $cart->id())
      ->condition('product_id', $product_id)
      ->condition('variation_id', $variation_id ?? 0)
      ->range(0, 1)
      ->execute();

    if ($existing) {
      $item = $item_storage->load(reset($existing));
      $current_qty = (int) $item->get('quantity')->value;
      $item->set('quantity', $current_qty + $quantity);
      $item->save();
    }
    else {
      $product = $this->entityTypeManager->getStorage('product_retail')->load($product_id);
      if (!$product) {
        return NULL;
      }

      $unit_price = (float) $product->get('price')->value;

      $item = $item_storage->create([
        'cart_id' => $cart->id(),
        'product_id' => $product_id,
        'variation_id' => $variation_id,
        'quantity' => $quantity,
        'unit_price' => $unit_price,
      ]);
      $item->save();
    }

    $this->recalculateCart($cart);
    return $item;
  }

  public function removeItem(object $cart, int $item_id): bool {
    $item_storage = $this->entityTypeManager->getStorage('comercio_cart_item');
    $item = $item_storage->load($item_id);
    if (!$item || (int) $item->get('cart_id')->target_id !== (int) $cart->id()) {
      return FALSE;
    }

    $item->delete();
    $this->recalculateCart($cart);
    return TRUE;
  }

  public function updateItemQuantity(object $cart, int $item_id, int $quantity): bool {
    if ($quantity <= 0) {
      return $this->removeItem($cart, $item_id);
    }

    $item_storage = $this->entityTypeManager->getStorage('comercio_cart_item');
    $item = $item_storage->load($item_id);
    if (!$item || (int) $item->get('cart_id')->target_id !== (int) $cart->id()) {
      return FALSE;
    }

    $item->set('quantity', $quantity);
    $item->save();
    $this->recalculateCart($cart);
    return TRUE;
  }

  public function getCartItems(object $cart): array {
    $item_storage = $this->entityTypeManager->getStorage('comercio_cart_item');
    $ids = $item_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('cart_id', $cart->id())
      ->sort('created', 'ASC')
      ->execute();

    return $ids ? array_values($item_storage->loadMultiple($ids)) : [];
  }

  public function getCartItemCount(object $cart): int {
    $items = $this->getCartItems($cart);
    $count = 0;
    foreach ($items as $item) {
      $count += (int) $item->get('quantity')->value;
    }
    return $count;
  }

  public function recalculateCart(object $cart): void {
    $items = $this->getCartItems($cart);
    $subtotal = 0.0;

    foreach ($items as $item) {
      $qty = (int) $item->get('quantity')->value;
      $price = (float) $item->get('unit_price')->value;
      $subtotal += $qty * $price;
    }

    $discount = (float) $cart->get('discount_amount')->value;
    $shipping = (float) $cart->get('shipping_cost')->value;
    $total = $subtotal - $discount + $shipping;

    $cart->set('subtotal', $subtotal);
    $cart->set('total', max(0, $total));
    $cart->save();
  }

  public function applyCoupon(object $cart, string $coupon_code): array {
    $coupon_storage = $this->entityTypeManager->getStorage('coupon_retail');
    $ids = $coupon_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('code', $coupon_code)
      ->condition('status', 'active')
      ->range(0, 1)
      ->execute();

    if (!$ids) {
      return ['success' => FALSE, 'message' => t('Cupon no valido o expirado.')];
    }

    $coupon = $coupon_storage->load(reset($ids));

    $max_uses = (int) $coupon->get('max_uses')->value;
    $current_uses = (int) $coupon->get('current_uses')->value;
    if ($max_uses > 0 && $current_uses >= $max_uses) {
      return ['success' => FALSE, 'message' => t('Este cupon ha alcanzado su limite de usos.')];
    }

    $min_amount = (float) $coupon->get('min_order_amount')->value;
    $subtotal = (float) $cart->get('subtotal')->value;
    if ($min_amount > 0 && $subtotal < $min_amount) {
      return ['success' => FALSE, 'message' => t('Pedido minimo de @amount EUR.', ['@amount' => number_format($min_amount, 2, ',', '.')])];
    }

    $discount_type = $coupon->get('discount_type')->value;
    $discount_value = (float) $coupon->get('discount_value')->value;

    $discount = match ($discount_type) {
      'percentage' => $subtotal * ($discount_value / 100),
      'fixed_amount' => $discount_value,
      'free_shipping' => (float) $cart->get('shipping_cost')->value,
      default => 0,
    };

    $cart->set('coupon_id', $coupon->id());
    $cart->set('discount_amount', $discount);
    if ($discount_type === 'free_shipping') {
      $cart->set('shipping_cost', 0);
    }
    $this->recalculateCart($cart);

    return ['success' => TRUE, 'message' => t('Cupon aplicado correctamente.'), 'discount' => $discount];
  }

  public function clearCart(object $cart): void {
    $items = $this->getCartItems($cart);
    foreach ($items as $item) {
      $item->delete();
    }
    $cart->set('subtotal', 0);
    $cart->set('discount_amount', 0);
    $cart->set('shipping_cost', 0);
    $cart->set('total', 0);
    $cart->set('coupon_id', NULL);
    $cart->set('status', 'completed');
    $cart->save();
  }

  protected function getTenantId(): int {
    if (\Drupal::hasService('ecosistema_jaraba_core.tenant_context')) {
      $tenant_context = \Drupal::service('ecosistema_jaraba_core.tenant_context');
      $tenant = $tenant_context->getCurrentTenant();
      if ($tenant) {
        return (int) $tenant->id();
      }
    }
    return 1;
  }

}
