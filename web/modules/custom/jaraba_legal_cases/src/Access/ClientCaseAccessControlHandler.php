<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_cases\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad ClientCase.
 *
 * Estructura: Extiende EntityAccessControlHandler con logica
 *   de permisos por operacion.
 *
 * Logica: Los administradores con 'manage legal cases' tienen
 *   acceso completo. Los abogados ven/gestionan sus expedientes
 *   asignados (assigned_to o uid = owner).
 */
class ClientCaseAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('manage legal cases')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $is_owner = ($entity->getOwnerId() == $account->id());

    switch ($operation) {
      case 'view':
        if ($is_owner && $account->hasPermission('view own legal cases')) {
          return AccessResult::allowed()->addCacheableDependency($entity)->cachePerUser();
        }
        return AccessResult::neutral();

      case 'update':
        if ($is_owner && $account->hasPermission('edit own legal cases')) {
          return AccessResult::allowed()->addCacheableDependency($entity)->cachePerUser();
        }
        return AccessResult::neutral();

      case 'delete':
        if ($is_owner && $account->hasPermission('delete own legal cases')) {
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
      'manage legal cases',
      'create legal cases',
    ], 'OR');
  }

}
