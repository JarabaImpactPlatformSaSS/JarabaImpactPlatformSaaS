<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad FundingApplication.
 *
 * Estructura: Handler de acceso para solicitudes de fondos.
 *   Diferencia entre ver y gestionar solicitudes, con acceso
 *   implicito para el propietario de la solicitud.
 *
 * Logica: Los administradores tienen acceso total. El propietario
 *   puede ver y editar sus propias solicitudes. Otros usuarios
 *   necesitan permisos explicitos.
 */
class FundingApplicationAccessControlHandler extends EntityAccessControlHandler {

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
          return AccessResult::allowedIfHasPermission($account, 'view funding applications')
            ->cachePerPermissions()
            ->cachePerUser();
        }
        return AccessResult::allowedIfHasPermission($account, 'view funding applications')
          ->cachePerPermissions();

      case 'update':
      case 'edit':
        if ($is_owner) {
          return AccessResult::allowedIfHasPermission($account, 'manage funding applications')
            ->cachePerPermissions()
            ->cachePerUser();
        }
        return AccessResult::allowedIfHasPermission($account, 'manage funding applications')
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
      'manage funding applications',
    ], 'OR')->cachePerPermissions();
  }

}
