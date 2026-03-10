<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for IndicadorFsePlus.
 *
 * TENANT-ISOLATION-ACCESS-001: Verifies tenant match for update/delete.
 * ACCESS-STRICT-001: Ownership with (int) === (int).
 * ACCESS-RETURN-TYPE-001: Returns AccessResultInterface.
 */
class IndicadorFsePlusAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    $admin = AccessResult::allowedIfHasPermission($account, 'administer andalucia ei');
    if ($admin->isAllowed()) {
      return $admin;
    }

    if ($operation === 'view') {
      return AccessResult::allowedIfHasPermission($account, 'view indicador fse plus');
    }

    if (in_array($operation, ['update', 'delete'], TRUE)) {
      // TENANT-ISOLATION-ACCESS-001: Verify tenant match.
      $tenantAccess = $this->checkTenantAccess($entity, $account);
      if ($tenantAccess !== NULL && !$tenantAccess) {
        return AccessResult::forbidden('Tenant mismatch.')
          ->addCacheableDependency($entity);
      }

      // ACCESS-STRICT-001: (int) === (int) comparisons.
      $isOwner = (int) $entity->getOwnerId() === (int) $account->id();
      return AccessResult::allowedIf($isOwner)
        ->andIf(AccessResult::allowedIfHasPermission($account, 'edit indicador fse plus'))
        ->addCacheableDependency($entity);
    }

    return parent::checkAccess($entity, $operation, $account);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResultInterface {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer andalucia ei',
      'create indicador fse plus',
    ], 'OR');
  }

  /**
   * Check tenant isolation for the entity.
   *
   * TENANT-ISOLATION-ACCESS-001: Verifies entity tenant matches user's tenant.
   * TENANT-002: Uses ecosistema_jaraba_core.tenant_context.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return bool|null
   *   TRUE if tenant matches, FALSE if mismatch, NULL if cannot determine.
   */
  protected function checkTenantAccess(EntityInterface $entity, AccountInterface $account): ?bool {
    if (!$entity->hasField('tenant_id')) {
      return NULL;
    }

    $entityTenantId = (int) ($entity->get('tenant_id')->target_id ?? 0);
    if ($entityTenantId === 0) {
      return NULL;
    }

    // TENANT-002: Use tenant_context service.
    if (\Drupal::hasService('ecosistema_jaraba_core.tenant_context')) {
      try {
        $tenantContext = \Drupal::service('ecosistema_jaraba_core.tenant_context');
        $currentTenant = $tenantContext->getCurrentTenant();
        if ($currentTenant) {
          return (int) $currentTenant->id() === $entityTenantId;
        }
      }
      catch (\Throwable) {
        return NULL;
      }
    }

    return NULL;
  }

}
