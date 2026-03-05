<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler para CredentialStack.
 *
 * GAP-H06: Includes plan-based limit check in checkCreateAccess().
 * Verifies that the tenant's plan allows creating more credential stacks
 * via PlanValidator before granting create access.
 */
class CredentialStackAccessControlHandler extends DefaultEntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    // TENANT-ISOLATION-ACCESS-001: Tenant isolation via parent.
    $parentResult = parent::checkAccess($entity, $operation, $account);
    if ($parentResult->isForbidden()) {
      return $parentResult;
    }

    if ($account->hasPermission('administer credentials')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    if ($operation === 'view' && $account->hasPermission('view stack progress')) {
      return AccessResult::allowed()
        ->cachePerPermissions()
        ->addCacheableDependency($entity);
    }

    if ($account->hasPermission('manage credential stacks')) {
      return AccessResult::allowed()
        ->cachePerPermissions()
        ->addCacheableDependency($entity);
    }

    return AccessResult::forbidden()->cachePerPermissions();
  }

  /**
   * {@inheritdoc}
   *
   * GAP-H06: Checks plan limits for credential stacks before allowing creation.
   * Uses FeatureAccessService to verify 'credential_stacks' feature is available,
   * then PlanValidator to check the stacks count limit hasn't been exceeded.
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    $permissionResult = AccessResult::allowedIfHasPermissions($account, [
      'administer credentials',
      'manage credential stacks',
    ], 'OR');

    if (!$permissionResult->isAllowed()) {
      return $permissionResult;
    }

    // GAP-H06: Check plan limit for credential stacks.
    try {
      if (\Drupal::hasService('jaraba_billing.feature_access')
          && \Drupal::hasService('ecosistema_jaraba_core.tenant_context')) {
        $tenantContext = \Drupal::service('ecosistema_jaraba_core.tenant_context');
        $tenant = $tenantContext->getCurrentTenant();
        if ($tenant) {
          $featureAccess = \Drupal::service('jaraba_billing.feature_access');
          if (!$featureAccess->canAccess((int) $tenant->id(), 'credential_stacks')) {
            return AccessResult::forbidden('Plan does not include credential stacks.')
              ->cachePerPermissions()
              ->addCacheContexts(['user']);
          }
        }
      }
    }
    catch (\Throwable $e) {
      // PRESAVE-RESILIENCE-001: Don't block access on service failure.
    }

    return $permissionResult;
  }

}
