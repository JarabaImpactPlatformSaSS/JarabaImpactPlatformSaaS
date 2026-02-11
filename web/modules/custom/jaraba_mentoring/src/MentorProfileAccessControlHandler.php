<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the Mentor Profile entity.
 */
class MentorProfileAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
    {
        /** @var \Drupal\jaraba_mentoring\Entity\MentorProfile $entity */

        // Admin always has access.
        if ($account->hasPermission('administer mentor profiles')) {
            return AccessResult::allowed()->cachePerPermissions();
        }

        switch ($operation) {
            case 'view':
                // Published mentors can be viewed by anyone with permission.
                if ($entity->get('status')->value === 'active') {
                    return AccessResult::allowedIfHasPermission($account, 'view mentor profiles')
                        ->addCacheableDependency($entity);
                }
                // Owners can view their own profile.
                if ($entity->getOwnerId() === $account->id()) {
                    return AccessResult::allowed()->cachePerUser();
                }
                return AccessResult::forbidden();

            case 'update':
                // Owners can edit their own profile.
                if ($entity->getOwnerId() === $account->id()) {
                    return AccessResult::allowedIfHasPermission($account, 'edit own mentor profile')
                        ->cachePerUser();
                }
                return AccessResult::forbidden();

            case 'delete':
                // Only admins can delete.
                return AccessResult::forbidden();
        }

        return AccessResult::neutral();
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL)
    {
        return AccessResult::allowedIfHasPermission($account, 'create mentor profile');
    }

}
