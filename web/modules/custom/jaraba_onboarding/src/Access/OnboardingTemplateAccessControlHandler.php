<?php

declare(strict_types=1);

namespace Drupal\jaraba_onboarding\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad OnboardingTemplate.
 */
class OnboardingTemplateAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer onboarding')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermissions($account, ['manage onboarding', 'view onboarding progress'], 'OR')
          ->addCacheableDependency($entity)
          ->cachePerUser();

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'manage onboarding')
          ->addCacheableDependency($entity)
          ->cachePerUser();

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
