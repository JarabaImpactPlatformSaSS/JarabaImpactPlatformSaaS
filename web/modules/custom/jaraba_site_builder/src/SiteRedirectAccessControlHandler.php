<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder;

use Drupal\Core\Access\AccessResult;
use Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResultInterface;

/**
 * Control de acceso para la entidad SiteRedirect.
 */
class SiteRedirectAccessControlHandler extends DefaultEntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    // TENANT-ISOLATION-ACCESS-001: Tenant isolation via parent.
    $parentResult = parent::checkAccess($entity, $operation, $account);
    if ($parentResult->isForbidden()) {
      return $parentResult;
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'manage site redirects')
          ->cachePerPermissions()
          ->addCacheableDependency($entity);

      case 'update':
      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'manage site redirects')
          ->cachePerPermissions()
          ->addCacheableDependency($entity);
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'manage site redirects');
  }

}
