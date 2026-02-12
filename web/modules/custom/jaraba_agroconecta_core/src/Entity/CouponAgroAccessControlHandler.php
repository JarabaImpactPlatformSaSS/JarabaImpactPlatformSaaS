<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler para CouponAgro.
 */
class CouponAgroAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult
    {
        return match ($operation) {
            'view' => AccessResult::allowedIfHasPermission($account, 'view agro promotions'),
            'update', 'delete' => AccessResult::allowedIfHasPermission($account, 'manage agro promotions'),
            default => AccessResult::neutral(),
        };
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult
    {
        return AccessResult::allowedIfHasPermission($account, 'manage agro promotions');
    }

}
