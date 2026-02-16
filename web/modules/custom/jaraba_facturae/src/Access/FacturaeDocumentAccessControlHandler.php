<?php

declare(strict_types=1);

namespace Drupal\jaraba_facturae\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para FacturaeDocument y FacturaeTenantConfig.
 *
 * RESTRICCIONES:
 * - FacturaeDocument: update solo permitido en estado 'draft'.
 *   Una vez validada, firmada o enviada, la factura es inmutable.
 * - FacturaeDocument: delete solo permitido en estado 'draft'.
 * - FacturaeTenantConfig: CRUD completo con permisos adecuados.
 *
 * Spec: Doc 180, Seccion 2.1.
 * Plan: FASE 6, entregable F6-7.
 */
class FacturaeDocumentAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    $entity_type_id = $entity->getEntityTypeId();

    // Administrators have full read access.
    if ($account->hasPermission('administer facturae')) {
      // FacturaeDocument: only draft allows update/delete.
      if ($entity_type_id === 'facturae_document') {
        if ($operation === 'update' || $operation === 'delete') {
          $status = $entity->get('status')->value ?? '';
          if ($status !== 'draft') {
            return AccessResult::forbidden('Facturae documents can only be modified or deleted in draft status. Once validated, signed or sent, they are immutable.')
              ->addCacheableDependency($entity);
          }
        }
      }
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermissions($account, [
          'view facturae documents',
          'view facturae logs',
          'manage facturae config',
        ], 'OR')->cachePerPermissions();

      case 'update':
        if ($entity_type_id === 'facturae_document') {
          $status = $entity->get('status')->value ?? '';
          if ($status !== 'draft') {
            return AccessResult::forbidden('Facturae documents can only be modified in draft status.')
              ->addCacheableDependency($entity);
          }
          return AccessResult::allowedIfHasPermission($account, 'create facturae documents')
            ->cachePerPermissions();
        }
        if ($entity_type_id === 'facturae_tenant_config') {
          return AccessResult::allowedIfHasPermission($account, 'manage facturae config')
            ->cachePerPermissions();
        }
        return AccessResult::neutral();

      case 'delete':
        if ($entity_type_id === 'facturae_document') {
          $status = $entity->get('status')->value ?? '';
          if ($status !== 'draft') {
            return AccessResult::forbidden('Only draft Facturae documents can be deleted.')
              ->addCacheableDependency($entity);
          }
          return AccessResult::allowedIfHasPermission($account, 'delete facturae documents')
            ->cachePerPermissions();
        }
        if ($entity_type_id === 'facturae_tenant_config') {
          return AccessResult::neutral();
        }
        return AccessResult::neutral();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer facturae',
      'create facturae documents',
      'manage facturae config',
    ], 'OR')->cachePerPermissions();
  }

}
