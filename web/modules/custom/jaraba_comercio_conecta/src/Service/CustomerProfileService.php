<?php

namespace Drupal\jaraba_comercio_conecta\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountInterface;

class CustomerProfileService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected CurrentRouteMatch $currentRouteMatch,
  ) {}

  public function getOrCreateProfile(AccountInterface $account): ?ContentEntityInterface {
    $storage = $this->entityTypeManager->getStorage('customer_profile_retail');

    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('uid', $account->id())
      ->range(0, 1)
      ->execute();

    if ($ids) {
      return $storage->load(reset($ids));
    }

    try {
      $profile = $storage->create([
        'uid' => $account->id(),
        'status' => 1,
        'favorite_merchants' => json_encode([]),
        'loyalty_points' => 0,
      ]);
      $profile->save();

      return $profile;
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  public function updateProfile(int $profileId, array $data): bool {
    $storage = $this->entityTypeManager->getStorage('customer_profile_retail');
    $profile = $storage->load($profileId);
    if (!$profile) {
      return FALSE;
    }

    try {
      foreach ($data as $field => $value) {
        if ($profile->hasField($field)) {
          $profile->set($field, $value);
        }
      }
      $profile->save();

      return TRUE;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  public function getProfile(int $userId): ?ContentEntityInterface {
    $storage = $this->entityTypeManager->getStorage('customer_profile_retail');

    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('uid', $userId)
      ->range(0, 1)
      ->execute();

    if (!$ids) {
      return NULL;
    }

    return $storage->load(reset($ids));
  }

  public function addFavoriteMerchant(int $profileId, int $merchantId): bool {
    $storage = $this->entityTypeManager->getStorage('customer_profile_retail');
    $profile = $storage->load($profileId);
    if (!$profile) {
      return FALSE;
    }

    try {
      $favorites = $this->getFavoriteMerchants($profileId);

      if (in_array($merchantId, $favorites)) {
        return TRUE;
      }

      $favorites[] = $merchantId;
      $profile->set('favorite_merchants', json_encode(array_values($favorites)));
      $profile->save();

      return TRUE;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  public function removeFavoriteMerchant(int $profileId, int $merchantId): bool {
    $storage = $this->entityTypeManager->getStorage('customer_profile_retail');
    $profile = $storage->load($profileId);
    if (!$profile) {
      return FALSE;
    }

    try {
      $favorites = $this->getFavoriteMerchants($profileId);
      $favorites = array_filter($favorites, fn($id) => $id !== $merchantId);
      $profile->set('favorite_merchants', json_encode(array_values($favorites)));
      $profile->save();

      return TRUE;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  public function getFavoriteMerchants(int $profileId): array {
    $storage = $this->entityTypeManager->getStorage('customer_profile_retail');
    $profile = $storage->load($profileId);
    if (!$profile) {
      return [];
    }

    $raw = $profile->get('favorite_merchants')->value ?? '[]';
    $decoded = json_decode($raw, TRUE);

    return is_array($decoded) ? array_map('intval', $decoded) : [];
  }

  public function addLoyaltyPoints(int $profileId, int $points): int {
    $storage = $this->entityTypeManager->getStorage('customer_profile_retail');
    $profile = $storage->load($profileId);
    if (!$profile) {
      return 0;
    }

    $current = (int) ($profile->get('loyalty_points')->value ?? 0);
    $new_total = $current + $points;
    $profile->set('loyalty_points', $new_total);
    $profile->save();

    return $new_total;
  }

  public function getProfileStats(int $profileId): array {
    $storage = $this->entityTypeManager->getStorage('customer_profile_retail');
    $profile = $storage->load($profileId);
    if (!$profile) {
      return [
        'order_count' => 0,
        'total_spent' => 0.0,
        'wishlist_count' => 0,
        'loyalty_points' => 0,
      ];
    }

    $userId = (int) $profile->get('uid')->target_id;
    $loyaltyPoints = (int) ($profile->get('loyalty_points')->value ?? 0);

    try {
      $orderStorage = $this->entityTypeManager->getStorage('order_retail');

      $orderCount = (int) $orderStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('customer_uid', $userId)
        ->count()
        ->execute();

      $revenueResult = $orderStorage->getAggregateQuery()
        ->accessCheck(FALSE)
        ->aggregate('total', 'SUM')
        ->condition('customer_uid', $userId)
        ->condition('payment_status', 'paid')
        ->execute();
      $totalSpent = (float) ($revenueResult[0]['total_sum'] ?? 0);

      $wishlistStorage = $this->entityTypeManager->getStorage('wishlist_item_retail');
      $wishlistCount = (int) $wishlistStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('uid', $userId)
        ->count()
        ->execute();
    }
    catch (\Exception $e) {
      return [
        'order_count' => 0,
        'total_spent' => 0.0,
        'wishlist_count' => 0,
        'loyalty_points' => $loyaltyPoints,
      ];
    }

    return [
      'order_count' => $orderCount,
      'total_spent' => $totalSpent,
      'wishlist_count' => $wishlistCount,
      'loyalty_points' => $loyaltyPoints,
    ];
  }

}
