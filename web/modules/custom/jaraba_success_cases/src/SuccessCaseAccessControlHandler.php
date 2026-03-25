<?php

declare(strict_types=1);

namespace Drupal\jaraba_success_cases;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for Success Case entities.
 *
 * Permissions:
 * - administer success cases: full CRUD (site_admin + content_editor)
 * - view published success cases: frontend viewing
 * - view unpublished success cases: preview drafts.
 */
class SuccessCaseAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   *
   * TENANT-ISOLATION-ACCESS-001: update/delete verifican tenant match.
   * ACCESS-STRICT-001: comparación con (int) cast + ===.
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    /** @var \Drupal\jaraba_success_cases\Entity\SuccessCase $entity */

    if ($account->hasPermission('administer success cases')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        if ($entity->get('status')->value) {
          return AccessResult::allowedIfHasPermission($account, 'view published success cases')
            ->addCacheableDependency($entity);
        }
        return AccessResult::allowedIfHasPermission($account, 'view unpublished success cases')
          ->addCacheableDependency($entity);

      case 'update':
      case 'delete':
        $hasPermission = AccessResult::allowedIfHasPermission($account, 'administer success cases')
          ->cachePerPermissions();

        // TENANT-ISOLATION-ACCESS-001: verify tenant match for
        // update/delete operations when tenant_id is set.
        if ($entity->hasField('tenant_id') && !$entity->get('tenant_id')->isEmpty()) {
          $entityTenantId = (int) $entity->get('tenant_id')->target_id;
          $currentTenantId = $this->resolveCurrentTenantId();
          if ($currentTenantId !== NULL && $entityTenantId !== $currentTenantId) {
            return AccessResult::forbidden('Tenant mismatch')
              ->addCacheableDependency($entity)
              ->cachePerUser();
          }
        }

        return $hasPermission;
    }

    return AccessResult::neutral();
  }

  /**
   * Resolves the current tenant ID from the tenant context service.
   *
   * @return int|null
   *   The current tenant Group ID, or NULL if unavailable.
   */
  protected function resolveCurrentTenantId(): ?int {
    if (!\Drupal::hasService('ecosistema_jaraba_core.tenant_context')) {
      return NULL;
    }

    try {
      $tenantContext = \Drupal::service('ecosistema_jaraba_core.tenant_context');
      $tenantId = $tenantContext->getCurrentTenantId();
      return $tenantId ? (int) $tenantId : NULL;
    }
    catch (\Throwable) {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResultInterface {
    return AccessResult::allowedIfHasPermission($account, 'administer success cases');
  }

}
