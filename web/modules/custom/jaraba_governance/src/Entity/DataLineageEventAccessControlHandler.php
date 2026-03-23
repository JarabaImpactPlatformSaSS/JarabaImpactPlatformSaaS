<?php

declare(strict_types=1);

namespace Drupal\jaraba_governance\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResultInterface;

/**
 * Access control handler for DataLineageEvent entities.
 *
 * APPEND-ONLY: update and delete operations are ALWAYS denied.
 * Lineage events form an immutable audit trail.
 */
class DataLineageEventAccessControlHandler extends DefaultEntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    // TENANT-ISOLATION-ACCESS-001: Tenant isolation via parent.
    $parentResult = parent::checkAccess($entity, $operation, $account);
    if ($parentResult->isForbidden()) {
      return $parentResult;
    }

    // APPEND-ONLY: deny update and delete unconditionally.
    if (in_array($operation, ['update', 'delete'], TRUE)) {
      return AccessResult::forbidden('Data lineage events are append-only and cannot be modified or deleted.');
    }

    if ($account->hasPermission('administer data governance')) {
      return AccessResult::allowed();
    }

    if ($operation === 'view' && $account->hasPermission('view data lineage')) {
      return AccessResult::allowed();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    // Creating lineage events is allowed for any user with lineage or admin permission.
    return AccessResult::allowedIfHasPermission($account, 'view data lineage')
      ->orIf(AccessResult::allowedIfHasPermission($account, 'administer data governance'));
  }

}
