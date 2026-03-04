<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad CohortDefinition.
 *
 * PROPÓSITO:
 * Gestiona permisos para crear, ver, editar y eliminar definiciones de cohorte.
 *
 * LÓGICA:
 * - view: requiere 'access jaraba analytics'
 * - update: requiere 'access jaraba analytics'
 * - delete: requiere 'access jaraba analytics'
 * - create: requiere 'administer jaraba analytics'
 */
class CohortDefinitionAccessControlHandler extends DefaultEntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
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
