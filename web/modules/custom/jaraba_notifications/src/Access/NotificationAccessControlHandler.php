<?php

declare(strict_types=1);

namespace Drupal\jaraba_notifications\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResultInterface;

/**
 * Access control handler para la entidad Notification.
 *
 * - Admins: acceso total.
 * - Usuarios: solo ver/editar sus propias notificaciones.
 * - TENANT-001: aislamiento implicito via owner check.
 */
class NotificationAccessControlHandler extends DefaultEntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    // TENANT-ISOLATION-ACCESS-001: Tenant isolation via parent.
    $parentResult = parent::checkAccess($entity, $operation, $account);
    if ($parentResult->isForbidden()) {
      return $parentResult;
    }

    if ($account->hasPermission('administer site structure')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    // Solo el propietario puede ver/editar sus notificaciones.
    $isOwner = (int) $entity->getOwnerId() === (int) $account->id();

    return match ($operation) {
      'view', 'update' => AccessResult::allowedIf($isOwner)
        ->addCacheableDependency($entity)
        ->cachePerUser(),
      'delete' => AccessResult::allowedIf($isOwner && $account->hasPermission('administer site structure'))
        ->cachePerPermissions()
        ->cachePerUser(),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'administer site structure');
  }

}
