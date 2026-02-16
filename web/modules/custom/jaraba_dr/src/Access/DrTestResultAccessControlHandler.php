<?php

declare(strict_types=1);

namespace Drupal\jaraba_dr\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para DrTestResult.
 *
 * LOGICA:
 * - Administradores de DR: acceso completo.
 * - Usuarios con 'execute dr tests': pueden crear y ver resultados.
 * - Usuarios con 'view dr dashboard': solo lectura.
 *
 * Spec: Doc 185 s4.3. Plan: FASE 9, Stack Compliance Legal N1.
 */
class DrTestResultAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer dr')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermissions($account, ['view dr dashboard', 'execute dr tests'], 'OR')
          ->cachePerPermissions();

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'execute dr tests')
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
    return AccessResult::allowedIfHasPermissions($account, ['administer dr', 'execute dr tests'], 'OR')
      ->cachePerPermissions();
  }

}
