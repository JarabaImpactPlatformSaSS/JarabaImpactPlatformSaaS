<?php

declare(strict_types=1);

namespace Drupal\jaraba_predictive\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad LeadScore (CRUD estandar).
 *
 * Estructura: Extiende EntityAccessControlHandler con permisos por operacion.
 * Logica: Administradores con 'administer predictions' acceso completo.
 *   Usuarios con 'manage prediction models' pueden crear y actualizar.
 *   Acceso de lectura con 'view lead scores'.
 *   Solo administradores pueden eliminar puntuaciones de leads.
 */
class LeadScoreAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    // Acceso completo para administradores de predicciones.
    if ($account->hasPermission('administer predictions')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        // Permiso basico de visualizacion de lead scores.
        return AccessResult::allowedIfHasPermission($account, 'view lead scores')
          ->cachePerPermissions();

      case 'update':
        // Solo quienes pueden gestionar modelos de prediccion.
        return AccessResult::allowedIfHasPermission($account, 'manage prediction models')
          ->cachePerPermissions();

      case 'delete':
        // Eliminar lead scores es exclusivo de administradores.
        return AccessResult::neutral()
          ->cachePerPermissions();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    // Crear lead scores requiere permiso de gestion de modelos o administracion.
    return AccessResult::allowedIfHasPermissions($account, [
      'manage prediction models',
      'administer predictions',
    ], 'OR');
  }

}
