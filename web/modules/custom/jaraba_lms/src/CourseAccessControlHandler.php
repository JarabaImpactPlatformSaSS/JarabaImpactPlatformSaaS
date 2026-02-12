<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the Course entity.
 */
class CourseAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
    {
        switch ($operation) {
            case 'view':
                if ($entity->get('is_published')->value) {
                    return AccessResult::allowedIfHasPermission($account, 'view published courses');
                }
                return AccessResult::allowedIfHasPermission($account, 'view unpublished courses');

            case 'update':
                return AccessResult::allowedIfHasPermission($account, 'edit courses');

            case 'delete':
                return AccessResult::allowedIfHasPermission($account, 'delete courses');
        }

        return AccessResult::neutral();
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL)
    {
        return AccessResult::allowedIfHasPermission($account, 'create courses')
            ->orIf(AccessResult::allowedIfHasPermission($account, 'access administration pages'));
    }

}
