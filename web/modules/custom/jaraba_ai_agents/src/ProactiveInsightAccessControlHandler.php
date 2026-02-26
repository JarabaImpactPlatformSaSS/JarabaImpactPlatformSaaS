<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for ProactiveInsight entities.
 *
 * GAP-AUD-010: Insights are visible only to the target user or admins.
 * Follows TENANT-ISOLATION-ACCESS-001 and ACCESS-STRICT-001.
 */
class ProactiveInsightAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
    {
        /** @var \Drupal\jaraba_ai_agents\Entity\ProactiveInsightInterface $entity */

        // Admin has full access.
        if ($account->hasPermission('administer proactive insights')) {
            return AccessResult::allowed()->cachePerPermissions();
        }

        // Tenant isolation (TENANT-ISOLATION-ACCESS-001, ACCESS-STRICT-001).
        if ($entity->hasField('tenant_id')) {
            $entityTenantId = (int) $entity->get('tenant_id')->value;
            if ($entityTenantId > 0) {
                try {
                    if (\Drupal::hasService('ecosistema_jaraba_core.tenant_context')) {
                        $tenantContext = \Drupal::service('ecosistema_jaraba_core.tenant_context');
                        $currentTenant = $tenantContext->getCurrentTenant();
                        if ($currentTenant !== NULL && (int) $currentTenant->id() !== $entityTenantId) {
                            return AccessResult::forbidden('Tenant mismatch.')
                                ->addCacheableDependency($entity)
                                ->cachePerUser();
                        }
                    }
                }
                catch (\Exception $e) {
                    // Log but don't block.
                }
            }
        }

        switch ($operation) {
            case 'view':
                // Only the target user or admin can view.
                $targetUserId = (int) ($entity->get('target_user')->target_id ?? 0);
                if ($targetUserId === (int) $account->id()) {
                    return AccessResult::allowed()
                        ->cachePerUser()
                        ->addCacheableDependency($entity);
                }
                return AccessResult::neutral();

            case 'update':
            case 'delete':
                return AccessResult::allowedIfHasPermission($account, 'administer proactive insights');
        }

        return AccessResult::neutral();
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL)
    {
        return AccessResult::allowedIfHasPermission($account, 'administer proactive insights');
    }

}
