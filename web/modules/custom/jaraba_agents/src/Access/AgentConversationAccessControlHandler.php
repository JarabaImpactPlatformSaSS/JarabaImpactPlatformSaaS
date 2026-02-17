<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad AgentConversation.
 *
 * ESTRUCTURA:
 *   Extiende EntityAccessControlHandler con permisos estandar
 *   para operaciones CRUD sobre conversaciones de agentes.
 *
 * LOGICA:
 *   - view: requiere 'view conversations' o 'administer agents'.
 *   - update: requiere 'manage conversations' o 'administer agents'.
 *   - delete: requiere exclusivamente 'administer agents'.
 *   - create: requiere 'manage conversations' o 'administer agents'.
 *   AUDIT-CONS-005: tenant_id como entity_reference a group.
 */
class AgentConversationAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    // Acceso completo para administradores en cualquier operacion.
    if ($account->hasPermission('administer agents')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view conversations')
          ->cachePerPermissions();

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'manage conversations')
          ->cachePerPermissions();

      case 'delete':
        // Solo administradores pueden eliminar conversaciones.
        return AccessResult::forbidden('Solo administradores pueden eliminar conversaciones.')
          ->addCacheTags(['agent_conversation_list']);
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
