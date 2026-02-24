<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_billing\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para LegalInvoice, InvoiceLine y CreditNote.
 *
 * Estructura: Extiende EntityAccessControlHandler con permisos por operacion.
 * Logica: 'administer billing' = acceso total, 'manage invoices' = CRUD,
 *   'access billing' = solo lectura, propietario puede ver sus facturas.
 */
class LegalInvoiceAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer billing')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $isOwner = FALSE;
    if ($entity->hasField('uid') && (int) $entity->get('uid')->target_id === (int) $account->id()) {
      $isOwner = TRUE;
    }
    elseif ($entity->hasField('provider_id') && (int) $entity->get('provider_id')->target_id === (int) $account->id()) {
      $isOwner = TRUE;
    }

    switch ($operation) {
      case 'view':
        if ($account->hasPermission('manage invoices') || $account->hasPermission('access billing')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        if ($isOwner) {
          return AccessResult::allowed()->addCacheableDependency($entity)->cachePerUser();
        }
        return AccessResult::neutral();

      case 'update':
        if ($account->hasPermission('manage invoices')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        if ($isOwner) {
          return AccessResult::allowed()->addCacheableDependency($entity)->cachePerUser();
        }
        return AccessResult::neutral();

      case 'delete':
        if ($account->hasPermission('manage invoices')) {
          return AccessResult::allowed()->cachePerPermissions();
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
      'administer billing',
      'manage invoices',
    ], 'OR');
  }

}
