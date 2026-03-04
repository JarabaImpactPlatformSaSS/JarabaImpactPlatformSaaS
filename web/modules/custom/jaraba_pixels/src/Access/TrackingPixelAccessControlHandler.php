<?php

declare(strict_types=1);

namespace Drupal\jaraba_pixels\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad TrackingPixel.
 *
 * Permisos:
 * - administer pixels: acceso completo (admin).
 * - view pixels: solo lectura.
 * - manage pixels: actualizar y eliminar.
 */
class TrackingPixelAccessControlHandler extends DefaultEntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    // TENANT-ISOLATION-ACCESS-001: Tenant isolation via parent.
    $parentResult = parent::checkAccess($entity, $operation, $account);
    if ($parentResult->isForbidden()) {
      return $parentResult;
    }

    if ($account->hasPermission('administer pixels')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view pixels')
          ->addCacheableDependency($entity)
          ->cachePerUser();

      case 'update':
      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'manage pixels')
          ->addCacheableDependency($entity)
          ->cachePerUser();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, ['administer pixels', 'manage pixels'], 'OR');
  }

}
