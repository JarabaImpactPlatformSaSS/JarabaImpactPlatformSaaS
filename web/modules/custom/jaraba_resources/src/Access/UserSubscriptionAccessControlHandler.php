<?php

declare(strict_types=1);

namespace Drupal\jaraba_resources\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for User Subscription entities.
 */
class UserSubscriptionAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
    {
        if ($account->hasPermission('administer membership plans')) {
            return AccessResult::allowed()->cachePerPermissions();
        }

        // Users can only view/manage their own subscriptions.
        if ((int) $entity->getOwnerId() === (int) $account->id()) {
            switch ($operation) {
                case 'view':
                    return AccessResult::allowed()->cachePerUser()->addCacheableDependency($entity);

                case 'update':
                    return AccessResult::allowedIfHasPermission($account, 'manage own subscription')
                        ->cachePerUser()
                        ->addCacheableDependency($entity);
            }
        }

        return AccessResult::forbidden()->cachePerUser();
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL)
    {
        return AccessResult::allowedIfHasPermission($account, 'subscribe to plans');
    }

}
