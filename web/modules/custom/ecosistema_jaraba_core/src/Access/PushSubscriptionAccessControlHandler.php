<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad PushSubscription.
 *
 * PROPÓSITO:
 * Gestiona permisos para las suscripciones de notificaciones push.
 *
 * LÓGICA:
 * - view/delete propias: usuarios autenticados pueden gestionar sus
 *   propias suscripciones push.
 * - view/delete ajenas: requiere 'administer tenants'.
 * - create: usuarios autenticados (las suscripciones se crean via API).
 * - update: siempre denegado (las suscripciones se reemplazan, no se editan).
 *
 * PHASE 5 - G109-3: Push Notifications
 */
class PushSubscriptionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // Los administradores de tenants tienen acceso completo.
    $adminAccess = AccessResult::allowedIfHasPermission($account, 'administer tenants');
    if ($adminAccess->isAllowed()) {
      return $adminAccess;
    }

    // Verificar si el usuario es propietario de la suscripción.
    $isOwner = (int) $entity->get('user_id')->target_id === (int) $account->id();

    return match ($operation) {
      'view', 'delete' => $isOwner
        ? AccessResult::allowed()->addCacheableDependency($entity)->cachePerUser()
        : AccessResult::neutral()->cachePerUser(),
      'update' => AccessResult::forbidden('Las suscripciones push no se editan. Elimine y cree una nueva.'),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    // Cualquier usuario autenticado puede crear suscripciones push.
    if ($account->isAuthenticated()) {
      return AccessResult::allowed()->cachePerUser();
    }

    return AccessResult::neutral();
  }

}
