<?php

declare(strict_types=1);

namespace Drupal\jaraba_crm;

use Drupal\Core\Access\AccessResult;
use Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad Contact.
 */
class ContactAccessControlHandler extends DefaultEntityAccessControlHandler
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

        if ($account->hasPermission('administer crm entities')) {
            return AccessResult::allowed()->cachePerPermissions();
        }

        switch ($operation) {
            case 'view':
                return AccessResult::allowedIfHasPermission($account, 'view crm entities');

            case 'update':
                return AccessResult::allowedIfHasPermission($account, 'edit crm entities');

            case 'delete':
                return AccessResult::allowedIfHasPermission($account, 'delete crm entities');
        }

        return AccessResult::neutral();
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL)
    {
        return AccessResult::allowedIfHasPermissions($account, [
            'administer crm entities',
            'create crm entities',
        ], 'OR');
    }

}
