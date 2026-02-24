<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_vault\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad SecureDocument.
 *
 * Estructura: Extiende EntityAccessControlHandler con permisos por operacion.
 * Logica: Administradores con 'administer vault' acceso completo.
 *   Usuarios con 'manage vault documents' gestionan documentos.
 *   Acceso basico con 'access vault' solo lectura.
 *   El propietario (owner_id) siempre puede ver sus propios documentos.
 */
class SecureDocumentAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer vault')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $is_owner = ((int) $entity->get('owner_id')->target_id === (int) $account->id());

    switch ($operation) {
      case 'view':
        if ($account->hasPermission('manage vault documents') || $account->hasPermission('access vault')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        if ($is_owner) {
          return AccessResult::allowed()->addCacheableDependency($entity)->cachePerUser();
        }
        return AccessResult::neutral();

      case 'update':
        if ($account->hasPermission('manage vault documents')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        if ($is_owner) {
          return AccessResult::allowed()->addCacheableDependency($entity)->cachePerUser();
        }
        return AccessResult::neutral();

      case 'delete':
        if ($account->hasPermission('manage vault documents')) {
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
      'administer vault',
      'manage vault documents',
    ], 'OR');
  }

}
