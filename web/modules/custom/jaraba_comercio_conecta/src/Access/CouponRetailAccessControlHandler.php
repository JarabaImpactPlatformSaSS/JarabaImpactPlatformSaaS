<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 *
 */
class CouponRetailAccessControlHandler extends DefaultEntityAccessControlHandler {

  /**
   *
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    // TENANT-ISOLATION-ACCESS-001: Tenant isolation via parent.
    $parentResult = parent::checkAccess($entity, $operation, $account);
    if ($parentResult->isForbidden()) {
      return $parentResult;
    }

    if ($account->hasPermission('manage comercio coupons')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view comercio coupons');

      case 'update':
        $is_owner = (int) $entity->getOwnerId() === (int) $account->id();
        return AccessResult::allowedIf(
          $is_owner && $account->hasPermission('edit own comercio coupons')
        )->addCacheableDependency($entity)->cachePerUser();

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'manage comercio coupons');
    }

    return AccessResult::neutral();
  }

  /**
   *
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'manage comercio coupons',
      'create comercio coupons',
    ], 'OR');
  }

}
