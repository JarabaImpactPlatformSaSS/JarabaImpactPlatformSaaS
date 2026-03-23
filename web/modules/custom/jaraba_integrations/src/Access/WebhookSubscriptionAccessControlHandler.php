<?php

declare(strict_types=1);

namespace Drupal\jaraba_integrations\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResultInterface;

/**
 * Control de acceso para WebhookSubscription con aislamiento por tenant.
 */
class WebhookSubscriptionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    if ($account->hasPermission('administer integrations')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    if ($account->hasPermission('manage webhooks')) {
      return AccessResult::allowed()->cachePerPermissions()->cachePerUser();
    }

    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer integrations',
      'manage webhooks',
    ], 'OR');
  }

}
