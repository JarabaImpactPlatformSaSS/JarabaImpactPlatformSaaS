<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms;

use Drupal\Core\Access\AccessResult;
use Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResultInterface;

/**
 * Access controller for the Course entity.
 */
class CourseAccessControlHandler extends DefaultEntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    // Admin bypass via admin_permission.
    if ($account->hasPermission('administer lms courses')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    // TENANT-ISOLATION-ACCESS-001: Tenant isolation for update/delete.
    if (in_array($operation, ['update', 'delete'], TRUE)) {
      $tenantCheck = $this->checkTenantIsolation($entity, $account);
      if ($tenantCheck !== NULL) {
        return $tenantCheck;
      }
    }

    switch ($operation) {
      case 'view':
        if ($entity->get('is_published')->value) {
          return AccessResult::allowedIfHasPermission($account, 'view published courses');
        }
        return AccessResult::allowedIfHasPermission($account, 'view unpublished courses');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit courses');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete courses');
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'create courses')
      ->orIf(AccessResult::allowedIfHasPermission($account, 'access administration pages'));
  }

}
