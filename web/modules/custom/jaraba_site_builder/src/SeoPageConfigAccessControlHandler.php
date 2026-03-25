<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler para la entidad SeoPageConfig.
 */
class SeoPageConfigAccessControlHandler extends DefaultEntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    // TENANT-ISOLATION-ACCESS-001: Tenant isolation via parent.
    $parentResult = parent::checkAccess($entity, $operation, $account);
    if ($parentResult->isForbidden()) {
      return $parentResult;
    }

    return match ($operation) {
      'view' => AccessResult::allowedIfHasPermissions($account, [
        'administer site structure',
        'manage seo configuration',
        'view seo audit',
      ], 'OR'),
            'update', 'delete' => AccessResult::allowedIfHasPermissions($account, [
              'administer site structure',
              'manage seo configuration',
            ], 'OR'),
            default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResultInterface {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer site structure',
      'manage seo configuration',
    ], 'OR');
  }

}
