<?php

declare(strict_types=1);

namespace Drupal\jaraba_job_board;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the JobApplication entity.
 */
class JobApplicationAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
    {
        switch ($operation) {
            case 'view':
                // Candidates can view their own applications
                if ((int) $entity->get('candidate_id')->target_id === (int) $account->id()) {
                    return AccessResult::allowed()->cachePerUser();
                }
                return AccessResult::allowedIfHasPermission($account, 'view job applications');

            case 'update':
                return AccessResult::allowedIfHasPermission($account, 'manage job applications');

            case 'delete':
                return AccessResult::allowedIfHasPermission($account, 'delete job applications');
        }

        return AccessResult::neutral();
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL)
    {
        return AccessResult::allowedIfHasPermission($account, 'apply to jobs')
            ->orIf(AccessResult::allowedIfHasPermission($account, 'access administration pages'));
    }

}
