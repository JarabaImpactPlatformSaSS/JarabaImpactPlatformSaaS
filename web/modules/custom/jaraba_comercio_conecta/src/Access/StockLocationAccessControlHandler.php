<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad StockLocation.
 *
 * Estructura: Permisos basados en 'manage comercio stock' (admin)
 *   y 'view comercio stock' (lectura).
 *
 * Lógica: Solo administradores y comerciantes propietarios pueden
 *   modificar ubicaciones de stock. La vista está disponible para
 *   cualquier usuario con permiso de stock.
 */
class StockLocationAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('manage comercio stock')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view comercio stock');

      case 'update':
      case 'delete':
        // Los comerciantes pueden gestionar sus propias ubicaciones
        if ($account->hasPermission('edit own merchant profile')) {
          $merchant = $entity->get('merchant_id')->entity;
          if ($merchant) {
            $owner_id = $merchant->getOwnerId();
            return AccessResult::allowedIf((int) $owner_id === (int) $account->id())
              ->addCacheableDependency($entity)
              ->cachePerUser();
          }
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
      'manage comercio stock',
      'edit own merchant profile',
    ], 'OR');
  }

}
