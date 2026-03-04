<?php

declare(strict_types=1);

namespace Drupal\jaraba_sla\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for SLA Agreement entities.
 *
 * Structure: Extends EntityAccessControlHandler with permission-based access.
 * Logic: 'administer sla' = full access, 'manage sla agreements' = CRUD,
 *   'view sla dashboard' = read-only.
 */
class SlaAgreementAccessControlHandler extends DefaultEntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    // TENANT-ISOLATION-ACCESS-001: Tenant isolation via parent.
    $parentResult = parent::checkAccess($entity, $operation, $account);
    if ($parentResult->isForbidden()) {
      return $parentResult;
    }

    if ($account->hasPermission('administer sla')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        if ($account->hasPermission('manage sla agreements') || $account->hasPermission('view sla dashboard')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        return AccessResult::neutral();

      case 'update':
      case 'delete':
        if ($account->hasPermission('manage sla agreements')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
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
      'manage sla agreements',
    ], 'OR');
  }

}
