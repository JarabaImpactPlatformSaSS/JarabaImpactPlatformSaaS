<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

class NapEntryAccessControlHandler extends EntityAccessControlHandler {

  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'administer comercio local seo')
      ->cachePerPermissions();
  }

  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'administer comercio local seo')
      ->cachePerPermissions();
  }

}
