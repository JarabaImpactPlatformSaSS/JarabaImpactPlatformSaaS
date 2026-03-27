<?php

declare(strict_types=1);

namespace Drupal\jaraba_whatsapp\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler;

/**
 * Access control handler para WaConversation.
 *
 * TENANT-ISOLATION-ACCESS-001: Verifica tenant match para update/delete.
 * AUDIT-CONS-001: Obligatorio para toda ContentEntity.
 */
class WaConversationAccessControlHandler extends DefaultEntityAccessControlHandler {

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
      'update' => AccessResult::allowedIfHasPermission($account, 'manage whatsapp conversations'),
      'delete' => AccessResult::allowedIfHasPermission($account, 'administer whatsapp'),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResultInterface {
    return AccessResult::allowedIfHasPermission($account, 'manage whatsapp conversations');
  }

}
