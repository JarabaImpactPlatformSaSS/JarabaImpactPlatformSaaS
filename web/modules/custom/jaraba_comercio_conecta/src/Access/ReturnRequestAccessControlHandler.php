<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

class ReturnRequestAccessControlHandler extends DefaultEntityAccessControlHandler {

  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    // TENANT-ISOLATION-ACCESS-001: Tenant isolation via parent.
    $parentResult = parent::checkAccess($entity, $operation, $account);
    if ($parentResult->isForbidden()) {
      return $parentResult;
    }

    if ($account->hasPermission('manage comercio returns')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
      case 'update':
        $is_owner = (int) $entity->getOwnerId() === (int) $account->id();
        if ($is_owner) {
          return AccessResult::allowed()->addCacheableDependency($entity)->cachePerUser();
        }
        return AccessResult::neutral();

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'manage comercio returns');
    }

    return AccessResult::neutral();
  }

  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'manage comercio returns',
      'create comercio returns',
    ], 'OR');
  }

}
