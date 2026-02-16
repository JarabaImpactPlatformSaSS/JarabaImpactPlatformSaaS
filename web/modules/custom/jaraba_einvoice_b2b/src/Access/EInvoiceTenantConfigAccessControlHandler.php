<?php

declare(strict_types=1);

namespace Drupal\jaraba_einvoice_b2b\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for EInvoice Tenant Config entities.
 *
 * - view: requires 'manage einvoice config' or admin.
 * - update: requires 'manage einvoice config' or admin.
 * - delete: admin only (tenant configs should not be casually removed).
 *
 * Spec: Doc 181, Section 2.2.
 */
class EInvoiceTenantConfigAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer einvoice b2b')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'manage einvoice config')
          ->cachePerPermissions()
          ->addCacheableDependency($entity);

      case 'delete':
        return AccessResult::forbidden('Tenant configurations can only be deleted by administrators.')
          ->addCacheableDependency($entity);
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer einvoice b2b',
      'manage einvoice config',
    ], 'OR')->cachePerPermissions();
  }

}
