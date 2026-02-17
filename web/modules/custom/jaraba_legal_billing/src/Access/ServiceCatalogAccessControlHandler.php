<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_billing\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para ServiceCatalogItem.
 *
 * Estructura: Extiende EntityAccessControlHandler.
 * Logica: 'manage service catalog' = CRUD. Propietario puede gestionar.
 */
class ServiceCatalogAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer billing')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $isOwner = $entity->hasField('provider_id') && $entity->get('provider_id')->target_id == $account->id();

    switch ($operation) {
      case 'view':
        if ($account->hasPermission('manage service catalog') || $account->hasPermission('access billing')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        if ($isOwner) {
          return AccessResult::allowed()->addCacheableDependency($entity)->cachePerUser();
        }
        return AccessResult::neutral();

      case 'update':
      case 'delete':
        if ($account->hasPermission('manage service catalog')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        if ($isOwner) {
          return AccessResult::allowed()->addCacheableDependency($entity)->cachePerUser();
        }
        return AccessResult::neutral();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer billing',
      'manage service catalog',
    ], 'OR');
  }

}
