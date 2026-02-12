<?php

declare(strict_types=1);

namespace Drupal\jaraba_diagnostic;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad EmployabilityDiagnostic.
 *
 * PROPOSITO:
 * Gestiona permisos de view own, edit own, administer para
 * diagnosticos de empleabilidad.
 */
class EmployabilityDiagnosticAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    $adminPermission = 'administer employability diagnostics';

    if ($account->hasPermission($adminPermission)) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        if ($account->hasPermission('view own employability diagnostic') && $entity->getOwnerId() == $account->id()) {
          return AccessResult::allowed()->cachePerPermissions()->cachePerUser();
        }
        // Permitir acceso anonimo con token.
        return AccessResult::neutral();

      case 'update':
        if ($account->hasPermission('edit own employability diagnostic') && $entity->getOwnerId() == $account->id()) {
          return AccessResult::allowed()->cachePerPermissions()->cachePerUser();
        }
        return AccessResult::neutral();

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, $adminPermission);
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer employability diagnostics',
      'create employability diagnostic',
    ], 'OR');
  }

}
