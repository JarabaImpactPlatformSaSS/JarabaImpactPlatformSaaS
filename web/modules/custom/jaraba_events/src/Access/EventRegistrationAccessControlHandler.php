<?php

namespace Drupal\jaraba_events\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad EventRegistration.
 *
 * Estructura: Extiende EntityAccessControlHandler con lógica
 *   de permisos por operación (view, update, delete).
 *
 * Lógica: Los administradores con 'manage event registrations' tienen
 *   acceso completo. Los asistentes con 'register for events' pueden
 *   ver y crear registros. Solo pueden cancelar sus propios registros
 *   con 'cancel own event registrations'.
 *
 * Sintaxis: Drupal 11 — AccessResult con cachePerPermissions/cachePerUser.
 */
class EventRegistrationAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('manage event registrations')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $is_owner = (int) $entity->getOwnerId() === (int) $account->id();

    switch ($operation) {
      case 'view':
        // El propio asistente puede ver su registro.
        if ($is_owner && $account->hasPermission('register for events')) {
          return AccessResult::allowed()->addCacheableDependency($entity)->cachePerUser();
        }
        return AccessResult::allowedIfHasPermission($account, 'view marketing events');

      case 'update':
        // Solo el propietario puede editar su registro.
        if ($is_owner && $account->hasPermission('register for events')) {
          return AccessResult::allowed()->addCacheableDependency($entity)->cachePerUser();
        }
        return AccessResult::neutral();

      case 'delete':
        // Solo el propietario puede cancelar su registro.
        if ($is_owner && $account->hasPermission('cancel own event registrations')) {
          return AccessResult::allowed()->addCacheableDependency($entity)->cachePerUser();
        }
        return AccessResult::neutral();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'manage event registrations',
      'register for events',
    ], 'OR');
  }

}
