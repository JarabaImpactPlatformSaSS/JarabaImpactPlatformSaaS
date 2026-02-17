<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_vault\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para DocumentAccess, DocumentRequest y DocumentDelivery.
 *
 * Estructura: Extiende EntityAccessControlHandler con permisos por operacion.
 * Logica: Administradores con 'administer vault' acceso completo.
 *   'manage document access' permite gestionar comparticiones.
 *   'manage portal' permite gestionar solicitudes y entregas.
 *   Propietarios pueden ver sus propias entidades.
 */
class DocumentAccessAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer vault')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $is_owner = FALSE;
    if ($entity->hasField('granted_by') && $entity->get('granted_by')->target_id == $account->id()) {
      $is_owner = TRUE;
    }
    elseif ($entity->hasField('uid') && $entity->get('uid')->target_id == $account->id()) {
      $is_owner = TRUE;
    }

    switch ($operation) {
      case 'view':
        if ($account->hasPermission('manage document access') || $account->hasPermission('manage portal')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        if ($is_owner) {
          return AccessResult::allowed()->addCacheableDependency($entity)->cachePerUser();
        }
        return AccessResult::neutral();

      case 'update':
        if ($account->hasPermission('manage document access') || $account->hasPermission('manage portal')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        return AccessResult::neutral();

      case 'delete':
        if ($account->hasPermission('manage document access')) {
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
      'manage document access',
      'manage portal',
    ], 'OR');
  }

}
