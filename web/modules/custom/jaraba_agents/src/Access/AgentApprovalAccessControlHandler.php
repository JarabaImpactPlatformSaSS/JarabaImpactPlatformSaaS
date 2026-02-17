<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad AgentApproval.
 *
 * Estructura: Extiende EntityAccessControlHandler con permisos por operacion.
 * Logica: Las aprobaciones se crean normalmente por el sistema.
 *   Solo administradores pueden crear o eliminar aprobaciones.
 *   Usuarios con 'manage approvals' pueden actualizar (aprobar/rechazar).
 *   Usuarios con 'view approvals' solo lectura.
 */
class AgentApprovalAccessControlHandler extends EntityAccessControlHandler {

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
        // Permiso basico de visualizacion de aprobaciones.
        return AccessResult::allowedIfHasPermission($account, 'view approvals')
          ->cachePerPermissions();

      case 'update':
        // Gestionar aprobaciones (aprobar/rechazar).
        return AccessResult::allowedIfHasPermission($account, 'manage approvals')
          ->cachePerPermissions();

      case 'delete':
        // Eliminar aprobaciones es exclusivo de administradores.
        return AccessResult::neutral()
          ->cachePerPermissions();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    // Normalmente las aprobaciones las crea el sistema, no los usuarios.
    return AccessResult::allowedIfHasPermission($account, 'administer agents');
  }

}
