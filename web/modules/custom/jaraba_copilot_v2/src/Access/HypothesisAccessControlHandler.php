<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad Hypothesis.
 *
 * Administradores con 'administer hypotheses' tienen acceso total.
 * Usuarios con 'view own hypotheses' pueden ver sus propias hipotesis.
 * Usuarios con 'create hypotheses' pueden crear nuevas.
 */
class HypothesisAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    /** @var \Drupal\jaraba_copilot_v2\Entity\Hypothesis $entity */
    if ($account->hasPermission('administer hypotheses')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        if ((int) $entity->getOwnerId() === (int) $account->id()) {
          return AccessResult::allowedIfHasPermission($account, 'view own hypotheses')
            ->cachePerUser()
            ->addCacheableDependency($entity);
        }
        return AccessResult::neutral();

      case 'update':
        if ((int) $entity->getOwnerId() === (int) $account->id()) {
          return AccessResult::allowedIfHasPermission($account, 'create hypotheses')
            ->cachePerUser()
            ->addCacheableDependency($entity);
        }
        return AccessResult::neutral();

      case 'delete':
        if ((int) $entity->getOwnerId() === (int) $account->id()) {
          return AccessResult::allowedIfHasPermission($account, 'create hypotheses')
            ->cachePerUser()
            ->addCacheableDependency($entity);
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
      'administer hypotheses',
      'create hypotheses',
    ], 'OR');
  }

}
