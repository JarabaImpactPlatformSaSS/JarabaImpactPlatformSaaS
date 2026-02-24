<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the Enrollment entity.
 */
class EnrollmentAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
    {
        switch ($operation) {
            case 'view':
                // Users can view their own enrollments
                if ((int) $entity->get('user_id')->target_id === (int) $account->id()) {
                    return AccessResult::allowed()->cachePerUser();
                }
                return AccessResult::allowedIfHasPermission($account, 'view enrollments');

            case 'update':
                return AccessResult::allowedIfHasPermission($account, 'edit enrollments');

            case 'delete':
                return AccessResult::allowedIfHasPermission($account, 'delete enrollments');
        }

        return AccessResult::neutral();
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL)
    {
        return AccessResult::allowedIfHasPermission($account, 'create enrollments')
            ->orIf(AccessResult::allowedIfHasPermission($account, 'access administration pages'));
    }

}
