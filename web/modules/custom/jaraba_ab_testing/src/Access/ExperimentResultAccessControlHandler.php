<?php

declare(strict_types=1);

namespace Drupal\jaraba_ab_testing\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad ExperimentResult.
 *
 * Estructura: Extiende EntityAccessControlHandler con logica
 *   de permisos por operacion (view, update, delete).
 *
 * Logica: Los administradores con 'administer ab testing' tienen
 *   acceso completo. Los usuarios con 'view experiment results'
 *   pueden ver resultados. Los usuarios con 'edit any experiment'
 *   pueden editar cualquier resultado. Los usuarios con
 *   'delete any experiment' pueden eliminar cualquier resultado.
 *
 * Relaciones: Usa los permisos definidos en
 *   jaraba_ab_testing.permissions.yml.
 *
 * Sintaxis: Drupal 11 — AccessResult con cachePerPermissions.
 */
class ExperimentResultAccessControlHandler extends EntityAccessControlHandler {

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
