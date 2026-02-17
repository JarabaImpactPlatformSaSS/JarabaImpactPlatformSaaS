<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad FundingOpportunity.
 *
 * Estructura: Handler de acceso basado en permisos del usuario.
 *   Implementa short-circuit para administradores y permisos
 *   diferenciados para ver y gestionar convocatorias.
 *
 * Logica: Los administradores de fondos tienen acceso completo.
 *   Los usuarios con 'manage funding opportunities' pueden crear/editar.
 *   Los usuarios con 'view funding opportunities' solo pueden ver.
 *   El propietario (uid) tiene acceso de edicion implicito.
 */
class FundingOpportunityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer funding')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view funding opportunities')
          ->cachePerPermissions();

      case 'update':
      case 'edit':
        $is_owner = ((int) $entity->getOwnerId() === (int) $account->id());
        if ($is_owner) {
          return AccessResult::allowedIfHasPermission($account, 'manage funding opportunities')
            ->cachePerPermissions()
            ->cachePerUser();
        }
        return AccessResult::allowedIfHasPermission($account, 'manage funding opportunities')
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
      'manage funding opportunities',
    ], 'OR')->cachePerPermissions();
  }

}
