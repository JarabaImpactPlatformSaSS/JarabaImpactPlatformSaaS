<?php

declare(strict_types=1);

namespace Drupal\jaraba_sla\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for SLA Measurement entities (append-only).
 *
 * Structure: Extends EntityAccessControlHandler with strict append-only semantics.
 * Logic: View is allowed with 'view sla dashboard'. Update and delete are always
 *   denied to enforce the append-only audit trail. Only 'administer sla' can create.
 */
class SlaMeasurementAccessControlHandler extends DefaultEntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    // TENANT-ISOLATION-ACCESS-001: Tenant isolation via parent.
    $parentResult = parent::checkAccess($entity, $operation, $account);
    if ($parentResult->isForbidden()) {
      return $parentResult;
    }

    if ($account->hasPermission('administer sla') && $operation === 'view') {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        if ($account->hasPermission('view sla dashboard')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        return AccessResult::neutral();

      case 'update':
      case 'delete':
        // Append-only: deny all updates and deletes.
        return AccessResult::forbidden('SLA measurements are append-only and cannot be modified or deleted.')
          ->addCacheableDependency($entity);
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'administer sla');
  }

}
