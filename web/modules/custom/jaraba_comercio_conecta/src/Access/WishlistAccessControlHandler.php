<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad Wishlist.
 *
 * Estructura: Extiende EntityAccessControlHandler con lógica
 *   de permisos por operación (view, update, delete).
 *
 * Lógica: Los propietarios pueden ver, actualizar y eliminar sus propias
 *   listas de deseos. Las listas públicas (visibility = public) son
 *   visibles por cualquier usuario.
 */
class WishlistAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer customer profiles')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $is_owner = (int) $entity->getOwnerId() === (int) $account->id();

    switch ($operation) {
      case 'view':
        // Listas públicas son visibles por cualquiera.
        $visibility = $entity->get('visibility')->value;
        if ($visibility === 'public') {
          return AccessResult::allowed()->addCacheableDependency($entity);
        }
        // Listas privadas: solo el propietario.
        if ($is_owner) {
          return AccessResult::allowed()->addCacheableDependency($entity)->cachePerUser();
        }
        return AccessResult::neutral()->addCacheableDependency($entity);

      case 'update':
        if ($is_owner) {
          return AccessResult::allowed()->addCacheableDependency($entity)->cachePerUser();
        }
        return AccessResult::neutral()->addCacheableDependency($entity);

      case 'delete':
        if ($is_owner) {
          return AccessResult::allowed()->addCacheableDependency($entity)->cachePerUser();
        }
        return AccessResult::neutral()->addCacheableDependency($entity);
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowed();
  }

}
