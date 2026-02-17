<?php

declare(strict_types=1);

namespace Drupal\jaraba_multiregion\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad TenantRegion.
 *
 * ESTRUCTURA:
 * Handler de acceso que implementa el modelo de permisos diferenciados
 * para la configuracion regional de tenants. Extiende EntityAccessControlHandler
 * para integrar con el sistema de entidades de Drupal.
 *
 * LOGICA:
 * Aplica un cortocircuito (short-circuit) para administradores con el permiso
 * 'administer multiregion', permitiendo acceso total sin evaluacion adicional.
 * Para operaciones de visualizacion se requiere 'access multiregion'.
 * Para edicion/actualizacion se requiere 'manage regions', con cache por usuario
 * para propietarios de la entidad (uid coincidente). La eliminacion queda
 * restringida exclusivamente a administradores. La creacion se permite con
 * 'administer multiregion' O 'manage regions' (logica OR).
 *
 * SINTAXIS:
 * Sobreescribe checkAccess() y checkCreateAccess() de EntityAccessControlHandler.
 * Usa AccessResult con cachePerPermissions() y cachePerUser() para cacheabilidad
 * correcta del sistema de acceso de Drupal.
 */
class TenantRegionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   *
   * ESTRUCTURA: Evalua acceso a operaciones sobre entidades TenantRegion existentes.
   *
   * LOGICA:
   * 1. Admin short-circuit: 'administer multiregion' concede acceso total.
   * 2. View: requiere 'access multiregion'.
   * 3. Update/edit: requiere 'manage regions'; si el usuario es propietario
   *    de la entidad se anade cachePerUser() para variacion de cache.
   * 4. Delete: solo 'administer multiregion'.
   *
   * SINTAXIS: Retorna AccessResult con metadata de cache apropiada.
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    // Cortocircuito administrativo: acceso total si tiene permiso global.
    if ($account->hasPermission('administer multiregion')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'access multiregion')
          ->cachePerPermissions();

      case 'update':
      case 'edit':
        // Verificar si el usuario es propietario de la entidad.
        $is_owner = ((int) $entity->getOwnerId() === (int) $account->id());
        if ($is_owner) {
          return AccessResult::allowedIfHasPermission($account, 'manage regions')
            ->cachePerPermissions()
            ->cachePerUser();
        }
        return AccessResult::allowedIfHasPermission($account, 'manage regions')
          ->cachePerPermissions();

      case 'delete':
        // Solo administradores pueden eliminar configuraciones regionales.
        return AccessResult::allowedIfHasPermission($account, 'administer multiregion')
          ->cachePerPermissions();
    }

    return AccessResult::neutral()->cachePerPermissions();
  }

  /**
   * {@inheritdoc}
   *
   * ESTRUCTURA: Evalua acceso para crear nuevas entidades TenantRegion.
   *
   * LOGICA: Permite creacion si el usuario tiene 'administer multiregion'
   *   O 'manage regions'. Operador OR para flexibilidad de roles.
   *
   * SINTAXIS: allowedIfHasPermissions con operador 'OR'.
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer multiregion',
      'manage regions',
    ], 'OR')->cachePerPermissions();
  }

}
