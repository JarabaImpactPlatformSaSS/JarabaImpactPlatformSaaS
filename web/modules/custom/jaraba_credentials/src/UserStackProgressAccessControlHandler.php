<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler para UserStackProgress.
 */
class UserStackProgressAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer credentials')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    if ($operation === 'view') {
      $userId = $entity->get('user_id')->target_id ?? NULL;
      if ($userId && (int) $userId === (int) $account->id()) {
        return AccessResult::allowed()
          ->cachePerUser()
          ->addCacheableDependency($entity);
      }

      if ($account->hasPermission('view stack progress')) {
        return AccessResult::allowed()
          ->cachePerPermissions()
          ->addCacheableDependency($entity);
      }
    }

    return AccessResult::forbidden()->cachePerPermissions();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'administer credentials');
  }

}
