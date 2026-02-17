<?php

namespace Drupal\jaraba_comercio_conecta\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;

class WishlistService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
  ) {}

  public function getOrCreateDefaultWishlist(int $userId): ContentEntityInterface {
    $storage = $this->entityTypeManager->getStorage('wishlist_retail');

    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('uid', $userId)
      ->condition('is_default', 1)
      ->range(0, 1)
      ->execute();

    if ($ids) {
      return $storage->load(reset($ids));
    }

    $wishlist = $storage->create([
      'uid' => $userId,
      'title' => 'Mi lista de deseos',
      'is_default' => 1,
      'status' => 1,
    ]);
    $wishlist->save();

    return $wishlist;
  }

  public function getUserWishlists(int $userId): array {
    $storage = $this->entityTypeManager->getStorage('wishlist_retail');

    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('uid', $userId)
      ->sort('is_default', 'DESC')
      ->sort('created', 'DESC')
      ->execute();

    return $ids ? array_values($storage->loadMultiple($ids)) : [];
  }

  public function addItem(int $wishlistId, int $productId, string $note = ''): bool {
    $wishlistStorage = $this->entityTypeManager->getStorage('wishlist_retail');
    $wishlist = $wishlistStorage->load($wishlistId);
    if (!$wishlist) {
      return FALSE;
    }

    $itemStorage = $this->entityTypeManager->getStorage('wishlist_item_retail');

    $existing = $itemStorage->getQuery()
      ->accessCheck(TRUE)
      ->condition('wishlist_id', $wishlistId)
      ->condition('product_id', $productId)
      ->count()
      ->execute();

    if ((int) $existing > 0) {
      return FALSE;
    }

    try {
      $item = $itemStorage->create([
        'wishlist_id' => $wishlistId,
        'product_id' => $productId,
        'uid' => $wishlist->get('uid')->target_id,
        'note' => $note,
      ]);
      $item->save();

      return TRUE;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  public function removeItem(int $wishlistId, int $productId): bool {
    $itemStorage = $this->entityTypeManager->getStorage('wishlist_item_retail');

    $ids = $itemStorage->getQuery()
      ->accessCheck(TRUE)
      ->condition('wishlist_id', $wishlistId)
      ->condition('product_id', $productId)
      ->execute();

    if (!$ids) {
      return FALSE;
    }

    try {
      $items = $itemStorage->loadMultiple($ids);
      $itemStorage->delete($items);

      return TRUE;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  public function getItems(int $wishlistId): array {
    $itemStorage = $this->entityTypeManager->getStorage('wishlist_item_retail');

    $ids = $itemStorage->getQuery()
      ->accessCheck(TRUE)
      ->condition('wishlist_id', $wishlistId)
      ->sort('created', 'DESC')
      ->execute();

    if (!$ids) {
      return [];
    }

    $items = $itemStorage->loadMultiple($ids);
    $productStorage = $this->entityTypeManager->getStorage('product_retail');
    $result = [];

    foreach ($items as $item) {
      $productId = (int) $item->get('product_id')->target_id;
      $product = $productStorage->load($productId);

      $result[] = [
        'item' => $item,
        'product' => $product,
        'note' => $item->get('note')->value ?? '',
      ];
    }

    return $result;
  }

  public function isInWishlist(int $userId, int $productId): bool {
    $wishlistStorage = $this->entityTypeManager->getStorage('wishlist_retail');

    $wishlistIds = $wishlistStorage->getQuery()
      ->accessCheck(TRUE)
      ->condition('uid', $userId)
      ->execute();

    if (!$wishlistIds) {
      return FALSE;
    }

    $itemStorage = $this->entityTypeManager->getStorage('wishlist_item_retail');

    $count = (int) $itemStorage->getQuery()
      ->accessCheck(TRUE)
      ->condition('wishlist_id', $wishlistIds, 'IN')
      ->condition('product_id', $productId)
      ->count()
      ->execute();

    return $count > 0;
  }

  public function getItemCount(int $wishlistId): int {
    $itemStorage = $this->entityTypeManager->getStorage('wishlist_item_retail');

    return (int) $itemStorage->getQuery()
      ->accessCheck(TRUE)
      ->condition('wishlist_id', $wishlistId)
      ->count()
      ->execute();
  }

  public function moveItem(int $fromWishlistId, int $toWishlistId, int $productId): bool {
    $itemStorage = $this->entityTypeManager->getStorage('wishlist_item_retail');

    $ids = $itemStorage->getQuery()
      ->accessCheck(TRUE)
      ->condition('wishlist_id', $fromWishlistId)
      ->condition('product_id', $productId)
      ->range(0, 1)
      ->execute();

    if (!$ids) {
      return FALSE;
    }

    $existsInTarget = (int) $itemStorage->getQuery()
      ->accessCheck(TRUE)
      ->condition('wishlist_id', $toWishlistId)
      ->condition('product_id', $productId)
      ->count()
      ->execute();

    if ($existsInTarget > 0) {
      return FALSE;
    }

    try {
      $item = $itemStorage->load(reset($ids));
      $item->set('wishlist_id', $toWishlistId);
      $item->save();

      return TRUE;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

}
