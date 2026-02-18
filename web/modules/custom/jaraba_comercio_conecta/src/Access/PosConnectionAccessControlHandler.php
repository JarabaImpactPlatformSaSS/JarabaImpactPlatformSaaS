<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

class PosConnectionAccessControlHandler extends EntityAccessControlHandler {

  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('manage comercio pos')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
      case 'update':
      case 'delete':
        return $this->checkMerchantOwnership($entity, $account);
    }

    return AccessResult::neutral();
  }

  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'manage comercio pos',
      'create own comercio pos connections',
    ], 'OR');
  }

  protected function checkMerchantOwnership(EntityInterface $entity, AccountInterface $account): AccessResult {
    $merchant_id = $entity->get('merchant_id')->target_id ?? NULL;
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
      $is_owner && $account->hasPermission('manage own comercio pos connections')
    )->addCacheableDependency($entity)->addCacheableDependency($merchant)->cachePerUser();
  }

}
