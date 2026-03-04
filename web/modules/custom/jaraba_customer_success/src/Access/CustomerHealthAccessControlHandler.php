<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Access;

use Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;

/**
 * Control de acceso para CustomerHealth.
 *
 * LÓGICA:
 * - View: requiere 'view customer health scores'.
 * - Update/Delete: requiere 'administer customer success'.
 * - Create: solo administradores (el sistema crea vía servicio).
 */
class CustomerHealthAccessControlHandler extends DefaultEntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    // TENANT-ISOLATION-ACCESS-001: Tenant isolation via parent.
    $parentResult = parent::checkAccess($entity, $operation, $account);
    if ($parentResult->isForbidden()) {
      return $parentResult;
    }

    return match($operation) {
      'view' => AccessResult::allowedIfHasPermission($account, 'view customer health scores'),
      'update', 'delete' => AccessResult::allowedIfHasPermission($account, 'administer customer success'),
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
