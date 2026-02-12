<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Access;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Control de acceso para PlaybookExecution.
 *
 * LÓGICA:
 * - View: requiere 'manage playbooks'.
 * - Update: requiere 'manage playbooks' (para cancelar ejecuciones).
 * - Delete: requiere 'administer customer success'.
 */
class PlaybookExecutionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    return match($operation) {
      'view', 'update' => AccessResult::allowedIfHasPermission($account, 'manage playbooks'),
      'delete' => AccessResult::allowedIfHasPermission($account, 'administer customer success'),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'administer customer success');
  }

}
