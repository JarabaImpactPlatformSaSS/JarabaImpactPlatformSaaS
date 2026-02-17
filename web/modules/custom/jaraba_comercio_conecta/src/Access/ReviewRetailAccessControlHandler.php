<?php

namespace Drupal\jaraba_comercio_conecta\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

class ReviewRetailAccessControlHandler extends EntityAccessControlHandler {

  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('manage comercio reviews')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        $status = $entity->get('status')->value;
        if ($status === 'approved') {
          return AccessResult::allowed()->addCacheableDependency($entity);
        }
        $is_owner = (int) $entity->get('user_id')->target_id === (int) $account->id();
        if ($is_owner) {
          return AccessResult::allowed()->addCacheableDependency($entity)->cachePerUser();
        }
        return AccessResult::neutral()->addCacheableDependency($entity)->cachePerUser();

      case 'update':
        $is_owner = (int) $entity->get('user_id')->target_id === (int) $account->id();
        if ($is_owner) {
          return AccessResult::allowedIf(
            $account->hasPermission('edit own comercio reviews')
          )->addCacheableDependency($entity)->cachePerUser();
        }
        return AccessResult::neutral()->addCacheableDependency($entity)->cachePerUser();

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'manage comercio reviews');
    }

    return AccessResult::neutral();
  }

  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'manage comercio reviews',
      'create comercio reviews',
    ], 'OR');
  }

}
