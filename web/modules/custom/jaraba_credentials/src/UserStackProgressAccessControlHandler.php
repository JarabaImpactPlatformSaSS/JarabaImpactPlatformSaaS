<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials;

use Drupal\Core\Access\AccessResult;
use Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler para UserStackProgress.
 */
class UserStackProgressAccessControlHandler extends DefaultEntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    // TENANT-ISOLATION-ACCESS-001: Tenant isolation via parent.
    $parentResult = parent::checkAccess($entity, $operation, $account);
    if ($parentResult->isForbidden()) {
      return $parentResult;
    }

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
