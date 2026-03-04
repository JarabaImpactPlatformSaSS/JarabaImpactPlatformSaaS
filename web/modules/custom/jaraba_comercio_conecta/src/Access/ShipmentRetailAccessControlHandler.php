<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

class ShipmentRetailAccessControlHandler extends DefaultEntityAccessControlHandler {

  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    // TENANT-ISOLATION-ACCESS-001: Tenant isolation via parent.
    $parentResult = parent::checkAccess($entity, $operation, $account);
    if ($parentResult->isForbidden()) {
      return $parentResult;
    }

    if ($account->hasPermission('manage comercio shipments')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return $this->checkMerchantOwnership($entity, $account);

      case 'update':
        return $this->checkMerchantOwnership($entity, $account);

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'manage comercio shipments');
    }

    return AccessResult::neutral();
  }

  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'manage comercio shipments',
      'create comercio shipments',
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

    $is_owner = (int) $merchant->getOwnerId() === (int) $account->id();
    return AccessResult::allowedIf(
      $is_owner && $account->hasPermission('view own comercio shipments')
    )->addCacheableDependency($entity)->addCacheableDependency($merchant)->cachePerUser();
  }

}
