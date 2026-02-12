<?php

declare(strict_types=1);

namespace Drupal\jaraba_resources\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for Membership Plan entities.
 */
class MembershipPlanAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
    {
        if ($account->hasPermission('administer membership plans')) {
            return AccessResult::allowed()->cachePerPermissions();
        }

        switch ($operation) {
            case 'view':
                // Public can view active plans.
                if ($entity->get('status')->value === 'active') {
                    return AccessResult::allowed()->addCacheableDependency($entity);
                }
                return AccessResult::forbidden();

            case 'update':
            case 'delete':
                return AccessResult::allowedIfHasPermission($account, 'administer membership plans');
        }

        return AccessResult::neutral();
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL)
    {
        return AccessResult::allowedIfHasPermission($account, 'administer membership plans');
    }

}
