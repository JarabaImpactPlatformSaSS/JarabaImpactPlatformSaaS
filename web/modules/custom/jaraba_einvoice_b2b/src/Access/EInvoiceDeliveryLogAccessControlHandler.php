<?php

declare(strict_types=1);

namespace Drupal\jaraba_einvoice_b2b\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for EInvoice Delivery Log entities.
 *
 * Append-only entity: update and delete are ALWAYS forbidden for all roles
 * including administrators. This is an audit log requirement.
 *
 * - view: requires 'view einvoice delivery logs' or admin.
 * - update: FORBIDDEN (append-only per Ley 18/2022 traceability requirement).
 * - delete: FORBIDDEN (append-only).
 * - create: programmatic only (admin permission).
 *
 * Spec: Doc 181, Section 2.3.
 */
class EInvoiceDeliveryLogAccessControlHandler extends DefaultEntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    // TENANT-ISOLATION-ACCESS-001: Tenant isolation via parent.
    $parentResult = parent::checkAccess($entity, $operation, $account);
    if ($parentResult->isForbidden()) {
      return $parentResult;
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermissions($account, [
          'administer einvoice b2b',
          'view einvoice delivery logs',
        ], 'OR')->cachePerPermissions()->addCacheableDependency($entity);

      case 'update':
      case 'delete':
        return AccessResult::forbidden('E-Invoice delivery log entries are immutable (append-only). This is required for Ley 18/2022 traceability compliance.')
          ->addCacheableDependency($entity);
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'administer einvoice b2b')
      ->cachePerPermissions();
  }

}
