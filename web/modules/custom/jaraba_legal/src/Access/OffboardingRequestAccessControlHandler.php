<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para OffboardingRequest.
 *
 * LÓGICA:
 * - Administradores legales: acceso completo.
 * - Usuarios con 'manage offboarding': crear, ver y editar.
 * - Usuarios con 'view legal dashboard': solo lectura.
 * - Eliminación restringida a administradores.
 * - Aislamiento multi-tenant via TenantContextService (en queries).
 *
 * Spec: Doc 184 §2. Plan: FASE 5, Stack Compliance Legal N1.
 */
class OffboardingRequestAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer legal')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermissions($account, ['view legal dashboard', 'manage offboarding'], 'OR')
          ->cachePerPermissions();

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'manage offboarding')
          ->cachePerPermissions();

      case 'delete':
        return AccessResult::neutral()->cachePerPermissions();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, ['administer legal', 'manage offboarding'], 'OR')
      ->cachePerPermissions();
  }

}
