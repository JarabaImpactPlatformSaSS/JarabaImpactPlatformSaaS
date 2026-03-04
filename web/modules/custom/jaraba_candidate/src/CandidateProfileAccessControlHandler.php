<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate;

use Drupal\Core\Access\AccessResult;
use Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the CandidateProfile entity.
 */
class CandidateProfileAccessControlHandler extends DefaultEntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
    {
      // TENANT-ISOLATION-ACCESS-001: Tenant isolation via parent.
      $parentResult = parent::checkAccess($entity, $operation, $account);
      if ($parentResult->isForbidden()) {
        return $parentResult;
      }

        switch ($operation) {
            case 'view':
                // Owners can always view their own profile
                if ((int) $entity->get('user_id')->target_id === (int) $account->id()) {
                    return AccessResult::allowed()->cachePerUser();
                }
                // Public profiles can be viewed
                if ($entity->get('is_public')->value) {
                    return AccessResult::allowedIfHasPermission($account, 'view candidate profiles');
                }
                return AccessResult::allowedIfHasPermission($account, 'view private candidate profiles');

            case 'update':
                // Owners can edit their own profile
                if ((int) $entity->get('user_id')->target_id === (int) $account->id()) {
                    return AccessResult::allowed()->cachePerUser();
                }
                return AccessResult::allowedIfHasPermission($account, 'edit any candidate profiles');

            case 'delete':
                return AccessResult::allowedIfHasPermission($account, 'delete candidate profiles');
        }

        return AccessResult::neutral();
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL)
    {
        return AccessResult::allowedIfHasPermission($account, 'create candidate profile')
            ->orIf(AccessResult::allowedIfHasPermission($account, 'access administration pages'));
    }

}
