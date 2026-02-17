<?php

namespace Drupal\jaraba_comercio_conecta\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

class CartItemAccessControlHandler extends EntityAccessControlHandler {

  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('manage comercio orders')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $cart_id = $entity->get('cart_id')->target_id;
    if (!$cart_id) {
      return AccessResult::neutral()->addCacheableDependency($entity);
    }

    $cart = \Drupal::entityTypeManager()
      ->getStorage('comercio_cart')
      ->load($cart_id);

    if ($cart && (int) $cart->getOwnerId() === (int) $account->id()) {
      return AccessResult::allowed()->addCacheableDependency($entity)->addCacheableDependency($cart)->cachePerUser();
    }

    return AccessResult::neutral();
  }

  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowed();
  }

}
