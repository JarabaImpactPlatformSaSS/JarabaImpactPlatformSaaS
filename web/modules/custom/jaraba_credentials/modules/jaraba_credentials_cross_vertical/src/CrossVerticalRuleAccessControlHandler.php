<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials_cross_vertical;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler para CrossVerticalRule.
 */
class CrossVerticalRuleAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer credentials')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    if ($account->hasPermission('manage cross vertical credentials')) {
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
      'manage cross vertical credentials',
    ], 'OR');
  }

}
