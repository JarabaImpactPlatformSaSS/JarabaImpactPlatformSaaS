<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for Mentoring Engagement entity.
 */
class MentoringEngagementAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
    {
        // Admin bypasses all.
        if ($account->hasPermission('manage engagements')) {
            return AccessResult::allowed();
        }

        // Check if user is the mentee (owner).
        $is_mentee = $entity->get('mentee_id')->target_id === $account->id();
        // Check if user is the mentor.
        $mentor = $entity->get('mentor_id')->entity;
        $is_mentor = $mentor && $mentor->getOwnerId() === $account->id();

        return match ($operation) {
            'view' => AccessResult::allowedIf($is_mentee || $is_mentor),
            'update' => AccessResult::allowedIf($is_mentor)->orIf(AccessResult::allowedIfHasPermission($account, 'manage engagements')),
            'delete' => AccessResult::allowedIfHasPermission($account, 'manage engagements'),
            default => AccessResult::neutral(),
        };
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL)
    {
        return AccessResult::allowedIfHasPermissions($account, ['manage engagements', 'view mentoring packages'], 'OR');
    }

}
