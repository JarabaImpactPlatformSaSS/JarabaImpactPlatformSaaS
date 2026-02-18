<?php

declare(strict_types=1);

namespace Drupal\jaraba_identity\Access;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Control de acceso para Identity Wallet.
 */
class IdentityWalletAccessControlHandler extends EntityAccessControlHandler {

  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\jaraba_identity\Entity\IdentityWallet $entity */

    // Admin global puede todo.
    if ($account->hasPermission('administer jaraba identity')) {
      return AccessResult::allowed();
    }

    // Dueño de la wallet.
    $isOwner = ($entity->getOwnerId() == $account->id());

    switch ($operation) {
      case 'view':
        return $isOwner 
          ? AccessResult::allowedIfHasPermission($account, 'view own identity wallet')
          : AccessResult::neutral();

      case 'update':
      case 'delete':
        return $isOwner
          ? AccessResult::allowedIfHasPermission($account, 'manage own identity wallet')
          : AccessResult::neutral();
    }

    return AccessResult::neutral();
  }

  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'manage own identity wallet');
  }

}
