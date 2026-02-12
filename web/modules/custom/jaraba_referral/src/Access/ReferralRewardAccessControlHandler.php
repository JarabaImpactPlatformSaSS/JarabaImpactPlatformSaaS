<?php

declare(strict_types=1);

namespace Drupal\jaraba_referral\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad Recompensa de Referido.
 *
 * ESTRUCTURA: Extiende EntityAccessControlHandler con lógica
 *   de permisos por operación (view, update, delete).
 *
 * LÓGICA: Los administradores con 'administer referral program' tienen
 *   acceso completo. Los usuarios pueden ver sus propias recompensas
 *   con el permiso 'view referral program'. Solo los managers de
 *   recompensas pueden aprobar, rechazar y procesar pagos.
 *
 * RELACIONES:
 * - ReferralRewardAccessControlHandler -> ReferralReward entity (controla acceso)
 * - ReferralRewardAccessControlHandler <- Drupal core (invocado por)
 */
class ReferralRewardAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer referral program')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        // Los usuarios pueden ver sus propias recompensas.
        $is_owner = $entity->get('user_id')->target_id == $account->id();
        return AccessResult::allowedIf(
          $is_owner && $account->hasPermission('view referral program')
        )->addCacheableDependency($entity)->cachePerUser();

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'manage referral rewards');

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
      'manage referral rewards',
    ], 'OR');
  }

}
