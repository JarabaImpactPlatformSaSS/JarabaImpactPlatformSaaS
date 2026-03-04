<?php

declare(strict_types=1);

namespace Drupal\jaraba_security_compliance\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler;
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
class SecurityPolicyAccessControlHandler extends DefaultEntityAccessControlHandler {

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
