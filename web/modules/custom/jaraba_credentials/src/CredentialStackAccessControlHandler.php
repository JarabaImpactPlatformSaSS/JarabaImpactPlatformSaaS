<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler para CredentialStack.
 */
class CredentialStackAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer credentials')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    if ($operation === 'view' && $account->hasPermission('view stack progress')) {
      return AccessResult::allowed()
        ->cachePerPermissions()
        ->addCacheableDependency($entity);
    }

    if ($account->hasPermission('manage credential stacks')) {
      return AccessResult::allowed()
        ->cachePerPermissions()
        ->addCacheableDependency($entity);
    }

    return AccessResult::forbidden()->cachePerPermissions();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer credentials',
      'manage credential stacks',
    ], 'OR');
  }

}
