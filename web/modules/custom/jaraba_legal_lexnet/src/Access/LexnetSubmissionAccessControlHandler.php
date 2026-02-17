<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_lexnet\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad LexnetSubmission.
 *
 * Estructura: Extiende EntityAccessControlHandler con logica
 *   de permisos por operacion.
 *
 * Logica: Los administradores con 'administer lexnet' tienen
 *   acceso completo. Usuarios con 'manage lexnet submissions'
 *   pueden crear, editar y consultar envios. El propietario del
 *   envio (uid) puede ver sus propios envios.
 */
class LexnetSubmissionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer lexnet')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $is_owner = ($entity->getOwnerId() == $account->id());

    switch ($operation) {
      case 'view':
        if ($account->hasPermission('manage lexnet submissions')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        if ($is_owner && $account->hasPermission('access lexnet')) {
          return AccessResult::allowed()->addCacheableDependency($entity)->cachePerUser();
        }
        return AccessResult::neutral();

      case 'update':
        if ($account->hasPermission('manage lexnet submissions')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        if ($is_owner && $account->hasPermission('access lexnet')) {
          return AccessResult::allowed()->addCacheableDependency($entity)->cachePerUser();
        }
        return AccessResult::neutral();

      case 'delete':
        if ($account->hasPermission('administer lexnet')) {
          return AccessResult::allowed()->cachePerPermissions();
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
      'administer lexnet',
      'manage lexnet submissions',
    ], 'OR');
  }

}
