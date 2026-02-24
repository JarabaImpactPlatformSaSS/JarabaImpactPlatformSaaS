<?php

declare(strict_types=1);

namespace Drupal\jaraba_job_board;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the JobPosting entity.
 */
class JobPostingAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
    {
        switch ($operation) {
            case 'view':
                $status = $entity->get('status')->value ?? 'draft';
                if ($status === 'published') {
                    return AccessResult::allowedIfHasPermission($account, 'view published jobs');
                }
                // Owners can view their own drafts
                if ((int) $entity->get('employer_id')->target_id === (int) $account->id()) {
                    return AccessResult::allowed()->cachePerUser();
                }
                return AccessResult::allowedIfHasPermission($account, 'view unpublished jobs');

            case 'update':
                // Owners can edit their own jobs
                if ((int) $entity->get('employer_id')->target_id === (int) $account->id()) {
                    return AccessResult::allowedIfHasPermission($account, 'edit own job postings');
                }
                return AccessResult::allowedIfHasPermission($account, 'edit any job postings');

            case 'delete':
                if ((int) $entity->get('employer_id')->target_id === (int) $account->id()) {
                    return AccessResult::allowedIfHasPermission($account, 'delete own job postings');
                }
                return AccessResult::allowedIfHasPermission($account, 'delete any job postings');
        }

        return AccessResult::neutral();
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL)
    {
        return AccessResult::allowedIfHasPermission($account, 'create job postings')
            ->orIf(AccessResult::allowedIfHasPermission($account, 'access administration pages'));
    }

}
