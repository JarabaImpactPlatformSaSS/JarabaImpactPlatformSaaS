<?php

declare(strict_types=1);

namespace Drupal\jaraba_ads\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad AdsMetricsDaily.
 *
 * ESTRUCTURA: Extiende EntityAccessControlHandler con lógica
 *   de permisos por operación (view, update, delete).
 *
 * LÓGICA: Los administradores con 'administer ads settings' tienen
 *   acceso completo. Los usuarios con 'view ads dashboard' pueden
 *   ver métricas. Solo administradores pueden crear/editar/eliminar
 *   registros de métricas directamente.
 *
 * RELACIONES:
 * - AdsMetricsDailyAccessControlHandler -> AdsMetricsDaily entity (controla acceso)
 * - AdsMetricsDailyAccessControlHandler <- Drupal core (invocado por)
 */
class AdsMetricsDailyAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer ads settings')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view ads dashboard');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'administer ads settings');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'administer ads settings');
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'administer ads settings');
  }

}
