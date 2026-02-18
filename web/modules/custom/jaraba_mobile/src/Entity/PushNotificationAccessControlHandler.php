<?php

declare(strict_types=1);

namespace Drupal\jaraba_mobile\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for the PushNotification entity.
 *
 * CRITICAL: This entity is APPEND-ONLY.
 * - CREATE is allowed with 'send push notifications' permission.
 * - VIEW is allowed with 'view push history' or for the notification recipient.
 * - UPDATE and DELETE are DENIED for all roles (immutable audit trail).
 * - Admin with 'administer jaraba mobile' can view all.
 */
class PushNotificationAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    $admin_permission = $this->entityType->getAdminPermission();

    // Update and delete are ALWAYS denied — append-only pattern.
    if ($operation === 'update' || $operation === 'delete') {
      return AccessResult::forbidden('Push notifications are immutable (append-only audit trail).')
        ->addCacheableDependency($entity);
    }

    // Admin can view all notifications.
    if ($account->hasPermission($admin_permission)) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    // View access.
    if ($operation === 'view') {
      // Users with 'view push history' permission.
      if ($account->hasPermission('view push history')) {
        return AccessResult::allowed()->cachePerPermissions();
      }

      // Recipients can view their own notifications.
      /** @var \Drupal\jaraba_mobile\Entity\PushNotification $entity */
      $recipient_id = $entity->get('recipient_id')->target_id;
      $is_recipient = (int) $recipient_id === (int) $account->id();

      return AccessResult::allowedIf($is_recipient)
        ->addCacheableDependency($entity)
        ->cachePerUser();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      $this->entityType->getAdminPermission(),
      'send push notifications',
    ], 'OR');
  }

}
