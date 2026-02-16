<?php

declare(strict_types=1);

namespace Drupal\jaraba_facturae\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para FacturaeFaceLog.
 *
 * CRITICO: El log de comunicaciones FACe es append-only.
 * Las operaciones update y delete estan PROHIBIDAS para todos los roles,
 * incluyendo administradores, para mantener trazabilidad completa
 * de las comunicaciones con la Administracion Publica.
 *
 * Spec: Doc 180, Seccion 2.3.
 * Plan: FASE 6, entregable F6-7.
 */
class FacturaeFaceLogAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermissions($account, [
          'administer facturae',
          'view facturae logs',
        ], 'OR')->cachePerPermissions();

      case 'update':
      case 'delete':
        return AccessResult::forbidden('FACe communication log entries are immutable (append-only). No update or delete operations are permitted to ensure complete audit traceability.')
          ->addCacheableDependency($entity);
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'administer facturae')
      ->cachePerPermissions();
  }

}
