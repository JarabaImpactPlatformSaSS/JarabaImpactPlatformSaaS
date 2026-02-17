<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad TechnicalReport.
 *
 * Estructura: Handler de acceso para memorias tecnicas.
 *
 * Logica: Las memorias tecnicas requieren permisos especificos
 *   de gestion o visualizacion. El propietario tiene acceso
 *   de edicion implicito.
 */
class TechnicalReportAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer funding')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $is_owner = ((int) $entity->getOwnerId() === (int) $account->id());

    switch ($operation) {
      case 'view':
        if ($is_owner) {
          return AccessResult::allowedIfHasPermission($account, 'view technical reports')
            ->cachePerPermissions()
            ->cachePerUser();
        }
        return AccessResult::allowedIfHasPermission($account, 'view technical reports')
          ->cachePerPermissions();

      case 'update':
      case 'edit':
        if ($is_owner) {
          return AccessResult::allowedIfHasPermission($account, 'manage technical reports')
            ->cachePerPermissions()
            ->cachePerUser();
        }
        return AccessResult::allowedIfHasPermission($account, 'manage technical reports')
          ->cachePerPermissions();

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'administer funding')
          ->cachePerPermissions();
    }

    return AccessResult::neutral()->cachePerPermissions();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer funding',
      'manage technical reports',
    ], 'OR')->cachePerPermissions();
  }

}
