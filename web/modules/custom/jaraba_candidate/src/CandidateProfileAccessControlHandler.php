<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the CandidateProfile entity.
 */
class CandidateProfileAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
    {
        switch ($operation) {
            case 'view':
                // Owners can always view their own profile
                if ($entity->get('user_id')->target_id == $account->id()) {
                    return AccessResult::allowed()->cachePerUser();
                }
                // Public profiles can be viewed
                if ($entity->get('is_public')->value) {
                    return AccessResult::allowedIfHasPermission($account, 'view candidate profiles');
                }
                return AccessResult::allowedIfHasPermission($account, 'view private candidate profiles');

            case 'update':
                // Owners can edit their own profile
                if ($entity->get('user_id')->target_id == $account->id()) {
                    return AccessResult::allowed()->cachePerUser();
                }
                return AccessResult::allowedIfHasPermission($account, 'edit any candidate profiles');

            case 'delete':
                return AccessResult::allowedIfHasPermission($account, 'delete candidate profiles');
        }

        return AccessResult::neutral();
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL)
    {
        return AccessResult::allowedIfHasPermission($account, 'create candidate profile')
            ->orIf(AccessResult::allowedIfHasPermission($account, 'access administration pages'));
    }

}
