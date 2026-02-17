<?php

declare(strict_types=1);

namespace Drupal\jaraba_predictive\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad ChurnPrediction (APPEND-ONLY).
 *
 * Estructura: Entidad inmutable — prohibe update siempre; delete solo admin.
 * Logica: Las predicciones de churn son registros historicos que no se modifican
 *   (regla ENTITY-APPEND-001).
 *   Solo vista para usuarios con 'view churn predictions'.
 *   Solo creacion para usuarios con 'execute predictions' o 'administer predictions'.
 *   Delete solo para administradores.
 */
class ChurnPredictionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    // Acceso completo de lectura para administradores.
    if ($account->hasPermission('administer predictions')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        // Permiso basico de visualizacion de predicciones de churn.
        return AccessResult::allowedIfHasPermission($account, 'view churn predictions')
          ->cachePerPermissions();

      case 'update':
        // Append-only: las predicciones son inmutables.
        return AccessResult::forbidden('Las predicciones de churn son inmutables (ENTITY-APPEND-001).')
          ->addCacheTags(['churn_prediction_list']);

      case 'delete':
        // Append-only: las predicciones no se pueden eliminar excepto admin.
        return AccessResult::forbidden('Las predicciones de churn no se pueden eliminar (ENTITY-APPEND-001).')
          ->addCacheTags(['churn_prediction_list']);
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    // Crear predicciones requiere permiso de ejecucion o administracion.
    return AccessResult::allowedIfHasPermissions($account, [
      'execute predictions',
      'administer predictions',
    ], 'OR');
  }

}
