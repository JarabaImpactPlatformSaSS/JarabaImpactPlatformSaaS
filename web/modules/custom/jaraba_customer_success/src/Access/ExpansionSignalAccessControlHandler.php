<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Access;

use Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Control de acceso para ExpansionSignal.
 *
 * LÓGICA:
 * - View/Update: requiere 'manage expansion signals'.
 * - Delete: requiere 'administer customer success'.
 * - Create: solo el sistema vía detección automática.
 */
class ExpansionSignalAccessControlHandler extends DefaultEntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    // TENANT-ISOLATION-ACCESS-001: Tenant isolation via parent.
    $parentResult = parent::checkAccess($entity, $operation, $account);
    if ($parentResult->isForbidden()) {
      return $parentResult;
    }

    return match($operation) {
      'view', 'update' => AccessResult::allowedIfHasPermission($account, 'manage expansion signals'),
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
