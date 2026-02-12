<?php

declare(strict_types=1);

namespace Drupal\jaraba_matching;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for MatchResult entity.
 */
class MatchResultAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
    {
        switch ($operation) {
            case 'view':
                // Admins can view all
                if ($account->hasPermission('view match results')) {
                    return AccessResult::allowed();
                }
                // Candidates can view their own matches
                if ($account->hasPermission('view own match results')) {
                    $candidate = $entity->get('candidate_id')->entity;
                    if ($candidate && $candidate->getOwnerId() == $account->id()) {
                        return AccessResult::allowed()->cachePerUser();
                    }
                }
                return AccessResult::forbidden();

            case 'delete':
                return AccessResult::allowedIfHasPermission($account, 'administer matching');
        }

        return AccessResult::neutral();
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL)
    {
        // Match results are created programmatically
        return AccessResult::allowedIfHasPermission($account, 'administer matching');
    }

}
