<?php

namespace Drupal\jaraba_ab_testing\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad ABExperiment.
 *
 * Estructura: Extiende EntityAccessControlHandler con lógica
 *   de permisos por operación (view, update, delete).
 *
 * Lógica: Los administradores con 'administer ab testing' tienen
 *   acceso completo. Los usuarios con 'view experiment results'
 *   pueden ver experimentos. Los usuarios con 'edit any experiment'
 *   pueden editar cualquier experimento. Los usuarios con
 *   'delete any experiment' pueden eliminar cualquier experimento.
 *
 * Relaciones: Usa los permisos definidos en
 *   jaraba_ab_testing.permissions.yml.
 *
 * Sintaxis: Drupal 11 — AccessResult con cachePerPermissions.
 */
class ABExperimentAccessControlHandler extends EntityAccessControlHandler {

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
        return AccessResult::allowedIfHasPermission($account, 'edit any experiment');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete any experiment');
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer ab testing',
      'create experiments',
    ], 'OR');
  }

}
