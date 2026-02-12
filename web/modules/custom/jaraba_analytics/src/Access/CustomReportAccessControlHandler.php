<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad CustomReport.
 *
 * PROPÓSITO:
 * Gestiona permisos para los informes personalizados de analytics.
 *
 * LÓGICA:
 * - view: requiere 'access jaraba analytics'
 * - create/update/delete: requiere 'administer jaraba analytics'
 */
class CustomReportAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    return match ($operation) {
      'view' => AccessResult::allowedIfHasPermission($account, 'access jaraba analytics'),
      'update', 'delete' => AccessResult::allowedIfHasPermission($account, 'administer jaraba analytics'),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'administer jaraba analytics');
  }

}
