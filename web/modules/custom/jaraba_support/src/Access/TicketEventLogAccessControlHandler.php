<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for Ticket Event Log (append-only).
 *
 * Event logs are immutable: update and delete operations are always denied.
 * Only admin and agents with analytics permission can view logs.
 */
class TicketEventLogAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($account->hasPermission('administer support system')) {
      // Even admin cannot update/delete logs.
      if (in_array($operation, ['update', 'delete'], TRUE)) {
        return AccessResult::forbidden('Event logs are immutable.')
          ->cachePerPermissions();
      }
      return AccessResult::allowed()->cachePerPermissions();
    }

    // Append-only: never allow update or delete.
    if (in_array($operation, ['update', 'delete'], TRUE)) {
      return AccessResult::forbidden('Event logs are immutable.')
        ->cachePerPermissions();
    }

    if ($operation === 'view') {
      return AccessResult::allowedIfHasPermission($account, 'view support analytics');
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    // Only internal services create logs — no form-based creation.
    return AccessResult::allowedIfHasPermission($account, 'administer support system');
  }

}
