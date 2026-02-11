<?php

namespace Drupal\jaraba_addons\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad Addon.
 *
 * ESTRUCTURA: Extiende EntityAccessControlHandler con lógica
 *   de permisos por operación (view, update, delete).
 *
 * LÓGICA: Los administradores con 'administer addons settings' tienen
 *   acceso completo. Los gestores con 'manage addons' pueden crear, editar
 *   y eliminar add-ons del catálogo. Los usuarios con 'view addon catalog'
 *   solo pueden ver.
 *
 * RELACIONES:
 * - AddonAccessControlHandler -> Addon entity (controla acceso)
 * - AddonAccessControlHandler <- Drupal core (invocado por)
 */
class AddonAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer addons settings')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermissions($account, [
          'view addon catalog',
          'manage addons',
        ], 'OR');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'manage addons');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'manage addons');
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer addons settings',
      'manage addons',
    ], 'OR');
  }

}
