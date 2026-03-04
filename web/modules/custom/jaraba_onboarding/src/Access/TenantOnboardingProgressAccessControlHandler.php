<?php

declare(strict_types=1);

namespace Drupal\jaraba_onboarding\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad TenantOnboardingProgress.
 *
 * Fase 5 — Doc 179.
 */
class TenantOnboardingProgressAccessControlHandler extends DefaultEntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    // TENANT-ISOLATION-ACCESS-001: Tenant isolation via parent.
    $parentResult = parent::checkAccess($entity, $operation, $account);
    if ($parentResult->isForbidden()) {
      return $parentResult;
    }

    if ($account->hasPermission('administer onboarding')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        $progressUserId = $entity->get('user_id')->target_id;
        if ($progressUserId && (int) $progressUserId === (int) $account->id()) {
          return AccessResult::allowedIfHasPermission($account, 'view onboarding progress')
            ->addCacheableDependency($entity)
            ->cachePerUser();
        }
        return AccessResult::allowedIfHasPermission($account, 'manage onboarding')
          ->addCacheableDependency($entity)
          ->cachePerUser();

      case 'update':
        // El usuario del wizard puede actualizar su propio progreso.
        $progressUserId = $entity->get('user_id')->target_id;
        if ($progressUserId && (int) $progressUserId === (int) $account->id()) {
          return AccessResult::allowedIfHasPermission($account, 'view onboarding progress')
            ->addCacheableDependency($entity)
            ->cachePerUser();
        }
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
    return AccessResult::allowedIfHasPermissions($account, ['administer onboarding', 'view onboarding progress'], 'OR');
  }

}
