<?php

namespace Drupal\jaraba_referral\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad Referral.
 *
 * ESTRUCTURA: Extiende EntityAccessControlHandler con lógica
 *   de permisos por operación (view, update, delete).
 *
 * LÓGICA: Los administradores con 'administer referral settings' tienen
 *   acceso completo. Los usuarios con 'view referral dashboard' pueden
 *   ver sus propios referidos. Solo managers pueden modificar recompensas.
 *
 * RELACIONES:
 * - ReferralAccessControlHandler -> Referral entity (controla acceso)
 * - ReferralAccessControlHandler <- Drupal core (invocado por)
 */
class ReferralAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer referral settings')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        // Los usuarios pueden ver sus propios referidos.
        $is_referrer = $entity->get('referrer_uid')->target_id == $account->id();
        return AccessResult::allowedIf(
          $is_referrer && $account->hasPermission('view referral dashboard')
        )->addCacheableDependency($entity)->cachePerUser();

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'manage referral rewards');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'manage referral rewards');
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer referral settings',
      'create referral codes',
    ], 'OR');
  }

}
