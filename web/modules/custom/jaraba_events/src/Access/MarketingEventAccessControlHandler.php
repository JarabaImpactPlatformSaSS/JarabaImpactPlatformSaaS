<?php

namespace Drupal\jaraba_events\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad MarketingEvent.
 *
 * Estructura: Extiende EntityAccessControlHandler con lógica
 *   de permisos por operación (view, update, delete).
 *
 * Lógica: Los administradores con 'manage marketing events' tienen
 *   acceso completo. Los organizadores con 'edit own marketing events'
 *   solo pueden editar sus propios eventos (verificación uid).
 *
 * Sintaxis: Drupal 11 — AccessResult con cachePerPermissions/cachePerUser.
 */
class MarketingEventAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('manage marketing events')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view marketing events');

      case 'update':
        $is_owner = $entity->getOwnerId() == $account->id();
        return AccessResult::allowedIf(
          $is_owner && $account->hasPermission('edit own marketing events')
        )->addCacheableDependency($entity)->cachePerUser();

      case 'delete':
        $is_owner = $entity->getOwnerId() == $account->id();
        return AccessResult::allowedIf(
          $is_owner && $account->hasPermission('delete own marketing events')
        )->addCacheableDependency($entity)->cachePerUser();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'manage marketing events',
      'create marketing events',
    ], 'OR');
  }

}
