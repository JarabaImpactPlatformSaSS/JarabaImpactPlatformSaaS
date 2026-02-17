<?php

namespace Drupal\jaraba_comercio_conecta\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad WishlistItem.
 *
 * Estructura: Extiende EntityAccessControlHandler delegando
 *   la verificación de acceso al wishlist padre.
 *
 * Lógica: Carga el wishlist referenciado por el item y comprueba
 *   si el uid del wishlist coincide con el usuario actual.
 */
class WishlistItemAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer customer profiles')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $wishlist_id = $entity->get('wishlist_id')->target_id;
    if (!$wishlist_id) {
      return AccessResult::neutral()->addCacheableDependency($entity);
    }

    $wishlist = \Drupal::entityTypeManager()
      ->getStorage('comercio_wishlist')
      ->load($wishlist_id);

    if ($wishlist && (int) $wishlist->getOwnerId() === (int) $account->id()) {
      return AccessResult::allowed()->addCacheableDependency($entity)->addCacheableDependency($wishlist)->cachePerUser();
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
