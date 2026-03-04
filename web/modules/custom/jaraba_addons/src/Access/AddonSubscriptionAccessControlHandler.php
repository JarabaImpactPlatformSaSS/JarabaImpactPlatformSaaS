<?php

namespace Drupal\jaraba_addons\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad AddonSubscription.
 *
 * ESTRUCTURA: Extiende EntityAccessControlHandler con lógica
 *   de permisos por operación (view, update, delete).
 *
 * LÓGICA: Los administradores con 'administer addons settings' tienen
 *   acceso completo. Los usuarios con 'purchase addons' pueden ver
 *   y gestionar sus suscripciones. Solo admins pueden eliminar.
 *
 * RELACIONES:
 * - AddonSubscriptionAccessControlHandler -> AddonSubscription entity (controla acceso)
 * - AddonSubscriptionAccessControlHandler <- Drupal core (invocado por)
 */
class AddonSubscriptionAccessControlHandler extends DefaultEntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    // TENANT-ISOLATION-ACCESS-001: Tenant isolation via parent.
    $parentResult = parent::checkAccess($entity, $operation, $account);
    if ($parentResult->isForbidden()) {
      return $parentResult;
    }

    if ($account->hasPermission('administer addons settings')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermissions($account, [
          'purchase addons',
          'manage addons',
        ], 'OR');

      case 'update':
        return AccessResult::allowedIfHasPermissions($account, [
          'purchase addons',
          'manage addons',
        ], 'OR');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'manage addons');
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer addons settings',
      'purchase addons',
    ], 'OR');
  }

}
