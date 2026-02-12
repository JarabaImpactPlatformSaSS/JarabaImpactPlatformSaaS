<?php

declare(strict_types=1);

namespace Drupal\jaraba_agent_flows\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para entidades AgentFlow.
 *
 * PROPOSITO:
 * Gestiona permisos de lectura, edicion y eliminacion de flujos de agentes IA.
 *
 * LOGICA:
 * - view: requiere 'view agent flows'
 * - update: requiere 'manage agent flows'
 * - delete: requiere 'administer agent flows'
 * - create: requiere 'manage agent flows'
 */
class AgentFlowAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    return match ($operation) {
      'view' => AccessResult::allowedIfHasPermission($account, 'view agent flows'),
      'update' => AccessResult::allowedIfHasPermission($account, 'manage agent flows'),
      'delete' => AccessResult::allowedIfHasPermission($account, 'administer agent flows'),
      default => parent::checkAccess($entity, $operation, $account),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'manage agent flows');
  }

}
