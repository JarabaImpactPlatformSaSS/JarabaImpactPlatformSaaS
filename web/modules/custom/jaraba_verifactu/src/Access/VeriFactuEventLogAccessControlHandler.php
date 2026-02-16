<?php

declare(strict_types=1);

namespace Drupal\jaraba_verifactu\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para VeriFactuEventLog (SIF).
 *
 * CRITICO: El log de eventos SIF es append-only segun RD 1007/2023.
 * Las operaciones update y delete estan PROHIBIDAS para todos los roles,
 * incluyendo administradores. El log es inmutable e irrefutable.
 *
 * Solo se permite lectura para usuarios con el permiso adecuado.
 * La creacion es programatica (via VeriFactuEventLogService).
 *
 * Spec: Doc 179, Seccion 2.2. Plan: FASE 1, entregable F1-5.
 */
class VeriFactuEventLogAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermissions($account, [
          'administer verifactu',
          'view verifactu event log',
        ], 'OR')->cachePerPermissions();

      case 'update':
      case 'delete':
        // SIEMPRE prohibido. El log SIF es inmutable (append-only).
        return AccessResult::forbidden('VeriFactu event log entries are immutable (append-only) per RD 1007/2023. No update or delete operations are permitted.')
          ->addCacheableDependency($entity);
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    // La creacion es programatica, pero requiere permisos de administracion.
    return AccessResult::allowedIfHasPermission($account, 'administer verifactu')
      ->cachePerPermissions();
  }

}
