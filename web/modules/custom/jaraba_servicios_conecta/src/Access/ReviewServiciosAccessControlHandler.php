<?php

declare(strict_types=1);

namespace Drupal\jaraba_servicios_conecta\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad ReviewServicios.
 *
 * Estructura: Extiende EntityAccessControlHandler con logica
 *   de permisos por operacion y rol.
 *
 * Logica:
 *   - View: publico si status == 'approved', o admin con 'manage servicios reviews'.
 *   - Create: usuario autenticado con permiso 'submit servicios reviews'.
 *   - Update: profesional puede anadir respuesta ('respond servicios reviews'),
 *     admin puede cambiar estado ('manage servicios reviews').
 *   - Delete: solo admin con 'manage servicios reviews'.
 */
class ReviewServiciosAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    // Administradores con permiso de gestion tienen acceso completo.
    if ($account->hasPermission('manage servicios reviews')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        // Las resenas aprobadas son publicas para quien tenga permiso de ver.
        $status = $entity->get('status')->value;
        if ($status === 'approved') {
          return AccessResult::allowedIfHasPermission($account, 'view servicios reviews')
            ->addCacheableDependency($entity);
        }
        // El autor puede ver su propia resena en cualquier estado.
        if ((int) $entity->getOwnerId() === (int) $account->id() && $account->isAuthenticated()) {
          return AccessResult::allowed()
            ->addCacheableDependency($entity)
            ->cachePerUser();
        }
        return AccessResult::neutral()->addCacheableDependency($entity);

      case 'update':
        // El profesional puede anadir respuesta.
        if ($account->hasPermission('respond servicios reviews')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        return AccessResult::neutral();

      case 'delete':
        // Solo admin (ya cubierto arriba).
        return AccessResult::neutral();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'manage servicios reviews',
      'submit servicios reviews',
    ], 'OR');
  }

}
