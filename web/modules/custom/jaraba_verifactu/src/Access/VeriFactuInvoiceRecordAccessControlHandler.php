<?php

declare(strict_types=1);

namespace Drupal\jaraba_verifactu\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para VeriFactuInvoiceRecord y VeriFactuTenantConfig.
 *
 * CRITICO: VeriFactuInvoiceRecord es append-only segun RD 1007/2023.
 * Las operaciones update y delete estan PROHIBIDAS para todos los roles,
 * incluyendo administradores. Las anulaciones se registran como nuevos
 * registros con record_type='anulacion'.
 *
 * Tambien se usa para VeriFactuTenantConfig (update permitido),
 * VeriFactuRemisionBatch (read-only).
 *
 * Spec: Doc 179, Seccion 2.1. Plan: FASE 1, entregable F1-5.
 */
class VeriFactuInvoiceRecordAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    $entity_type_id = $entity->getEntityTypeId();

    // Administradores VeriFactu tienen acceso completo de lectura.
    if ($account->hasPermission('administer verifactu')) {
      // Para InvoiceRecord: NUNCA permitir update/delete, incluso admins.
      if ($entity_type_id === 'verifactu_invoice_record') {
        if ($operation === 'update' || $operation === 'delete') {
          return AccessResult::forbidden('VeriFactu invoice records are append-only per RD 1007/2023. Cancellations must be registered as new records with record_type=anulacion.')
            ->addCacheableDependency($entity);
        }
      }
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermissions($account, [
          'view verifactu records',
          'manage verifactu config',
          'manage verifactu remision',
        ], 'OR')->cachePerPermissions();

      case 'update':
        // InvoiceRecord: SIEMPRE prohibido (append-only).
        if ($entity_type_id === 'verifactu_invoice_record') {
          return AccessResult::forbidden('VeriFactu invoice records are append-only per RD 1007/2023.')
            ->addCacheableDependency($entity);
        }
        // TenantConfig: permitido con permiso adecuado.
        if ($entity_type_id === 'verifactu_tenant_config') {
          return AccessResult::allowedIfHasPermission($account, 'manage verifactu config')
            ->cachePerPermissions();
        }
        return AccessResult::neutral();

      case 'delete':
        // InvoiceRecord: SIEMPRE prohibido (append-only).
        if ($entity_type_id === 'verifactu_invoice_record') {
          return AccessResult::forbidden('VeriFactu invoice records are append-only per RD 1007/2023.')
            ->addCacheableDependency($entity);
        }
        // TenantConfig: solo admin.
        return AccessResult::neutral();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer verifactu',
      'create verifactu records',
      'manage verifactu config',
    ], 'OR')->cachePerPermissions();
  }

}
