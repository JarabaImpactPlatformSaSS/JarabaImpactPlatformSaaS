<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler para ShippingMethodAgro.
 */
class ShippingMethodAgroAccessControlHandler extends DefaultEntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult
    {
      // TENANT-ISOLATION-ACCESS-001: Tenant isolation via parent.
      $parentResult = parent::checkAccess($entity, $operation, $account);
      if ($parentResult->isForbidden()) {
        return $parentResult;
      }

        return match ($operation) {
            'view' => AccessResult::allowedIfHasPermission($account, 'view agro shipping'),
            'update', 'delete' => AccessResult::allowedIfHasPermission($account, 'manage agro shipping'),
            default => AccessResult::neutral(),
        };
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult
    {
        return AccessResult::allowedIfHasPermission($account, 'manage agro shipping');
    }

}
