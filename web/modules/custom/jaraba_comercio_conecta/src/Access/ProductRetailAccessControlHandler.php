<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad ProductRetail.
 *
 * Estructura: Extiende EntityAccessControlHandler con lógica
 *   de permisos por operación (view, update, delete).
 *
 * Lógica: Los administradores con 'manage comercio products' tienen
 *   acceso completo. Los comerciantes con 'edit own comercio products'
 *   solo pueden editar/ver productos de su propio comercio
 *   (verificación uid del merchant_profile asociado).
 */
class ProductRetailAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('manage comercio products')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        // Productos activos son públicos en el marketplace
        $is_active = $entity->get('status')->value === 'active';
        if ($is_active) {
          return AccessResult::allowed()->addCacheableDependency($entity);
        }
        // Productos no activos: solo el dueño del comercio o admin
        return $this->checkMerchantOwnership($entity, $account);

      case 'update':
        return $this->checkMerchantOwnership($entity, $account);

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'manage comercio products');
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'manage comercio products',
      'edit own comercio products',
    ], 'OR');
  }

  /**
   * Verifica si el usuario es dueño del comercio asociado al producto.
   *
   * Lógica: Obtiene el merchant_profile referenciado por el producto
   *   y comprueba si el uid del merchant coincide con el usuario actual.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   La entidad ProductRetail.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   La cuenta del usuario actual.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Resultado de acceso.
   */
  protected function checkMerchantOwnership(EntityInterface $entity, AccountInterface $account): AccessResult {
    $merchant_id = $entity->get('merchant_id')->target_id;
    if (!$merchant_id) {
      return AccessResult::neutral()->addCacheableDependency($entity);
    }

    $merchant = \Drupal::entityTypeManager()
      ->getStorage('merchant_profile')
      ->load($merchant_id);

    if (!$merchant) {
      return AccessResult::neutral()->addCacheableDependency($entity);
    }

    $is_owner = (int) $merchant->getOwnerId() === (int) $account->id();
    return AccessResult::allowedIf(
      $is_owner && $account->hasPermission('edit own comercio products')
    )->addCacheableDependency($entity)->addCacheableDependency($merchant)->cachePerUser();
  }

}
