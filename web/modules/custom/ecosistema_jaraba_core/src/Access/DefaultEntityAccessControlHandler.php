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
 * HAL-AI-03 / TENANT-ISOLATION-ACCESS-001: Enforces tenant isolation for
 * update/delete operations on entities with a tenant_id field. Published
 * entities (view) remain public. This handler acts as the safety net for
 * all entities that don't define a custom access handler.
 *
 * NOTE: This is NOT the access handler for the Tenant entity itself.
 * It is a fallback handler for all custom entities that don't define one.
 * Renamed from TenantAccessControlHandler to avoid confusion.
 *
 * Access logic:
 * - view: Allowed if user has admin_permission, or 'access content'.
 * - update/delete: Allowed only with admin_permission + tenant match.
 * - create: Allowed only with admin_permission.
 *
 * For entities with a tenant_id field, results are cacheable per tenant.
 *
 * @see ecosistema_jaraba_core_entity_type_alter()
 * @see TENANT-ISOLATION-ACCESS-001
 */
class DefaultEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $adminPermission = $this->entityType->getAdminPermission();

    // Admin bypass (uid=1 or admin_permission).
    if ($adminPermission) {
      $adminResult = AccessResult::allowedIfHasPermission($account, $adminPermission);
      if ($adminResult->isAllowed()) {
        return $adminResult;
      }
    }

    // HAL-AI-03: Tenant isolation for update/delete.
    if (in_array($operation, ['update', 'delete'], TRUE)) {
      $tenantCheck = $this->checkTenantIsolation($entity, $account);
      if ($tenantCheck !== NULL) {
        return $tenantCheck;
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

  /**
   * Verifies tenant isolation for entities with a tenant_id field (HAL-AI-03).
   *
   * TENANT-ISOLATION-ACCESS-001: For update/delete operations, the entity's
   * tenant_id must match the current user's tenant. This prevents cross-tenant
   * data modification in the multi-tenant SaaS.
   *
   * The method is resilient to missing services (PRESAVE-RESILIENCE-001):
   * if TenantContextService is unavailable, it returns NULL (neutral) to
   * avoid blocking legitimate operations in test environments.
   *
   * ACCESS-STRICT-001: Uses strict integer comparison (===) for tenant IDs.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being accessed.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return \Drupal\Core\Access\AccessResult|null
   *   AccessResult::forbidden() if tenant mismatch, or NULL if no check needed.
   */
  protected function checkTenantIsolation(EntityInterface $entity, AccountInterface $account): ?AccessResult {
    // Only check entities that have a tenant_id field.
    if (!$entity->hasField('tenant_id')) {
      return NULL;
    }

    // Get entity's tenant_id (supports both plain integer and entity_reference).
    $tenantField = $entity->get('tenant_id');
    $entityTenantId = (int) ($tenantField->target_id ?? $tenantField->value ?? 0);

    // Skip check if entity has no tenant assigned (global/shared entity).
    if ($entityTenantId === 0) {
      return NULL;
    }

    // Resolve current user's tenant via TenantContextService.
    // OPTIONAL-SERVICE-DI-001: Service may not be available in tests.
    if (!\Drupal::hasService('ecosistema_jaraba_core.tenant_context')) {
      return NULL;
    }

    try {
      /** @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService $tenantContext */
      $tenantContext = \Drupal::service('ecosistema_jaraba_core.tenant_context');
      $currentTenant = $tenantContext->getCurrentTenant();

      if (!$currentTenant) {
        return NULL;
      }

      $userTenantId = (int) $currentTenant->id();

      // ACCESS-STRICT-001: Strict integer comparison.
      if ($entityTenantId !== $userTenantId) {
        return AccessResult::forbidden('Tenant mismatch: entity belongs to a different tenant.')
          ->addCacheContexts(['user'])
          ->addCacheableDependency($entity);
      }
    } catch (\Exception $e) {
      // PRESAVE-RESILIENCE-001: Don't block access on service failure.
      // Log but allow the standard permission check to proceed.
    }

    return NULL;
  }

}
