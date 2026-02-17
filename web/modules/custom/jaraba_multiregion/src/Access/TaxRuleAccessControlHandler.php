<?php

declare(strict_types=1);

namespace Drupal\jaraba_multiregion\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad TaxRule.
 *
 * ESTRUCTURA:
 * Handler de acceso que implementa el modelo de permisos diferenciados
 * para las reglas fiscales del sistema multi-region. Extiende
 * EntityAccessControlHandler para integrar con el sistema de entidades.
 *
 * LOGICA:
 * Aplica cortocircuito (short-circuit) para administradores con el permiso
 * 'administer multiregion', otorgando acceso completo. La visualizacion
 * requiere 'view tax rules'. La edicion/actualizacion requiere
 * 'manage tax rules'. La eliminacion queda restringida a administradores.
 * La creacion se permite con 'administer multiregion' O 'manage tax rules'
 * (logica OR) para flexibilidad de roles.
 *
 * SINTAXIS:
 * Sobreescribe checkAccess() y checkCreateAccess() de EntityAccessControlHandler.
 * TaxRule no implementa EntityOwnerInterface (entidad de sistema), por lo que
 * no se aplica logica de propietario.
 */
class TaxRuleAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   *
   * ESTRUCTURA: Evalua acceso a operaciones sobre entidades TaxRule existentes.
   *
   * LOGICA:
   * 1. Admin short-circuit: 'administer multiregion' concede acceso total.
   * 2. View: requiere 'view tax rules'.
   * 3. Update/edit: requiere 'manage tax rules'.
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
        return AccessResult::allowedIfHasPermission($account, 'view tax rules')
          ->cachePerPermissions();

      case 'update':
      case 'edit':
        return AccessResult::allowedIfHasPermission($account, 'manage tax rules')
          ->cachePerPermissions();

      case 'delete':
        // Solo administradores pueden eliminar reglas fiscales.
        return AccessResult::allowedIfHasPermission($account, 'administer multiregion')
          ->cachePerPermissions();
    }

    return AccessResult::neutral()->cachePerPermissions();
  }

  /**
   * {@inheritdoc}
   *
   * ESTRUCTURA: Evalua acceso para crear nuevas entidades TaxRule.
   *
   * LOGICA: Permite creacion si el usuario tiene 'administer multiregion'
   *   O 'manage tax rules'. Operador OR para flexibilidad de roles.
   *
   * SINTAXIS: allowedIfHasPermissions con operador 'OR'.
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer multiregion',
      'manage tax rules',
    ], 'OR')->cachePerPermissions();
  }

}
