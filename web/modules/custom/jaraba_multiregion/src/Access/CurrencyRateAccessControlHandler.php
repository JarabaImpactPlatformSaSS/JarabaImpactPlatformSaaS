<?php

declare(strict_types=1);

namespace Drupal\jaraba_multiregion\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad CurrencyRate.
 *
 * ESTRUCTURA:
 * Handler de acceso que implementa el modelo de permisos diferenciados
 * para los tipos de cambio del sistema multi-region. Extiende
 * EntityAccessControlHandler para integrar con el sistema de entidades.
 *
 * LOGICA:
 * Aplica cortocircuito (short-circuit) para administradores con el permiso
 * 'administer multiregion', otorgando acceso completo. La visualizacion
 * requiere 'view currency rates'. La edicion/actualizacion requiere
 * 'manage currency rates'. La eliminacion queda restringida a administradores.
 * La creacion se permite con 'administer multiregion' O 'manage currency rates'
 * (logica OR) para flexibilidad de roles.
 *
 * SINTAXIS:
 * Sobreescribe checkAccess() y checkCreateAccess() de EntityAccessControlHandler.
 * CurrencyRate es una entidad inmutable de sistema, sin EntityOwnerInterface,
 * por lo que no se aplica logica de propietario.
 */
class CurrencyRateAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   *
   * ESTRUCTURA: Evalua acceso a operaciones sobre entidades CurrencyRate existentes.
   *
   * LOGICA:
   * 1. Admin short-circuit: 'administer multiregion' concede acceso total.
   * 2. View: requiere 'view currency rates'.
   * 3. Update/edit: requiere 'manage currency rates'.
   * 4. Delete: solo 'administer multiregion'.
   *
   * SINTAXIS: Retorna AccessResult con metadata de cache por permisos.
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    // Cortocircuito administrativo: acceso total si tiene permiso global.
    if ($account->hasPermission('administer multiregion')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view currency rates')
          ->cachePerPermissions();

      case 'update':
      case 'edit':
        return AccessResult::allowedIfHasPermission($account, 'manage currency rates')
          ->cachePerPermissions();

      case 'delete':
        // Solo administradores pueden eliminar tipos de cambio.
        return AccessResult::allowedIfHasPermission($account, 'administer multiregion')
          ->cachePerPermissions();
    }

    return AccessResult::neutral()->cachePerPermissions();
  }

  /**
   * {@inheritdoc}
   *
   * ESTRUCTURA: Evalua acceso para crear nuevas entidades CurrencyRate.
   *
   * LOGICA: Permite creacion si el usuario tiene 'administer multiregion'
   *   O 'manage currency rates'. Operador OR para flexibilidad de roles.
   *
   * SINTAXIS: allowedIfHasPermissions con operador 'OR'.
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer multiregion',
      'manage currency rates',
    ], 'OR')->cachePerPermissions();
  }

}
