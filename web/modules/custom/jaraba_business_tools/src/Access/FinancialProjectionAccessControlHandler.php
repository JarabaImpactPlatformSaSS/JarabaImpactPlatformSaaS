<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for Financial Projection entities.
 */
class FinancialProjectionAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
    {
        if ($account->hasPermission('administer business model canvas')) {
            return AccessResult::allowed()->cachePerPermissions();
        }

        if ((int) $entity->getOwnerId() === (int) $account->id()) {
            return AccessResult::allowed()->cachePerUser()->addCacheableDependency($entity);
        }

        return AccessResult::neutral();
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL)
    {
        return AccessResult::allowedIfHasPermission($account, 'create canvas');
    }

}
