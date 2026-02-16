<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_cases\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad CaseActivity.
 *
 * Estructura: Extiende EntityAccessControlHandler para actividades
 *   append-only de expedientes.
 *
 * Logica: Las actividades son append-only: se permite crear y ver,
 *   pero update y delete estan restringidos a administradores.
 *   Solo usuarios con 'manage legal cases' pueden gestionar actividades.
 */
class CaseActivityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('manage legal cases')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        if ($account->hasPermission('view own legal cases')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        return AccessResult::neutral();

      case 'update':
      case 'delete':
        // Append-only: solo admin puede editar/eliminar actividades.
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
