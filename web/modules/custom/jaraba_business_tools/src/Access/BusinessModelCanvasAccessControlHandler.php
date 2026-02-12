<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools\Access;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access control handler for Business Model Canvas entities.
 */
class BusinessModelCanvasAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
    {
        /** @var \Drupal\jaraba_business_tools\Entity\BusinessModelCanvasInterface $entity */

        // Admin permission grants full access.
        if ($account->hasPermission('administer business model canvas')) {
            return AccessResult::allowed()->cachePerPermissions();
        }

        $isOwner = $entity->getOwnerId() == $account->id();
        $isCollaborator = in_array($account->id(), $entity->getSharedWith());
        $isTemplate = $entity->isTemplate();

        switch ($operation) {
            case 'view':
                // Owner, collaborators, and anyone for templates.
                if ($isOwner || $isCollaborator) {
                    return AccessResult::allowed()->cachePerUser()->addCacheableDependency($entity);
                }
                if ($isTemplate && $account->hasPermission('view any business model canvas')) {
                    return AccessResult::allowed()->cachePerPermissions()->addCacheableDependency($entity);
                }
                if ($account->hasPermission('view any business model canvas')) {
                    return AccessResult::allowed()->cachePerPermissions();
                }
                break;

            case 'update':
                if ($isOwner && $account->hasPermission('edit own business model canvas')) {
                    return AccessResult::allowed()->cachePerUser()->cachePerPermissions();
                }
                if ($isCollaborator && $account->hasPermission('edit own business model canvas')) {
                    return AccessResult::allowed()->cachePerUser()->addCacheableDependency($entity);
                }
                if ($account->hasPermission('edit any business model canvas')) {
                    return AccessResult::allowed()->cachePerPermissions();
                }
                break;

            case 'delete':
                if ($isOwner && $account->hasPermission('delete own business model canvas')) {
                    return AccessResult::allowed()->cachePerUser()->cachePerPermissions();
                }
                if ($account->hasPermission('delete any business model canvas')) {
                    return AccessResult::allowed()->cachePerPermissions();
                }
                break;
        }

        return AccessResult::neutral();
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL)
    {
        return AccessResult::allowedIfHasPermission($account, 'create business model canvas');
    }

}
