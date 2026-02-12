<?php

declare(strict_types=1);

namespace Drupal\jaraba_security_compliance\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad SecurityAuditLog.
 *
 * PROPOSITO:
 * Gestiona permisos para la entidad inmutable de auditoría.
 *
 * LOGICA:
 * - view: requiere 'view audit log'
 * - delete: requiere 'administer security compliance'
 * - create/update: siempre denegado (entidad inmutable)
 *
 * La inmutabilidad se garantiza bloqueando tanto create como update
 * a nivel de control de acceso. La creación se realiza exclusivamente
 * a través del AuditLogService.
 */
class SecurityAuditLogAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    return match ($operation) {
      'view' => AccessResult::allowedIfHasPermission($account, 'view audit log'),
      'delete' => AccessResult::allowedIfHasPermission($account, 'administer security compliance'),
      'update' => AccessResult::forbidden('Security audit logs are immutable and cannot be updated.'),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::forbidden('Security audit logs cannot be created through the entity API. Use AuditLogService::log() instead.');
  }

}
