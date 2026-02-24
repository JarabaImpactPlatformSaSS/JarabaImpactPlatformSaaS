<?php

declare(strict_types=1);

namespace Drupal\jaraba_groups\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for Group Resource entities.
 */
class GroupResourceAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
    {
        if ($account->hasPermission('administer collaboration groups')) {
            return AccessResult::allowed()->cachePerPermissions();
        }

        /** @var \Drupal\jaraba_groups\Entity\GroupResource $entity */
        switch ($operation) {
            case 'view':
                return AccessResult::allowedIfHasPermission($account, 'access group resources');

            case 'update':
            case 'delete':
                if ((int) $entity->get('uploader_id')->target_id === (int) $account->id()) {
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
        return AccessResult::allowedIfHasPermission($account, 'upload group resources');
    }

}
