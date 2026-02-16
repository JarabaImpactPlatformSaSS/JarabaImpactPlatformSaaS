<?php

declare(strict_types=1);

namespace Drupal\jaraba_privacy\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para ProcessingActivity (RAT).
 *
 * LÓGICA:
 * - Administradores de privacidad: acceso completo.
 * - 'view privacy dashboard': solo lectura.
 * - El DPO puede crear y editar actividades de tratamiento.
 *
 * Spec: Doc 183 §5.1. Plan: FASE 1, Stack Compliance Legal N1.
 */
class ProcessingActivityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer privacy')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view privacy dashboard')
          ->cachePerPermissions();

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'manage data rights')
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
    return AccessResult::allowedIfHasPermissions($account, [
      'administer privacy',
      'manage data rights',
    ], 'OR')->cachePerPermissions();
  }

}
