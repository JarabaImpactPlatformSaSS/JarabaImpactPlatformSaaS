<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad AnalyticsDashboard.
 *
 * PROPOSITO:
 * Gestiona permisos para crear, ver, editar y eliminar dashboards de analytics.
 *
 * LOGICA:
 * - view: requiere 'access jaraba analytics' o 'manage analytics dashboards'
 * - update/delete: requiere 'manage analytics dashboards'
 * - create: requiere 'create dashboards'
 */
class AnalyticsDashboardAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    return match ($operation) {
      'view' => AccessResult::allowedIfHasPermissions($account, [
        'access jaraba analytics',
        'manage analytics dashboards',
      ], 'OR'),
      'update', 'delete' => AccessResult::allowedIfHasPermission($account, 'manage analytics dashboards'),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, [
      'create dashboards',
      'manage analytics dashboards',
    ], 'OR');
  }

}
