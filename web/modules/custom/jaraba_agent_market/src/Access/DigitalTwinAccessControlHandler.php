<?php

declare(strict_types=1);

namespace Drupal\jaraba_agent_market\Access;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Control de acceso para Digital Twin.
 */
class DigitalTwinAccessControlHandler extends EntityAccessControlHandler {

  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($account->hasPermission('administer jaraba agent market')) {
      return AccessResult::allowed();
    }

    $isOwner = ($entity->getOwnerId() == $account->id());

    return $isOwner ? AccessResult::allowed() : AccessResult::neutral();
  }

  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    // Cualquier usuario autenticado puede crear un gemelo.
    return AccessResult::allowedIf($account->isAuthenticated());
  }

}
