<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad AuditLog.
 *
 * PROPÓSITO:
 * Gestiona permisos para la entidad inmutable de auditoría.
 *
 * LÓGICA:
 * - view: requiere 'view audit logs'
 * - delete: requiere 'administer site configuration'
 * - create/update: siempre denegado (entidad inmutable)
 *
 * La inmutabilidad se garantiza bloqueando tanto create como update
 * a nivel de control de acceso, de modo que no se puedan crear
 * registros desde formularios ni modificar los existentes.
 * La creación se realiza exclusivamente a través del AuditLogService.
 */
class AuditLogAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    return match ($operation) {
      'view' => AccessResult::allowedIfHasPermission($account, 'view audit logs'),
      'delete' => AccessResult::allowedIfHasPermission($account, 'administer site configuration'),
      'update' => AccessResult::forbidden('Audit logs are immutable and cannot be updated.'),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::forbidden('Audit logs cannot be created through the entity API. Use AuditLogService::log() instead.');
  }

}
