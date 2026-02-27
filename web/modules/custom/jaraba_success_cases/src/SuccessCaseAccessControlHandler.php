<?php

declare(strict_types=1);

namespace Drupal\jaraba_success_cases;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for Success Case entities.
 *
 * Permissions:
 * - administer success cases: full CRUD (site_admin + content_editor)
 * - view published success cases: frontend viewing
 * - view unpublished success cases: preview drafts
 */
class SuccessCaseAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface
    {
        /** @var \Drupal\jaraba_success_cases\Entity\SuccessCase $entity */

        if ($account->hasPermission('administer success cases')) {
            return AccessResult::allowed()->cachePerPermissions();
        }

        switch ($operation) {
            case 'view':
                if ($entity->get('status')->value) {
                    return AccessResult::allowedIfHasPermission($account, 'view published success cases')
                        ->addCacheableDependency($entity);
                }
                return AccessResult::allowedIfHasPermission($account, 'view unpublished success cases')
                    ->addCacheableDependency($entity);

            case 'update':
            case 'delete':
                return AccessResult::allowedIfHasPermission($account, 'administer success cases')
                    ->cachePerPermissions();
        }

        return AccessResult::neutral();
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResultInterface
    {
        return AccessResult::allowedIfHasPermission($account, 'administer success cases');
    }

}
