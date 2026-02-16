<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para WhistleblowerReport.
 *
 * LÓGICA:
 * - Administradores legales: acceso completo a lectura y actualización de estado.
 * - Usuarios con 'manage whistleblower reports': ver y actualizar estado/asignación.
 * - Los reportes son de solo lectura una vez creados (protección de integridad).
 * - No se permite eliminar reportes (requisito legal Directiva EU 2019/1937).
 * - Aislamiento multi-tenant via TenantContextService (en queries).
 *
 * Spec: Doc 184 §2. Plan: FASE 5, Stack Compliance Legal N1.
 */
class WhistleblowerReportAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer legal')) {
      // Admin tiene acceso completo excepto delete (requisito legal).
      if ($operation === 'delete') {
        return AccessResult::forbidden()
          ->addCacheableDependency($entity)
          ->cachePerPermissions();
      }
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'manage whistleblower reports')
          ->cachePerPermissions();

      case 'update':
        // Solo actualización de estado/asignación/resolución, no del contenido original.
        return AccessResult::allowedIfHasPermission($account, 'manage whistleblower reports')
          ->cachePerPermissions();

      case 'delete':
        // Nunca se permite eliminar reportes de denuncias (requisito legal).
        return AccessResult::forbidden()
          ->addCacheableDependency($entity)
          ->cachePerPermissions();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, ['administer legal', 'manage whistleblower reports'], 'OR')
      ->cachePerPermissions();
  }

}
