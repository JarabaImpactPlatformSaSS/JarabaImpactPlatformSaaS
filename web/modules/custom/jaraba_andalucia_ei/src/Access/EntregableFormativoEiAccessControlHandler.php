<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for EntregableFormativoEi.
 *
 * View: admin OR owner OR 'mark attendance sesion ei' (formadores).
 * Update: admin OR owner.
 * Create/Delete: admin only (auto-created by seed).
 *
 * TENANT-ISOLATION-ACCESS-001: Verifica tenant match para update/delete.
 * ACCESS-RETURN-TYPE-001: checkAccess() retorna AccessResultInterface.
 */
class EntregableFormativoEiAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    if ($account->hasPermission('administer andalucia ei')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $isOwner = $entity->hasField('uid')
      && (int) $entity->get('uid')->target_id === (int) $account->id();

    switch ($operation) {
      case 'view':
        // Owner can view their own entregables.
        if ($isOwner) {
          return AccessResult::allowed()
            ->addCacheableDependency($entity)
            ->cachePerUser();
        }
        // Formadores (mark attendance permission) can also view.
        if ($account->hasPermission('mark attendance sesion ei')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        break;

      case 'update':
        // TENANT-ISOLATION-ACCESS-001: tenant match required.
        $tenantDenied = $this->checkTenantMismatch($entity, $account);
        if ($tenantDenied !== NULL) {
          return $tenantDenied;
        }
        if ($isOwner) {
          return AccessResult::allowed()
            ->addCacheableDependency($entity)
            ->cachePerUser();
        }
        break;

      case 'delete':
        // Delete is admin-only (handled above). Deny everyone else.
        return AccessResult::forbidden('Solo administradores pueden eliminar entregables.')
          ->cachePerPermissions();
    }

    return parent::checkAccess($entity, $operation, $account);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResultInterface {
    return AccessResult::allowedIfHasPermission($account, 'administer andalucia ei');
  }

  /**
   * Check tenant isolation for update/delete operations.
   *
   * TENANT-ISOLATION-ACCESS-001.
   */
  private function checkTenantMismatch(EntityInterface $entity, AccountInterface $account): ?AccessResultInterface {
    if ($entity->hasField('tenant_id') && !$entity->get('tenant_id')->isEmpty()) {
      $userTenantId = $this->resolveUserTenant($account);
      $entityTenantId = (int) $entity->get('tenant_id')->target_id;
      if ($userTenantId !== null && $userTenantId !== $entityTenantId) {
        return AccessResult::forbidden('Tenant mismatch.')
          ->addCacheableDependency($entity)
          ->cachePerUser();
      }
    }
    return NULL;
  }

  /**
   * Resolve user's tenant ID.
   */
  private function resolveUserTenant(AccountInterface $account): ?int {
    if (\Drupal::hasService('ecosistema_jaraba_core.tenant_context')) {
      try {
        $tenant = \Drupal::service('ecosistema_jaraba_core.tenant_context')->getCurrentTenant();
        return $tenant !== NULL ? (int) $tenant->id() : NULL;
      }
      catch (\Throwable) {
        return NULL;
      }
    }
    return NULL;
  }

}
