<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_billing\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para TimeEntry.
 *
 * Estructura: Extiende EntityAccessControlHandler con permisos por operacion.
 * Logica: 'manage time entries' = CRUD completo. El propietario siempre
 *   puede ver y editar sus propias entradas de tiempo.
 */
class TimeEntryAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer billing')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $isOwner = ((int) $entity->get('user_id')->target_id === (int) $account->id());

    switch ($operation) {
      case 'view':
        if ($account->hasPermission('manage time entries') || $account->hasPermission('access billing')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        if ($isOwner) {
          return AccessResult::allowed()->addCacheableDependency($entity)->cachePerUser();
        }
        return AccessResult::neutral();

      case 'update':
        if ($account->hasPermission('manage time entries') || $isOwner) {
          return AccessResult::allowed()->cachePerPermissions()->cachePerUser();
        }
        return AccessResult::neutral();

      case 'delete':
        if ($account->hasPermission('manage time entries')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        if ($isOwner) {
          return AccessResult::allowed()->addCacheableDependency($entity)->cachePerUser();
        }
        return AccessResult::neutral();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer billing',
      'manage time entries',
    ], 'OR');
  }

}
