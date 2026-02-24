<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_billing\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para Quote, QuoteLineItem y ServiceCatalogItem.
 *
 * Estructura: Extiende EntityAccessControlHandler con permisos por operacion.
 * Logica: 'manage quotes' o 'manage service catalog' = CRUD.
 *   Propietario (provider_id) puede gestionar sus propios presupuestos.
 */
class QuoteAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer billing')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $isOwner = FALSE;
    if ($entity->hasField('provider_id') && (int) $entity->get('provider_id')->target_id === (int) $account->id()) {
      $isOwner = TRUE;
    }
    elseif ($entity->hasField('uid') && (int) $entity->get('uid')->target_id === (int) $account->id()) {
      $isOwner = TRUE;
    }

    switch ($operation) {
      case 'view':
        if ($account->hasPermission('manage quotes') || $account->hasPermission('manage service catalog') || $account->hasPermission('access billing')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        if ($isOwner) {
          return AccessResult::allowed()->addCacheableDependency($entity)->cachePerUser();
        }
        return AccessResult::neutral();

      case 'update':
        if ($account->hasPermission('manage quotes') || $account->hasPermission('manage service catalog')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        if ($isOwner) {
          return AccessResult::allowed()->addCacheableDependency($entity)->cachePerUser();
        }
        return AccessResult::neutral();

      case 'delete':
        if ($account->hasPermission('manage quotes') || $account->hasPermission('manage service catalog')) {
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
      'manage quotes',
      'manage service catalog',
    ], 'OR');
  }

}
