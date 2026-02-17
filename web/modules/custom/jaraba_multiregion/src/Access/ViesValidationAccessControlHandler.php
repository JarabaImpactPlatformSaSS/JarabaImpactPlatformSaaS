<?php

declare(strict_types=1);

namespace Drupal\jaraba_multiregion\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad ViesValidation.
 *
 * ESTRUCTURA:
 * Handler de acceso que implementa el modelo de permisos diferenciados
 * para los registros de validacion VIES del sistema multi-region. Extiende
 * EntityAccessControlHandler para integrar con el sistema de entidades.
 *
 * LOGICA:
 * Aplica cortocircuito (short-circuit) para administradores con el permiso
 * 'administer multiregion', otorgando acceso completo. La visualizacion
 * requiere 'access multiregion' (permiso compartido con regiones). La
 * eliminacion queda restringida exclusivamente a administradores. La creacion
 * (ejecutar una validacion VIES) se permite con 'administer multiregion'
 * O 'validate vies' (logica OR). No se define operacion de update ya que
 * las validaciones VIES son registros inmutables una vez creados.
 *
 * SINTAXIS:
 * Sobreescribe checkAccess() y checkCreateAccess() de EntityAccessControlHandler.
 * ViesValidation es una entidad inmutable de registro; no soporta edicion.
 */
class ViesValidationAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   *
   * ESTRUCTURA: Evalua acceso a operaciones sobre entidades ViesValidation existentes.
   *
   * LOGICA:
   * 1. Admin short-circuit: 'administer multiregion' concede acceso total.
   * 2. View: requiere 'access multiregion'.
   * 3. Delete: solo 'administer multiregion'.
   * Las validaciones VIES son inmutables: no se soporta update/edit.
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
        return AccessResult::allowedIfHasPermission($account, 'access multiregion')
          ->cachePerPermissions();

      case 'delete':
        // Solo administradores pueden eliminar registros de validacion VIES.
        return AccessResult::allowedIfHasPermission($account, 'administer multiregion')
          ->cachePerPermissions();
    }

    return AccessResult::neutral()->cachePerPermissions();
  }

  /**
   * {@inheritdoc}
   *
   * ESTRUCTURA: Evalua acceso para crear nuevas entidades ViesValidation.
   *
   * LOGICA: Permite creacion (ejecutar validacion VIES) si el usuario tiene
   *   'administer multiregion' O 'validate vies'. Operador OR para que los
   *   gestores regionales puedan validar numeros de IVA sin acceso admin.
   *
   * SINTAXIS: allowedIfHasPermissions con operador 'OR'.
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer multiregion',
      'validate vies',
    ], 'OR')->cachePerPermissions();
  }

}
