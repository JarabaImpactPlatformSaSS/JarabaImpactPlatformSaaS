<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Generic default access control handler for entities without explicit handler.
 *
 * AUDIT-CONS-001: Applied automatically to all Content Entities that lack
 * an explicit "access" handler via hook_entity_type_alter().
 *
 * NOTE: This is NOT the access handler for the Tenant entity itself.
 * It is a fallback handler for all custom entities that don't define one.
 * Renamed from TenantAccessControlHandler to avoid confusion.
 *
 * Access logic:
 * - view: Allowed if user has admin_permission, or 'access content'.
 * - update/delete: Allowed only with admin_permission.
 * - create: Allowed only with admin_permission.
 *
 * For entities with a tenant_id field, results are cacheable per tenant.
 *
 * @see ecosistema_jaraba_core_entity_type_alter()
 */
class DefaultEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $adminPermission = $this->entityType->getAdminPermission();

    // Admin bypass.
    if ($adminPermission) {
      $adminResult = AccessResult::allowedIfHasPermission($account, $adminPermission);
      if ($adminResult->isAllowed()) {
        return $adminResult;
      }
    }

    return match ($operation) {
      'view' => AccessResult::allowedIfHasPermission($account, 'access content'),
      'update', 'delete' => $adminPermission
        ? AccessResult::allowedIfHasPermission($account, $adminPermission)
        : AccessResult::allowedIfHasPermission($account, 'administer site configuration'),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $adminPermission = $this->entityType->getAdminPermission();

    if ($adminPermission) {
      return AccessResult::allowedIfHasPermission($account, $adminPermission);
    }

    return AccessResult::allowedIfHasPermission($account, 'administer site configuration');
  }

}
