<?php

declare(strict_types=1);

namespace Drupal\jaraba_resources\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for Digital Kit entities.
 */
class DigitalKitAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
    {
        if ($account->hasPermission('administer digital kits')) {
            return AccessResult::allowed()->cachePerPermissions();
        }

        /** @var \Drupal\jaraba_resources\Entity\DigitalKit $entity */
        switch ($operation) {
            case 'view':
                // Check if user has access based on subscription level.
                if ($entity->canAccess((int) $account->id())) {
                    return AccessResult::allowed()->cachePerUser()->addCacheableDependency($entity);
                }
                // Free kits are viewable by all authenticated users.
                if ($entity->getAccessLevel() === 'free' && $account->isAuthenticated()) {
                    return AccessResult::allowed()->cachePerUser();
                }
                return AccessResult::forbidden();

            case 'update':
            case 'delete':
                return AccessResult::allowedIfHasPermission($account, 'administer digital kits');
        }

        return AccessResult::neutral();
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL)
    {
        return AccessResult::allowedIfHasPermission($account, 'administer digital kits');
    }

}
