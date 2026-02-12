<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad FunnelDefinition.
 *
 * PROPÓSITO:
 * Gestiona permisos para las definiciones de funnels de conversión.
 *
 * LÓGICA:
 * - view: requiere 'access jaraba analytics'
 * - update: requiere 'access jaraba analytics'
 * - delete: requiere 'access jaraba analytics'
 * - create: requiere 'administer jaraba analytics'
 */
class FunnelDefinitionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    return match ($operation) {
      'view', 'update', 'delete' => AccessResult::allowedIfHasPermission($account, 'access jaraba analytics'),
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
