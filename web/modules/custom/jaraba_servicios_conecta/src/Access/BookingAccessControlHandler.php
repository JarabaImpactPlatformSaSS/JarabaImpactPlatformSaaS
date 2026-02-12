<?php

namespace Drupal\jaraba_servicios_conecta\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad Booking.
 *
 * Estructura: Extiende EntityAccessControlHandler con lógica
 *   de permisos por operación.
 *
 * Lógica: Los administradores con 'manage servicios bookings' tienen
 *   acceso completo. Los profesionales ven/gestionan sus reservas,
 *   los clientes ven sus propias reservas (uid = owner).
 */
class BookingAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('manage servicios bookings')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $is_owner = $entity->getOwnerId() == $account->id();

    switch ($operation) {
      case 'view':
        // El cliente (owner) o el profesional pueden ver la reserva
        if ($is_owner && $account->hasPermission('view own servicios bookings')) {
          return AccessResult::allowed()->addCacheableDependency($entity)->cachePerUser();
        }
        if ($account->hasPermission('manage own servicios bookings')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        return AccessResult::allowedIfHasPermission($account, 'view servicios bookings');

      case 'update':
        if ($account->hasPermission('manage own servicios bookings')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        return AccessResult::neutral();

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'manage servicios bookings');
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'manage servicios bookings',
      'create servicios bookings',
    ], 'OR');
  }

}
