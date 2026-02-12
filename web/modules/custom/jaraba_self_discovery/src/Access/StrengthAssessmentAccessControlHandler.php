<?php

declare(strict_types=1);

namespace Drupal\jaraba_self_discovery\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * AccessControlHandler para StrengthAssessment.
 */
class StrengthAssessmentAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface
    {
        if ($account->hasPermission('administer self discovery')) {
            return AccessResult::allowed()->cachePerPermissions();
        }

        switch ($operation) {
            case 'view':
                if ($entity->getOwnerId() === $account->id()) {
                    return AccessResult::allowedIfHasPermission($account, 'view own self discovery results')
                        ->cachePerUser()
                        ->addCacheableDependency($entity);
                }
                return AccessResult::allowedIfHasPermission($account, 'view any self discovery results');

            case 'update':
            case 'delete':
                if ($entity->getOwnerId() === $account->id()) {
                    return AccessResult::allowed()->cachePerUser()->addCacheableDependency($entity);
                }
                return AccessResult::forbidden();
        }

        return AccessResult::neutral();
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResultInterface
    {
        return AccessResult::allowedIfHasPermission($account, 'access self discovery tools');
    }

}
