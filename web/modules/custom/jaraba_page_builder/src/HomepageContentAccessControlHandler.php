<?php

namespace Drupal\jaraba_page_builder;

use Drupal\Core\Access\AccessResult;
use Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access handler para HomepageContent.
 */
class HomepageContentAccessControlHandler extends DefaultEntityAccessControlHandler
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

        if ($account->hasPermission('administer page builder')) {
            return AccessResult::allowed()->cachePerPermissions();
        }

        switch ($operation) {
            case 'view':
                return AccessResult::allowedIfHasPermission($account, 'view page builder');

            case 'update':
                return AccessResult::allowedIfHasPermission($account, 'edit page content');

            case 'delete':
                return AccessResult::allowedIfHasPermission($account, 'delete page content');
        }

        return AccessResult::neutral();
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL)
    {
        return AccessResult::allowedIfHasPermission($account, 'create page content');
    }

}
