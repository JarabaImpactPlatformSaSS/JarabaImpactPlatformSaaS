<?php

declare(strict_types=1);

namespace Drupal\jaraba_referral\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad Código de Referido.
 *
 * ESTRUCTURA: Extiende EntityAccessControlHandler con lógica
 *   de permisos por operación (view, update, delete).
 *
 * LÓGICA: Los administradores con 'administer referral program' tienen
 *   acceso completo. Los usuarios con 'manage referral codes' pueden
 *   ver y gestionar sus propios códigos. Los usuarios con 'view referral
 *   program' pueden ver los códigos públicos.
 *
 * RELACIONES:
 * - ReferralCodeAccessControlHandler -> ReferralCode entity (controla acceso)
 * - ReferralCodeAccessControlHandler <- Drupal core (invocado por)
 */
class ReferralCodeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer referral program')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        // Los propietarios del código pueden verlo, o quienes tengan permiso de gestión.
        $is_owner = $entity->get('user_id')->target_id == $account->id();
        return AccessResult::allowedIf(
          $is_owner && $account->hasPermission('manage referral codes')
        )->addCacheableDependency($entity)->cachePerUser();

      case 'update':
        // Solo propietarios con permiso de gestión pueden editar.
        $is_owner = $entity->get('user_id')->target_id == $account->id();
        return AccessResult::allowedIf(
          $is_owner && $account->hasPermission('manage referral codes')
        )->addCacheableDependency($entity)->cachePerUser();

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'administer referral program');
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer referral program',
      'manage referral codes',
    ], 'OR');
  }

}
