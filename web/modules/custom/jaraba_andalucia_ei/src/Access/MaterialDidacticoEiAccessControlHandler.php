<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for MaterialDidacticoEi.
 *
 * TENANT-ISOLATION-ACCESS-001: Verifica tenant match para update/delete.
 * ACCESS-RETURN-TYPE-001: checkAccess() retorna AccessResultInterface.
 */
class MaterialDidacticoEiAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    if ($account->hasPermission('administer andalucia ei')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    // TENANT-ISOLATION-ACCESS-001: view is public, update/delete require tenant match.
    if (in_array($operation, ['update', 'delete'], TRUE)) {
      if ($entity->hasField('tenant_id') && $entity->get('tenant_id')->target_id) {
        $userTenantId = $this->resolveUserTenant($account);
        $entityTenantId = (int) $entity->get('tenant_id')->target_id;
        if ($userTenantId && (int) $userTenantId !== $entityTenantId) {
          return AccessResult::forbidden('Tenant mismatch.')
            ->addCacheableDependency($entity)
            ->cachePerUser();
        }
      }
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
   * Resolve user's tenant ID.
   */
  private function resolveUserTenant(AccountInterface $account): ?int {
    if (\Drupal::hasService('ecosistema_jaraba_core.tenant_context')) {
      try {
        $tenant = \Drupal::service('ecosistema_jaraba_core.tenant_context')->getCurrentTenant();
        return $tenant ? (int) $tenant->id() : NULL;
      }
      catch (\Throwable) {
        return NULL;
      }
    }
    return NULL;
  }

}
