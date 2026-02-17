<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad AgentHandoff (APPEND-ONLY).
 *
 * ESTRUCTURA:
 *   Extiende EntityAccessControlHandler con patron APPEND-ONLY
 *   (ENTITY-APPEND-001). Los handoffs son registros inmutables.
 *
 * LOGICA:
 *   - view: requiere 'view handoffs' o 'administer agents'.
 *   - update: PROHIBIDO siempre (ENTITY-APPEND-001).
 *   - delete: PROHIBIDO siempre (ENTITY-APPEND-001).
 *   - create: requiere 'manage conversations' o 'administer agents'.
 *   AUDIT-CONS-005: tenant_id como entity_reference a group.
 */
class AgentHandoffAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    // Acceso de lectura completo para administradores.
    if ($account->hasPermission('administer agents') && $operation === 'view') {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view handoffs')
          ->cachePerPermissions();

      case 'update':
        // Append-only: los handoffs son inmutables.
        return AccessResult::forbidden('Los handoffs son inmutables (ENTITY-APPEND-001).')
          ->addCacheTags(['agent_handoff_list']);

      case 'delete':
        // Append-only: los handoffs no se pueden eliminar.
        return AccessResult::forbidden('Los handoffs no se pueden eliminar (ENTITY-APPEND-001).')
          ->addCacheTags(['agent_handoff_list']);
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'manage conversations',
      'administer agents',
    ], 'OR');
  }

}
