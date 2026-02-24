<?php

declare(strict_types=1);

namespace Drupal\jaraba_groups\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for Group Discussion entities.
 */
class GroupDiscussionAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
    {
        if ($account->hasPermission('administer collaboration groups')) {
            return AccessResult::allowed()->cachePerPermissions();
        }

        /** @var \Drupal\jaraba_groups\Entity\GroupDiscussion $entity */
        switch ($operation) {
            case 'view':
                return AccessResult::allowedIfHasPermission($account, 'view group discussions');

            case 'update':
                if ((int) $entity->get('author_id')->target_id === (int) $account->id()) {
                    return AccessResult::allowed()->cachePerUser()->addCacheableDependency($entity);
                }
                return AccessResult::allowedIfHasPermission($account, 'moderate group content');

            case 'delete':
                if ((int) $entity->get('author_id')->target_id === (int) $account->id()) {
                    return AccessResult::allowed()->cachePerUser()->addCacheableDependency($entity);
                }
                return AccessResult::allowedIfHasPermission($account, 'moderate group content');
        }

        return AccessResult::neutral();
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL)
    {
        return AccessResult::allowedIfHasPermission($account, 'create group content');
    }

}
