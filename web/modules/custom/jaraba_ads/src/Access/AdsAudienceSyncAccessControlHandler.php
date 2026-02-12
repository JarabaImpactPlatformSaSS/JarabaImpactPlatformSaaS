<?php

declare(strict_types=1);

namespace Drupal\jaraba_ads\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad AdsAudienceSync.
 *
 * ESTRUCTURA: Extiende EntityAccessControlHandler con lógica
 *   de permisos por operación (view, update, delete).
 *
 * LÓGICA: Los administradores con 'administer ads settings' tienen
 *   acceso completo. Los gestores con 'manage ads audiences' pueden
 *   crear, editar y eliminar audiencias. Los usuarios con 'view ads dashboard'
 *   solo pueden ver.
 *
 * RELACIONES:
 * - AdsAudienceSyncAccessControlHandler -> AdsAudienceSync entity (controla acceso)
 * - AdsAudienceSyncAccessControlHandler <- Drupal core (invocado por)
 */
class AdsAudienceSyncAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer ads settings')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermissions($account, [
          'view ads dashboard',
          'manage ads audiences',
        ], 'OR');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'manage ads audiences');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'manage ads audiences');
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer ads settings',
      'manage ads audiences',
    ], 'OR');
  }

}
