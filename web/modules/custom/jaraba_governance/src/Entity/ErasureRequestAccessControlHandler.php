<?php

declare(strict_types=1);

namespace Drupal\jaraba_governance\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for ErasureRequest entities.
 */
class ErasureRequestAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\jaraba_governance\Entity\ErasureRequestInterface $entity */

    if ($account->hasPermission('administer data governance')) {
      return AccessResult::allowed();
    }

    switch ($operation) {
      case 'view':
        // Users can view their own requests.
        if ($account->hasPermission('export user data') &&
            (int) $account->id() === $entity->getRequesterId()) {
          return AccessResult::allowed();
        }
        if ($account->hasPermission('process erasure requests')) {
          return AccessResult::allowed();
        }
        break;

      case 'update':
        if ($account->hasPermission('process erasure requests')) {
          return AccessResult::allowed();
        }
        break;

      case 'delete':
        // Only admins can delete erasure requests.
        return AccessResult::neutral();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'export user data')
      ->orIf(AccessResult::allowedIfHasPermission($account, 'administer data governance'));
  }

}
