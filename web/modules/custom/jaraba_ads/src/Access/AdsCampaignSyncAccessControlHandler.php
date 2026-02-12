<?php

declare(strict_types=1);

namespace Drupal\jaraba_ads\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad AdsCampaignSync.
 *
 * ESTRUCTURA: Extiende EntityAccessControlHandler con lógica
 *   de permisos por operación (view, update, delete).
 *
 * LÓGICA: Los administradores con 'administer ads settings' tienen
 *   acceso completo. Los gestores con 'manage ad campaigns' pueden
 *   crear, editar y eliminar campañas sincronizadas. Los usuarios con
 *   'view ads dashboard' solo pueden ver.
 *
 * RELACIONES:
 * - AdsCampaignSyncAccessControlHandler -> AdsCampaignSync entity (controla acceso)
 * - AdsCampaignSyncAccessControlHandler <- Drupal core (invocado por)
 */
class AdsCampaignSyncAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer ads settings')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermissions($account, [
          'view ads dashboard',
          'manage ad campaigns',
        ], 'OR');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'manage ad campaigns');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'manage ad campaigns');
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer ads settings',
      'manage ad campaigns',
    ], 'OR');
  }

}
