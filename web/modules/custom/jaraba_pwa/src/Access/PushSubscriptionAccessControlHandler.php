<?php

declare(strict_types=1);

namespace Drupal\jaraba_pwa\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResultInterface;

/**
 * Access control handler for PushSubscription entities.
 *
 * Permissions logic:
 * - view/delete own: authenticated users can manage their own subscriptions.
 * - view/delete others: requires 'administer pwa' or 'manage push subscriptions'.
 * - create: users with 'receive push notifications' (created via API).
 * - update: always denied (subscriptions are replaced, not edited).
 */
class PushSubscriptionAccessControlHandler extends DefaultEntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    // TENANT-ISOLATION-ACCESS-001: Tenant isolation via parent.
    $parentResult = parent::checkAccess($entity, $operation, $account);
    if ($parentResult->isForbidden()) {
      return $parentResult;
    }

    // Full admin access.
    $adminAccess = AccessResult::allowedIfHasPermissions($account, [
      'administer pwa',
    ]);
    if ($adminAccess->isAllowed()) {
      return $adminAccess;
    }

    // Managers can view and delete any subscription.
    if (in_array($operation, ['view', 'delete'], TRUE)) {
      $managerAccess = AccessResult::allowedIfHasPermission($account, 'manage push subscriptions');
      if ($managerAccess->isAllowed()) {
        return $managerAccess;
      }
    }

    // Check ownership for view/delete operations.
    $isOwner = (int) $entity->get('user_id')->target_id === (int) $account->id();

    return match ($operation) {
      'view', 'delete' => $isOwner
        ? AccessResult::allowed()->addCacheableDependency($entity)->cachePerUser()
        : AccessResult::neutral()->cachePerUser(),
      'update' => AccessResult::forbidden('Push subscriptions cannot be edited. Delete and create a new one.'),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    // Users with push notification permission can create subscriptions.
    return AccessResult::allowedIfHasPermission($account, 'receive push notifications')
      ->cachePerUser();
  }

}
