<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad MerchantProfile.
 *
 * Estructura: Extiende EntityAccessControlHandler con lógica
 *   de permisos por operación (view, update, delete).
 *
 * Lógica: Los administradores con 'manage comercio merchants' tienen
 *   acceso completo. Los comerciantes con 'edit own merchant profile'
 *   solo pueden editar/ver su propio perfil (verificación uid).
 */
class MerchantProfileAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('manage comercio merchants')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view comercio merchants');

      case 'update':
        $is_owner = $entity->getOwnerId() == $account->id();
        return AccessResult::allowedIf(
          $is_owner && $account->hasPermission('edit own merchant profile')
        )->addCacheableDependency($entity)->cachePerUser();

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'manage comercio merchants');
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'manage comercio merchants',
      'edit own merchant profile',
    ], 'OR');
  }

}
