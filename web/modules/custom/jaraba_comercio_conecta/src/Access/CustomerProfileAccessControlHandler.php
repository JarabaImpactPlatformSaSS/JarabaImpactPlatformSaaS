<?php

namespace Drupal\jaraba_comercio_conecta\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad CustomerProfileRetail.
 *
 * Estructura: Extiende EntityAccessControlHandler con lógica
 *   de permisos por operación (view, update, delete).
 *
 * Lógica: Los administradores con 'administer customer profiles' tienen
 *   acceso completo. Los usuarios propietarios pueden ver y actualizar
 *   su propio perfil de cliente.
 */
class CustomerProfileAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer customer profiles')) {
      return AccessResult::allowed()->cachePerPermissions()->addCacheTags(['customer_profile_retail_access']);
    }

    $is_owner = (int) $entity->getOwnerId() === (int) $account->id();

    switch ($operation) {
      case 'view':
        if ($is_owner) {
          return AccessResult::allowed()->addCacheableDependency($entity)->cachePerUser()->addCacheTags(['customer_profile_retail_access']);
        }
        return AccessResult::neutral()->addCacheableDependency($entity)->addCacheTags(['customer_profile_retail_access']);

      case 'update':
        if ($is_owner) {
          return AccessResult::allowed()->addCacheableDependency($entity)->cachePerUser()->addCacheTags(['customer_profile_retail_access']);
        }
        return AccessResult::neutral()->addCacheableDependency($entity)->addCacheTags(['customer_profile_retail_access']);

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'administer customer profiles')->addCacheTags(['customer_profile_retail_access']);
    }

    return AccessResult::neutral()->addCacheTags(['customer_profile_retail_access']);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer customer profiles',
      'create own customer profile',
    ], 'OR');
  }

}
