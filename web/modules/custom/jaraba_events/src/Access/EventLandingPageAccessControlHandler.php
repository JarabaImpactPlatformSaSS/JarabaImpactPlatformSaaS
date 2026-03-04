<?php

declare(strict_types=1);

namespace Drupal\jaraba_events\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad EventLandingPage.
 *
 * Estructura: Extiende EntityAccessControlHandler con lógica
 *   de permisos por operación (view, update, delete).
 *
 * Lógica: Los administradores con 'administer events' tienen
 *   acceso completo. Los usuarios con permisos granulares pueden
 *   ver ('view events'), crear ('create events'), editar
 *   ('edit events') o eliminar ('delete events') landing pages.
 *
 * Sintaxis: Drupal 11 — AccessResult con cachePerPermissions.
 */
class EventLandingPageAccessControlHandler extends DefaultEntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    // TENANT-ISOLATION-ACCESS-001: Tenant isolation via parent.
    $parentResult = parent::checkAccess($entity, $operation, $account);
    if ($parentResult->isForbidden()) {
      return $parentResult;
    }

    // Acceso total para administradores de eventos.
    if ($account->hasPermission('administer events')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view events');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit events')
          ->cachePerPermissions();

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete events')
          ->cachePerPermissions();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer events',
      'create events',
    ], 'OR');
  }

}
