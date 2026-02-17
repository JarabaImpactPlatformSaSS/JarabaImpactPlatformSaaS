<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad AutonomousAgent.
 *
 * Estructura: Extiende EntityAccessControlHandler con permisos por operacion.
 * Logica: Administradores con 'administer agents' acceso completo.
 *   Usuarios con 'configure agents' pueden crear y actualizar.
 *   Acceso de lectura con 'view agents'.
 *   Solo administradores pueden eliminar agentes.
 */
class AutonomousAgentAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    // Acceso completo para administradores de agentes.
    if ($account->hasPermission('administer agents')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        // Permiso basico de visualizacion de agentes.
        return AccessResult::allowedIfHasPermission($account, 'view agents')
          ->cachePerPermissions();

      case 'update':
        // Solo quienes pueden configurar agentes.
        return AccessResult::allowedIfHasPermission($account, 'configure agents')
          ->cachePerPermissions();

      case 'delete':
        // Eliminar agentes es exclusivo de administradores.
        return AccessResult::neutral()
          ->cachePerPermissions();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    // Crear agentes requiere permiso de configuracion o administracion.
    return AccessResult::allowedIfHasPermissions($account, [
      'configure agents',
      'administer agents',
    ], 'OR');
  }

}
