<?php

declare(strict_types=1);

namespace Drupal\jaraba_privacy\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para CookieConsent.
 *
 * LÓGICA:
 * - Registros de solo lectura (audit trail de consentimiento).
 * - Administradores de privacidad: lectura completa.
 * - 'manage cookie consent': puede ver registros.
 * - La creación se realiza vía CookieConsentManagerService (sin auth del user).
 * - Edición y borrado NO permitidos (registros inmutables).
 *
 * Spec: Doc 183 §4.2. Plan: FASE 1, Stack Compliance Legal N1.
 */
class CookieConsentAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer privacy')) {
      if ($operation === 'update' || $operation === 'delete') {
        return AccessResult::forbidden('Los registros de consentimiento de cookies son inmutables.')
          ->addCacheableDependency($entity);
      }
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermissions($account, [
          'manage cookie consent',
          'view privacy dashboard',
        ], 'OR')->cachePerPermissions();

      case 'update':
      case 'delete':
        return AccessResult::forbidden('Los registros de consentimiento de cookies son inmutables.')
          ->addCacheableDependency($entity);
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer privacy',
      'manage cookie consent',
    ], 'OR')->cachePerPermissions();
  }

}
