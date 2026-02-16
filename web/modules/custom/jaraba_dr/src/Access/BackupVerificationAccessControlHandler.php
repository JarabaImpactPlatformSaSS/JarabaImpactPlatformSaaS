<?php

declare(strict_types=1);

namespace Drupal\jaraba_dr\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para BackupVerification.
 *
 * LOGICA:
 * - Administradores de DR: acceso completo.
 * - Usuarios con 'view dr dashboard': solo lectura.
 * - Los registros son auto-generados por BackupVerifierService y son
 *   de solo lectura tras su creacion. El update esta restringido.
 *
 * Spec: Doc 185 s4.3. Plan: FASE 9, Stack Compliance Legal N1.
 */
class BackupVerificationAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer dr')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view dr dashboard')
          ->cachePerPermissions();

      case 'update':
        // Registros auto-generados: solo lectura tras creacion.
        return AccessResult::neutral()->cachePerPermissions();

      case 'delete':
        return AccessResult::neutral()->cachePerPermissions();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'administer dr')
      ->cachePerPermissions();
  }

}
