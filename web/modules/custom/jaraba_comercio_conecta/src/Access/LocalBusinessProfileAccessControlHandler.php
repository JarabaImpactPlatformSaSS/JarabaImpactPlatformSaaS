<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

class LocalBusinessProfileAccessControlHandler extends DefaultEntityAccessControlHandler {

  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    // TENANT-ISOLATION-ACCESS-001: Tenant isolation via parent.
    $parentResult = parent::checkAccess($entity, $operation, $account);
    if ($parentResult->isForbidden()) {
      return $parentResult;
    }

    if ($account->hasPermission('administer comercio local seo')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return $this->checkMerchantOwnership($entity, $account, 'view own local business profile');

      case 'update':
        return $this->checkMerchantOwnership($entity, $account, 'edit own local business profile');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'administer comercio local seo')
          ->cachePerPermissions();
    }

    return AccessResult::neutral();
  }

  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer comercio local seo',
      'create local business profile',
    ], 'OR');
  }

  protected function checkMerchantOwnership(EntityInterface $entity, AccountInterface $account, string $permission): AccessResult {
    $merchant_id = $entity->get('merchant_id')->target_id;
    if (!$merchant_id) {
      return AccessResult::neutral()->addCacheableDependency($entity);
    }

    $merchant = \Drupal::entityTypeManager()
      ->getStorage('merchant_profile')
      ->load($merchant_id);

    if (!$merchant) {
      return AccessResult::neutral()->addCacheableDependency($entity);
    }

    $is_owner = (int) $merchant->getOwnerId() === (int) $account->id();
    return AccessResult::allowedIf(
      $is_owner && $account->hasPermission($permission)
    )->addCacheableDependency($entity)->addCacheableDependency($merchant)->cachePerUser();
  }

}
