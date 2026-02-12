<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad FieldExit.
 *
 * Administradores con 'administer field exits' tienen acceso total.
 * Usuarios con 'view own field exits' pueden ver sus propias salidas de campo.
 */
class FieldExitAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    /** @var \Drupal\jaraba_copilot_v2\Entity\FieldExit $entity */
    if ($account->hasPermission('administer field exits')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        if ((int) $entity->getOwnerId() === (int) $account->id()) {
          return AccessResult::allowedIfHasPermission($account, 'view own field exits')
            ->cachePerUser()
            ->addCacheableDependency($entity);
        }
        return AccessResult::neutral();

      case 'update':
      case 'delete':
        if ((int) $entity->getOwnerId() === (int) $account->id()) {
          return AccessResult::allowedIfHasPermission($account, 'create field exits')
            ->cachePerUser()
            ->addCacheableDependency($entity);
        }
        return AccessResult::neutral();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer field exits',
      'create field exits',
    ], 'OR');
  }

}
