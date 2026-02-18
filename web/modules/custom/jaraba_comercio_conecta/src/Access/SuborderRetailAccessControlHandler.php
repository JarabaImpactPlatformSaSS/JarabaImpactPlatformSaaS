<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

class SuborderRetailAccessControlHandler extends EntityAccessControlHandler {

  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('manage comercio orders')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $merchant_id = $entity->get('merchant_id')->target_id;
    if (!$merchant_id) {
      return AccessResult::neutral()->addCacheableDependency($entity);
    }

    $merchant = \Drupal::entityTypeManager()
      ->getStorage('merchant_profile')
      ->load($merchant_id);

    if (!$merchant) {
      return AccessResult::neutral()->addCacheableDependency($entity);
    }

    $is_owner = $merchant->getOwnerId() == $account->id();
    return AccessResult::allowedIf(
      $is_owner && $account->hasPermission('edit own comercio orders')
    )->addCacheableDependency($entity)->addCacheableDependency($merchant)->cachePerUser();
  }

  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'manage comercio orders');
  }

}
