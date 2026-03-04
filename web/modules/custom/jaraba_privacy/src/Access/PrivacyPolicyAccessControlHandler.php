<?php

declare(strict_types=1);

namespace Drupal\jaraba_privacy\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para PrivacyPolicy.
 *
 * LÓGICA:
 * - Administradores de privacidad: acceso completo.
 * - Usuarios con 'view privacy dashboard': solo lectura.
 * - Las políticas activas son visibles públicamente (sin auth).
 *
 * Spec: Doc 183 §3.2. Plan: FASE 1, Stack Compliance Legal N1.
 */
class PrivacyPolicyAccessControlHandler extends DefaultEntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    // TENANT-ISOLATION-ACCESS-001: Tenant isolation via parent.
    $parentResult = parent::checkAccess($entity, $operation, $account);
    if ($parentResult->isForbidden()) {
      return $parentResult;
    }

    if ($account->hasPermission('administer privacy')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view privacy dashboard')
          ->cachePerPermissions();

      case 'update':
      case 'delete':
        return AccessResult::neutral()->cachePerPermissions();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'administer privacy')
      ->cachePerPermissions();
  }

}
