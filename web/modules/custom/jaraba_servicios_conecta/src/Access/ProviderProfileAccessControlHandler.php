<?php

declare(strict_types=1);

namespace Drupal\jaraba_servicios_conecta\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad ProviderProfile.
 *
 * Estructura: Extiende EntityAccessControlHandler con lógica
 *   de permisos por operación (view, update, delete).
 *
 * Lógica: Los administradores con 'manage servicios providers' tienen
 *   acceso completo. Los profesionales con 'edit own provider profile'
 *   solo pueden editar/ver su propio perfil (verificación uid).
 */
class ProviderProfileAccessControlHandler extends DefaultEntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    // TENANT-ISOLATION-ACCESS-001: Tenant isolation via parent.
    $parentResult = parent::checkAccess($entity, $operation, $account);
    if ($parentResult->isForbidden()) {
      return $parentResult;
    }

    if ($account->hasPermission('manage servicios providers')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view servicios providers');

      case 'update':
        $is_owner = (int) $entity->getOwnerId() === (int) $account->id();
        return AccessResult::allowedIf(
          $is_owner && $account->hasPermission('edit own provider profile')
        )->addCacheableDependency($entity)->cachePerUser();

      case 'delete':
        $is_owner = (int) $entity->getOwnerId() === (int) $account->id();
        return AccessResult::allowedIf(
          $is_owner && $account->hasPermission('delete own provider profile')
        )->addCacheableDependency($entity)->cachePerUser();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'manage servicios providers',
      'create servicios providers',
    ], 'OR');
  }

}
