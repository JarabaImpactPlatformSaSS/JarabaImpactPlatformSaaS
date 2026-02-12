<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad EntrepreneurLearning.
 *
 * Administradores con 'administer entrepreneur learnings' tienen acceso total.
 * Usuarios con 'view own entrepreneur learnings' pueden ver sus propios aprendizajes.
 */
class EntrepreneurLearningAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    /** @var \Drupal\jaraba_copilot_v2\Entity\EntrepreneurLearning $entity */
    if ($account->hasPermission('administer entrepreneur learnings')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        if ((int) $entity->getOwnerId() === (int) $account->id()) {
          return AccessResult::allowedIfHasPermission($account, 'view own entrepreneur learnings')
            ->cachePerUser()
            ->addCacheableDependency($entity);
        }
        return AccessResult::neutral();

      case 'update':
      case 'delete':
        if ((int) $entity->getOwnerId() === (int) $account->id()) {
          return AccessResult::allowedIfHasPermission($account, 'create entrepreneur learnings')
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
      'administer entrepreneur learnings',
      'create entrepreneur learnings',
    ], 'OR');
  }

}
