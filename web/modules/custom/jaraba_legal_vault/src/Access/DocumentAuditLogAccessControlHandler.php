<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_vault\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad DocumentAuditLog.
 *
 * Estructura: Append-only — prohibe update y delete siempre.
 * Logica: Solo vista para usuarios con permisos de vault.
 *   Jamas se permite editar o eliminar entradas de auditoria.
 */
class DocumentAuditLogAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    // Append-only: jamas permitir update o delete.
    if (in_array($operation, ['update', 'delete'], TRUE)) {
      return AccessResult::forbidden('Audit log entries are immutable.')
        ->addCacheTags(['document_audit_log_list']);
    }

    if ($operation === 'view') {
      if ($account->hasPermission('administer vault') || $account->hasPermission('access vault')) {
        return AccessResult::allowed()->cachePerPermissions();
      }
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    // Solo servicios internos crean entradas de auditoria.
    return AccessResult::allowedIfHasPermission($account, 'administer vault');
  }

}
