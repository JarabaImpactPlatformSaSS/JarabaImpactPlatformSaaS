<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para UsageLimitRecord.
 *
 * LÓGICA:
 * - Administradores legales: acceso completo.
 * - Usuarios con 'view legal dashboard': solo lectura.
 * - Usage Limit Records son auto-generados: edición y eliminación limitadas a admin.
 * - Aislamiento multi-tenant via TenantContextService (en queries).
 *
 * Spec: Doc 184 §2. Plan: FASE 5, Stack Compliance Legal N1.
 */
class UsageLimitRecordAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer legal')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view legal dashboard')
          ->cachePerPermissions();

      case 'update':
      case 'delete':
        // Usage limit records son auto-generados, solo admin puede modificar/eliminar.
        return AccessResult::neutral()->cachePerPermissions();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'administer legal')
      ->cachePerPermissions();
  }

}
