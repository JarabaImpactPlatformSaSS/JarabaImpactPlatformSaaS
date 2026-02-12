<?php

declare(strict_types=1);

namespace Drupal\jaraba_insights_hub\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para entidades del Insights Hub.
 *
 * ESTRUCTURA: Extiende EntityAccessControlHandler con logica
 *   de permisos por operacion (view, update, delete).
 *
 * LOGICA: Los administradores con 'administer insights hub' tienen
 *   acceso completo. Los usuarios con 'view insights dashboard' pueden
 *   ver datos. Los usuarios con 'manage uptime checks' pueden gestionar
 *   checks de uptime.
 *
 * RELACIONES:
 * - InsightsAccessControlHandler -> Insights entities (controla acceso)
 * - InsightsAccessControlHandler <- Drupal core (invocado por)
 */
class InsightsAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer insights hub')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermissions($account, [
          'view insights dashboard',
          'view own insights',
        ], 'OR');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'manage uptime checks');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'administer insights hub');
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer insights hub',
      'manage uptime checks',
    ], 'OR');
  }

}
