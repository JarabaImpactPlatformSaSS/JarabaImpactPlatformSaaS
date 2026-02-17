<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad AgentExecution (APPEND-ONLY).
 *
 * Estructura: Entidad inmutable — prohibe update y delete siempre.
 * Logica: Las ejecuciones son registros historicos que no se modifican
 *   ni eliminan (regla ENTITY-APPEND-001).
 *   Solo vista para usuarios con 'view executions'.
 *   Solo creacion para usuarios con 'execute agents' o 'administer agents'.
 */
class AgentExecutionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    // Acceso completo de lectura para administradores.
    if ($account->hasPermission('administer agents') && $operation === 'view') {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        // Permiso basico de visualizacion de ejecuciones.
        return AccessResult::allowedIfHasPermission($account, 'view executions')
          ->cachePerPermissions();

      case 'update':
        // Append-only: las ejecuciones son inmutables.
        return AccessResult::forbidden('Las ejecuciones son inmutables (ENTITY-APPEND-001).')
          ->addCacheTags(['agent_execution_list']);

      case 'delete':
        // Append-only: las ejecuciones no se pueden eliminar.
        return AccessResult::forbidden('Las ejecuciones no se pueden eliminar (ENTITY-APPEND-001).')
          ->addCacheTags(['agent_execution_list']);
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    // Crear ejecuciones requiere permiso de ejecucion o administracion.
    return AccessResult::allowedIfHasPermissions($account, [
      'execute agents',
      'administer agents',
    ], 'OR');
  }

}
