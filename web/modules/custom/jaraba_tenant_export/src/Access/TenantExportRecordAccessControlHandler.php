<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_export\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad TenantExportRecord.
 *
 * Administradores con 'administer tenant exports' tienen acceso completo.
 * Usuarios con 'view own exports' solo ven exportaciones de su tenant.
 */
class TenantExportRecordAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer tenant exports')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        if ($account->hasPermission('view all exports')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        return AccessResult::allowedIfHasPermission($account, 'view own exports')
          ->addCacheableDependency($entity)
          ->cachePerUser();

      case 'update':
      case 'delete':
        return AccessResult::neutral();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer tenant exports',
      'request tenant export',
    ], 'OR');
  }

}
