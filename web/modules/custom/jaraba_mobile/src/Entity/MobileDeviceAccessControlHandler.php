<?php

declare(strict_types=1);

namespace Drupal\jaraba_mobile\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for the MobileDevice entity.
 *
 * Users can view/update/delete their OWN devices.
 * Users with 'manage mobile devices' can manage all devices.
 * Admin permission grants full access.
 */
class MobileDeviceAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    $admin_permission = $this->entityType->getAdminPermission();

    // Full admin access.
    if ($account->hasPermission($admin_permission)) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    // Permission-based access for managing all devices.
    if ($account->hasPermission('manage mobile devices')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    // Owner-based access: users can manage their own devices.
    /** @var \Drupal\jaraba_mobile\Entity\MobileDevice $entity */
    $is_owner = (int) $entity->getOwnerId() === (int) $account->id();

    switch ($operation) {
      case 'view':
      case 'update':
      case 'delete':
        return AccessResult::allowedIf($is_owner)
          ->addCacheableDependency($entity)
          ->cachePerUser();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    // Any authenticated user can register a device, or admins.
    if ($account->isAuthenticated()) {
      return AccessResult::allowed()->cachePerUser();
    }

    return AccessResult::allowedIfHasPermissions($account, [
      $this->entityType->getAdminPermission(),
      'manage mobile devices',
    ], 'OR');
  }

}
