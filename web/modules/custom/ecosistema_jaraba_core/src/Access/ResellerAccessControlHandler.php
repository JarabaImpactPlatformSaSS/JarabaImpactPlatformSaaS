<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad Reseller.
 *
 * PROPOSITO:
 * Gestiona permisos de lectura, edicion y eliminacion de resellers.
 *
 * LOGICA:
 * - view: requiere 'administer tenants' O 'access partner portal'
 * - create/update/delete: requiere 'administer tenants'
 */
class ResellerAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    return match ($operation) {
      'view' => AccessResult::allowedIfHasPermissions($account, [
        'administer tenants',
        'access partner portal',
      ], 'OR'),
      'update', 'delete' => AccessResult::allowedIfHasPermission($account, 'administer tenants'),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'administer tenants');
  }

}
