<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para entidades de pedido agro (OrderAgro y OrderItemAgro).
 */
class OrderAgroAccessControlHandler extends DefaultEntityAccessControlHandler
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

        if ($account->hasPermission('administer agroconecta')) {
            return AccessResult::allowed()->cachePerPermissions();
        }

        return match ($operation) {
            'view' => AccessResult::allowedIfHasPermission($account, 'manage agro orders'),
            'update' => AccessResult::allowedIfHasPermission($account, 'manage agro orders'),
            'delete' => AccessResult::allowedIfHasPermission($account, 'administer agroconecta'),
            default => AccessResult::neutral(),
        };
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL)
    {
        return AccessResult::allowedIfHasPermission($account, 'manage agro orders');
    }

}
