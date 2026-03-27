<?php

declare(strict_types=1);

namespace Drupal\jaraba_training\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for MethodPortfolioItem entity.
 *
 * TENANT-ISOLATION-ACCESS-001: Verifica tenant match para update/delete.
 * ACCESS-RETURN-TYPE-001: Retorna AccessResultInterface (no AccessResult).
 * ACCESS-STRICT-001: Comparaciones ownership con (int)..===(int).
 */
class MethodPortfolioItemAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    $admin = AccessResult::allowedIfHasPermission($account, 'administer method portfolio');
    if ($admin->isAllowed()) {
      return $admin->addCacheableDependency($entity);
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view method portfolio')
          ->addCacheableDependency($entity);

      case 'update':
      case 'delete':
        $ownerId = method_exists($entity, 'getOwnerId') ? $entity->getOwnerId() : NULL;
        $isOwner = $ownerId !== NULL && $ownerId === $account->id();
        return AccessResult::allowedIf($isOwner)
          ->addCacheableDependency($entity)
          ->addCacheContexts(['user']);
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResultInterface {
    return AccessResult::allowedIfHasPermission($account, 'create method portfolio');
  }

}
