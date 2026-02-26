<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for AI Feedback entities.
 *
 * ENTITY-APPEND-001: AI feedback is append-only. Once submitted, feedback
 * records cannot be updated or deleted through normal access. Only admins
 * with 'administer ai agents' can view all feedback; regular users can
 * view only their own submissions.
 *
 * ACCESS-STRICT-001: Uses strict integer comparison for owner checks.
 *
 * FIX-034: AI Feedback entity and endpoint.
 */
class AiFeedbackAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
    {
        /** @var \Drupal\jaraba_ai_agents\Entity\AiFeedback $entity */

        // Admin bypass for all operations.
        if ($account->hasPermission('administer ai agents')) {
            return AccessResult::allowed()->cachePerPermissions();
        }

        // ACCESS-STRICT-001: Strict integer comparison for owner check.
        $isOwner = (int) $entity->get('user_id')->target_id === (int) $account->id();

        switch ($operation) {
            case 'view':
                // Users can view their own feedback only.
                if ($isOwner) {
                    return AccessResult::allowed()
                        ->cachePerUser()
                        ->addCacheableDependency($entity);
                }
                return AccessResult::neutral('AI feedback view restricted to owner')
                    ->cachePerUser()
                    ->addCacheableDependency($entity);

            case 'update':
            case 'delete':
                // ENTITY-APPEND-001: Append-only — update and delete are denied.
                return AccessResult::forbidden('AI feedback is append-only')
                    ->cachePerPermissions();
        }

        return AccessResult::neutral();
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL)
    {
        // Any authenticated user can submit feedback.
        if ($account->isAuthenticated()) {
            return AccessResult::allowed()->cachePerUser();
        }

        return AccessResult::forbidden('Anonymous users cannot submit AI feedback');
    }

}
