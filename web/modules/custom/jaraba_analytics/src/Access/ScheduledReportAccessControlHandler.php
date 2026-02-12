<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad ScheduledReport.
 *
 * PROPOSITO:
 * Gestiona permisos para crear, ver, editar y eliminar informes programados.
 *
 * LOGICA:
 * - view: requiere 'access jaraba analytics' o 'manage scheduled reports'
 * - update/delete: requiere 'manage scheduled reports'
 * - create: requiere 'manage scheduled reports'
 */
class ScheduledReportAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    return match ($operation) {
      'view' => AccessResult::allowedIfHasPermissions($account, [
        'access jaraba analytics',
        'manage scheduled reports',
      ], 'OR'),
      'update', 'delete' => AccessResult::allowedIfHasPermission($account, 'manage scheduled reports'),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'manage scheduled reports');
  }

}
