<?php

declare(strict_types=1);

namespace Drupal\jaraba_sla\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for SLA Incident entities.
 *
 * Structure: Extends EntityAccessControlHandler with permission-based access.
 * Logic: 'administer sla' = full access, 'manage postmortems' = update (for
 *   postmortem data), 'view sla dashboard' = read-only.
 */
class SlaIncidentAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer sla')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        if ($account->hasPermission('view sla dashboard') || $account->hasPermission('view status page')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        return AccessResult::neutral();

      case 'update':
        if ($account->hasPermission('manage postmortems')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        return AccessResult::neutral();

      case 'delete':
        return AccessResult::neutral();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer sla',
      'manage postmortems',
    ], 'OR');
  }

}
