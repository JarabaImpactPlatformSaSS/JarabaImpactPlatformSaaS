<?php

declare(strict_types=1);

namespace Drupal\jaraba_whitelabel\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for CustomDomain entities.
 */
class CustomDomainAccessControlHandler extends DefaultEntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // TENANT-ISOLATION-ACCESS-001: Tenant isolation via parent.
    $parentResult = parent::checkAccess($entity, $operation, $account);
    if ($parentResult->isForbidden()) {
      return $parentResult;
    }

    return match ($operation) {
      'view' => AccessResult::allowedIfHasPermissions($account, [
        'administer whitelabel',
        'manage custom domains',
      ], 'OR'),
      'update', 'delete' => AccessResult::allowedIfHasPermission($account, 'administer whitelabel'),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer whitelabel',
      'manage custom domains',
    ], 'OR');
  }

}
