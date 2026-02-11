<?php

namespace Drupal\jaraba_ab_testing\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad ABVariant.
 *
 * Estructura: Extiende EntityAccessControlHandler con lógica
 *   de permisos por operación (view, update, delete).
 *
 * Lógica: Los administradores con 'administer ab testing' tienen
 *   acceso completo. Los usuarios con 'view experiment results'
 *   pueden ver variantes. Los usuarios con 'manage experiment variants'
 *   pueden crear, editar y eliminar variantes.
 *
 * Relaciones: Usa los permisos definidos en
 *   jaraba_ab_testing.permissions.yml. Las variantes heredan
 *   conceptualmente el contexto del experimento padre.
 *
 * Sintaxis: Drupal 11 — AccessResult con cachePerPermissions.
 */
class ABVariantAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer ab testing')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view experiment results');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'manage experiment variants');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'manage experiment variants');
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer ab testing',
      'manage experiment variants',
    ], 'OR');
  }

}
