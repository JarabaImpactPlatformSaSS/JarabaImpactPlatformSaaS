<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 *
 */
class IncidentTicketAccessControlHandler extends DefaultEntityAccessControlHandler {

  /**
   *
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    // TENANT-ISOLATION-ACCESS-001: Tenant isolation via parent.
    $parentResult = parent::checkAccess($entity, $operation, $account);
    if ($parentResult->isForbidden()) {
      return $parentResult;
    }

    if ($account->hasPermission('manage comercio incidents')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return $this->checkOwnership($entity, $account);

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'manage comercio incidents');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'manage comercio incidents');
    }

    return AccessResult::neutral();
  }

  /**
   *
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'manage comercio incidents',
      'create comercio incidents',
    ], 'OR');
  }

  /**
   *
   */
  protected function checkOwnership(EntityInterface $entity, AccountInterface $account): AccessResult {
    $owner_id = $entity->get('uid')->target_id ?? NULL;
    if (!$owner_id) {
      return AccessResult::neutral()->addCacheableDependency($entity);
    }

    $is_owner = (int) $owner_id === (int) $account->id();
    return AccessResult::allowedIf(
      $is_owner && $account->hasPermission('view own comercio incidents')
    )->addCacheableDependency($entity)->cachePerUser();
  }

}
