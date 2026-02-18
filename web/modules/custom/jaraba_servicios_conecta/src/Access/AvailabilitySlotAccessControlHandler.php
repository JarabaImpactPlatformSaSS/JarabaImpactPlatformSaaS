<?php

declare(strict_types=1);

namespace Drupal\jaraba_servicios_conecta\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad AvailabilitySlot.
 */
class AvailabilitySlotAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('manage servicios providers')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
      case 'update':
        $is_owner = $entity->getOwnerId() == $account->id();
        return AccessResult::allowedIf(
          $is_owner && $account->hasPermission('manage servicios availability')
        )->addCacheableDependency($entity)->cachePerUser();

      case 'delete':
        $is_owner = $entity->getOwnerId() == $account->id();
        return AccessResult::allowedIf(
          $is_owner && $account->hasPermission('manage servicios availability')
        )->addCacheableDependency($entity)->cachePerUser();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'manage servicios providers',
      'manage servicios availability',
    ], 'OR');
  }

}
