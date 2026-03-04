<?php

declare(strict_types=1);

namespace Drupal\jaraba_agent_flows\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para entidades AgentFlowExecution.
 *
 * PROPOSITO:
 * Gestiona permisos de lectura y eliminacion de registros de ejecucion.
 *
 * LOGICA:
 * - view: requiere 'view agent flow executions'
 * - delete: requiere 'administer agent flows'
 */
class AgentFlowExecutionAccessControlHandler extends DefaultEntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    // TENANT-ISOLATION-ACCESS-001: Tenant isolation via parent.
    $parentResult = parent::checkAccess($entity, $operation, $account);
    if ($parentResult->isForbidden()) {
      return $parentResult;
    }

    return match ($operation) {
      'view' => AccessResult::allowedIfHasPermission($account, 'view agent flow executions'),
      'update', 'delete' => AccessResult::allowedIfHasPermission($account, 'administer agent flows'),
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
