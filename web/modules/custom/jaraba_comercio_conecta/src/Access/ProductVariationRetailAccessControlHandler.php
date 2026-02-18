<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad ProductVariationRetail.
 *
 * Estructura: Extiende EntityAccessControlHandler con verificación
 *   de permisos específicos de ComercioConecta.
 *
 * Lógica: Los administradores con 'manage comercio products' tienen
 *   acceso completo. Los comerciantes con 'edit own comercio products'
 *   solo pueden modificar variaciones de sus propios productos.
 *   Cualquier usuario con 'view comercio products' puede ver las variaciones.
 */
class ProductVariationRetailAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   La entidad ProductVariationRetail a verificar.
   * @param string $operation
   *   La operación: 'view', 'update', 'delete'.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   El usuario que solicita acceso.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Resultado de acceso permitido, denegado o neutral.
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    // Acceso completo para administradores del módulo
    if ($account->hasPermission('manage comercio products')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view comercio products');

      case 'update':
      case 'delete':
        // Los comerciantes pueden editar variaciones de sus propios productos
        if ($account->hasPermission('edit own comercio products')) {
          $product = $entity->get('product_id')->entity;
          if ($product) {
            $owner_id = $product->getOwnerId();
            return AccessResult::allowedIf($owner_id == $account->id())
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
      'manage comercio products',
      'create comercio products',
    ], 'OR');
  }

}
