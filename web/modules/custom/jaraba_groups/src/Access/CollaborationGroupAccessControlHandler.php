<?php

declare(strict_types=1);

namespace Drupal\jaraba_groups\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for Collaboration Group entities.
 */
class CollaborationGroupAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
    {
        if ($account->hasPermission('administer collaboration groups')) {
            return AccessResult::allowed()->cachePerPermissions();
        }

        /** @var \Drupal\jaraba_groups\Entity\CollaborationGroup $entity */
        switch ($operation) {
            case 'view':
                $visibility = $entity->getVisibility();
                if ($visibility === 'public') {
                    return AccessResult::allowed()->addCacheableDependency($entity);
                }
                if ($visibility === 'members_only') {
                    return AccessResult::allowedIfHasPermission($account, 'view member only groups');
                }
                return AccessResult::forbidden();

            case 'update':
                if ($entity->getOwnerId() == $account->id()) {
                    return AccessResult::allowed()->cachePerUser()->addCacheableDependency($entity);
                }
                return AccessResult::allowedIfHasPermission($account, 'edit any group');

            case 'delete':
                if ($entity->getOwnerId() == $account->id()) {
                    return AccessResult::allowed()->cachePerUser()->addCacheableDependency($entity);
                }
                return AccessResult::allowedIfHasPermission($account, 'delete any group');
        }

        return AccessResult::neutral();
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL)
    {
        return AccessResult::allowedIfHasPermission($account, 'create collaboration groups');
    }

}
