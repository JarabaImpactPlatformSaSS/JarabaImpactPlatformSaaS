<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for CandidateSkill entities.
 */
class CandidateSkillAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
    {
        /** @var \Drupal\jaraba_candidate\Entity\CandidateSkillInterface $entity */

        // Admin can do anything.
        if ($account->hasPermission('administer candidate skills')) {
            return AccessResult::allowed()->cachePerPermissions();
        }

        // Owner can view/edit their own skills.
        $is_owner = $entity->getOwnerId() === $account->id();

        switch ($operation) {
            case 'view':
                if ($is_owner) {
                    return AccessResult::allowed()->cachePerUser()->addCacheableDependency($entity);
                }
                // Public profiles can have skills viewed.
                return AccessResult::allowedIfHasPermission($account, 'view candidate skills');

            case 'update':
            case 'delete':
                if ($is_owner) {
                    return AccessResult::allowed()->cachePerUser()->addCacheableDependency($entity);
                }
                return AccessResult::forbidden();
        }

        return AccessResult::neutral();
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL)
    {
        return AccessResult::allowedIfHasPermissions($account, [
            'administer candidate skills',
            'create own candidate skills',
        ], 'OR');
    }

}
