<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

class NotificationPreferenceAccessControlHandler extends EntityAccessControlHandler {

  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('manage comercio notifications')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
      case 'update':
        $is_owner = (int) $entity->get('user_id')->target_id === (int) $account->id();
        if ($is_owner) {
          return AccessResult::allowed()->addCacheableDependency($entity)->cachePerUser();
        }
        return AccessResult::neutral()->addCacheableDependency($entity)->cachePerUser();

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'manage comercio notifications');
    }

    return AccessResult::neutral();
  }

  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'manage comercio notifications',
      'manage own notification preferences',
    ], 'OR');
  }

}
