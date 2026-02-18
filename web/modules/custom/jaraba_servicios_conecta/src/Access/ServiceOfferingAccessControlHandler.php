<?php

declare(strict_types=1);

namespace Drupal\jaraba_servicios_conecta\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad ServiceOffering.
 *
 * Estructura: Extiende EntityAccessControlHandler con lógica
 *   de permisos por operación.
 *
 * Lógica: Los administradores con 'manage servicios offerings' tienen
 *   acceso completo. Los profesionales solo pueden gestionar sus
 *   propios servicios via la cadena offering→provider→uid.
 */
class ServiceOfferingAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('manage servicios offerings')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view servicios offerings');

      case 'update':
        $is_owner = $entity->getOwnerId() == $account->id();
        return AccessResult::allowedIf(
          $is_owner && $account->hasPermission('edit own servicios offerings')
        )->addCacheableDependency($entity)->cachePerUser();

      case 'delete':
        $is_owner = $entity->getOwnerId() == $account->id();
        return AccessResult::allowedIf(
          $is_owner && $account->hasPermission('delete own servicios offerings')
        )->addCacheableDependency($entity)->cachePerUser();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'manage servicios offerings',
      'create servicios offerings',
    ], 'OR');
  }

}
