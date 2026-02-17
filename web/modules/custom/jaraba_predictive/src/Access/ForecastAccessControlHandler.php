<?php

declare(strict_types=1);

namespace Drupal\jaraba_predictive\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad Forecast (APPEND-ONLY).
 *
 * Estructura: Entidad inmutable — prohibe update siempre; delete solo admin.
 * Logica: Los forecasts son registros historicos que no se modifican
 *   (regla ENTITY-APPEND-001).
 *   Solo vista para usuarios con 'view forecasts'.
 *   Solo creacion para usuarios con 'execute predictions' o 'administer predictions'.
 *   Delete solo para administradores.
 */
class ForecastAccessControlHandler extends EntityAccessControlHandler {

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
        // Permiso basico de visualizacion de forecasts.
        return AccessResult::allowedIfHasPermission($account, 'view forecasts')
          ->cachePerPermissions();

      case 'update':
        // Append-only: los forecasts son inmutables.
        return AccessResult::forbidden('Los forecasts son inmutables (ENTITY-APPEND-001).')
          ->addCacheTags(['forecast_list']);

      case 'delete':
        // Append-only: los forecasts no se pueden eliminar excepto admin.
        return AccessResult::forbidden('Los forecasts no se pueden eliminar (ENTITY-APPEND-001).')
          ->addCacheTags(['forecast_list']);
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    // Crear forecasts requiere permiso de ejecucion o administracion.
    return AccessResult::allowedIfHasPermissions($account, [
      'execute predictions',
      'administer predictions',
    ], 'OR');
  }

}
