<?php

declare(strict_types=1);

namespace Drupal\jaraba_workflows;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for WorkflowRule config entities.
 */
class WorkflowRuleAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($account->hasPermission('administer workflow rules')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    if ($operation === 'view' && $account->hasPermission('view workflow rules')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    return AccessResult::neutral()->cachePerPermissions();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'administer workflow rules');
  }

}
