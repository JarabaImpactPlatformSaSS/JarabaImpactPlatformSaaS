<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

class CartAccessControlHandler extends EntityAccessControlHandler {

  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('manage comercio orders')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $is_owner = (int) $entity->getOwnerId() === (int) $account->id();
    if ($is_owner) {
      return AccessResult::allowed()->addCacheableDependency($entity)->cachePerUser();
    }

    return AccessResult::neutral();
  }

  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowed();
  }

}
