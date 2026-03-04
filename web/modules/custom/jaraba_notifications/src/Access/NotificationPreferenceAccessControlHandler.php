<?php

declare(strict_types=1);

namespace Drupal\jaraba_notifications\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para NotificationPreference.
 *
 * Solo el propietario puede ver y editar sus preferencias.
 * Administradores con 'administer notifications' tienen acceso completo.
 */
class NotificationPreferenceAccessControlHandler extends DefaultEntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    // TENANT-ISOLATION-ACCESS-001: Tenant isolation via parent.
    $parentResult = parent::checkAccess($entity, $operation, $account);
    if ($parentResult->isForbidden()) {
      return $parentResult;
    }

    if ($account->hasPermission('administer notifications')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $isOwner = (int) $entity->getOwnerId() === (int) $account->id();

    return match ($operation) {
      'view', 'update' => AccessResult::allowedIf($isOwner)
        ->addCacheableDependency($entity)
        ->cachePerUser(),
      'delete' => AccessResult::allowedIf($isOwner && $account->hasPermission('manage own notification preferences'))
        ->addCacheableDependency($entity)
        ->cachePerUser()
        ->cachePerPermissions(),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResultInterface {
    if ($account->hasPermission('administer notifications')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    return AccessResult::allowedIfHasPermission($account, 'manage own notification preferences');
  }

}
