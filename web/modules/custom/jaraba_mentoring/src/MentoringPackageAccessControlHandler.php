<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for Mentoring Package entity.
 */
class MentoringPackageAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
    {
        // Admin bypasses all.
        if ($account->hasPermission('administer mentoring packages')) {
            return AccessResult::allowed();
        }

        return match ($operation) {
            'view' => AccessResult::allowedIfHasPermission($account, 'view mentoring packages'),
            'update', 'delete' => AccessResult::allowedIfHasPermission($account, 'manage own packages')
                ->andIf(AccessResult::allowedIf($entity->get('mentor_id')->entity?->getOwnerId() === $account->id())),
            default => AccessResult::neutral(),
        };
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL)
    {
        return AccessResult::allowedIfHasPermissions($account, ['administer mentoring packages', 'manage own packages'], 'OR');
    }

}
