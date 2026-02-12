<?php

declare(strict_types=1);

namespace Drupal\jaraba_onboarding\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad UserOnboardingProgress.
 */
class UserOnboardingProgressAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer onboarding')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        // Los usuarios pueden ver su propio progreso.
        $progressUserId = $entity->get('user_id')->target_id;
        if ($progressUserId && (int) $progressUserId === (int) $account->id()) {
          return AccessResult::allowedIfHasPermission($account, 'view onboarding progress')
            ->addCacheableDependency($entity)
            ->cachePerUser();
        }
        // Los gestores pueden ver el progreso de todos.
        return AccessResult::allowedIfHasPermission($account, 'manage onboarding')
          ->addCacheableDependency($entity)
          ->cachePerUser();

      case 'update':
      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'manage onboarding')
          ->addCacheableDependency($entity)
          ->cachePerUser();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, ['administer onboarding', 'manage onboarding'], 'OR');
  }

}
