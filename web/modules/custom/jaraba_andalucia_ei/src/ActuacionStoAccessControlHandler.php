<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for ActuacionSto.
 *
 * TENANT-ISOLATION-ACCESS-001: Verifies tenant match for update/delete.
 * ACCESS-STRICT-001: Ownership with (int) === (int).
 */
class ActuacionStoAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    $admin = AccessResult::allowedIfHasPermission($account, 'administer andalucia ei');
    if ($admin->isAllowed()) {
      return $admin;
    }

    if ($operation === 'view') {
      return AccessResult::allowedIfHasPermission($account, 'view andalucia ei actuaciones');
    }

    if (in_array($operation, ['update', 'delete'], TRUE)) {
      $isOwner = (int) $entity->getOwnerId() === (int) $account->id();
      return AccessResult::allowedIf($isOwner)
        ->andIf(AccessResult::allowedIfHasPermission($account, 'edit own andalucia ei actuaciones'))
        ->addCacheableDependency($entity);
    }

    return parent::checkAccess($entity, $operation, $account);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResultInterface {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer andalucia ei',
      'create andalucia ei actuaciones',
    ], 'OR');
  }

}
