<?php

declare(strict_types=1);

namespace Drupal\jaraba_security_compliance\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad SecurityPolicy.
 *
 * PROPOSITO:
 * Gestiona permisos granulares de lectura, edición y eliminación
 * de políticas de seguridad.
 *
 * LOGICA:
 * - view: requiere 'manage security policies' o 'administer security compliance'
 * - update/delete: requiere 'manage security policies'
 * - create: requiere 'manage security policies'
 */
class SecurityPolicyAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    return match ($operation) {
      'view' => AccessResult::allowedIfHasPermissions($account, [
        'administer security compliance',
        'manage security policies',
      ], 'OR'),
      'update', 'delete' => AccessResult::allowedIfHasPermission($account, 'manage security policies'),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'manage security policies');
  }

}
