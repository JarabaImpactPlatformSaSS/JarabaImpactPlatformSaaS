<?php

declare(strict_types=1);

namespace Drupal\jaraba_pwa\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for PendingSyncAction entities.
 *
 * Permissions logic:
 * - Admin: full access with 'administer pwa'.
 * - Own actions: authenticated users can view/delete their own pending actions.
 * - Create: any authenticated user (actions are queued via service worker).
 * - Update: only admin (status transitions are managed by the sync service).
 */
class PendingSyncActionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // Full admin access.
    $adminAccess = AccessResult::allowedIfHasPermission($account, 'administer pwa');
    if ($adminAccess->isAllowed()) {
      return $adminAccess;
    }

    // Check ownership.
    $isOwner = (int) $entity->get('user_id')->target_id === (int) $account->id();

    return match ($operation) {
      'view', 'delete' => $isOwner
        ? AccessResult::allowed()->addCacheableDependency($entity)->cachePerUser()
        : AccessResult::neutral()->cachePerUser(),
      'update' => AccessResult::forbidden('Sync actions are updated only by the sync service.'),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    if ($account->isAuthenticated()) {
      return AccessResult::allowed()->cachePerUser();
    }

    return AccessResult::neutral();
  }

}
