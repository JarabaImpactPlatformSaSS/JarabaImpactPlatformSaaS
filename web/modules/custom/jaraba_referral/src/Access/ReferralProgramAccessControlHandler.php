<?php

declare(strict_types=1);

namespace Drupal\jaraba_referral\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad Programa de Referidos.
 *
 * ESTRUCTURA: Extiende EntityAccessControlHandler con lógica
 *   de permisos por operación (view, update, delete).
 *
 * LÓGICA: Los administradores con 'administer referral program' tienen
 *   acceso completo. Los usuarios con 'view referral program' pueden
 *   ver los programas. Solo administradores pueden crear, editar y eliminar.
 *
 * RELACIONES:
 * - ReferralProgramAccessControlHandler -> ReferralProgram entity (controla acceso)
 * - ReferralProgramAccessControlHandler <- Drupal core (invocado por)
 */
class ReferralProgramAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer referral program')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view referral program');

      case 'update':
      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'administer referral program');
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'administer referral program');
  }

}
