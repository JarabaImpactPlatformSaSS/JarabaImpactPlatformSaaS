<?php

declare(strict_types=1);

namespace Drupal\jaraba_whatsapp\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler;

/**
 * Access control handler para WaMessage.
 *
 * TENANT-ISOLATION-ACCESS-001: Hereda verificacion tenant del padre.
 */
class WaMessageAccessControlHandler extends DefaultEntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    $parentResult = parent::checkAccess($entity, $operation, $account);
    if ($parentResult->isForbidden()) {
      return $parentResult;
    }

    if ($account->hasPermission('administer whatsapp')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    return match ($operation) {
      'view' => AccessResult::allowedIfHasPermission($account, 'view whatsapp conversations'),
      default => AccessResult::allowedIfHasPermission($account, 'administer whatsapp'),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResultInterface {
    return AccessResult::allowedIfHasPermission($account, 'manage whatsapp conversations');
  }

}
