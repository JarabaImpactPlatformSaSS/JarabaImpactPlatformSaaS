<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResultInterface;

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
class FunnelDefinitionAccessControlHandler extends DefaultEntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    // TENANT-ISOLATION-ACCESS-001: Tenant isolation via parent.
    $parentResult = parent::checkAccess($entity, $operation, $account);
    if ($parentResult->isForbidden()) {
      return $parentResult;
    }

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
