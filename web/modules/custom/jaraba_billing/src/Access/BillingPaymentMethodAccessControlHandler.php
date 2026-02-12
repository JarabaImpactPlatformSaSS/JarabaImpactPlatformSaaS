<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad BillingPaymentMethod.
 *
 * Administradores con 'administer billing' tienen acceso completo.
 * Usuarios con 'manage payment methods' pueden gestionar métodos de su tenant.
 */
class BillingPaymentMethodAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer billing')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'manage payment methods')
          ->addCacheableDependency($entity)
          ->cachePerUser();

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'manage payment methods')
          ->addCacheableDependency($entity)
          ->cachePerUser();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer billing',
      'manage payment methods',
    ], 'OR');
  }

}
