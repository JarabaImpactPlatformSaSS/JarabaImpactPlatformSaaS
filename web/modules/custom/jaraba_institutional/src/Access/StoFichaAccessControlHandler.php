<?php

declare(strict_types=1);

namespace Drupal\jaraba_institutional\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad Ficha STO.
 *
 * Estructura: Extiende EntityAccessControlHandler con restriccion
 *   append-only (ENTITY-APPEND-001). Las fichas STO son inmutables
 *   una vez creadas — no se pueden editar ni eliminar.
 *
 * Logica: view requiere 'view sto fichas'. update y delete siempre
 *   devuelven forbidden (inmutabilidad). create requiere
 *   'generate sto fichas' o 'administer institutional'.
 */
class StoFichaAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer institutional') && $operation === 'view') {
      return AccessResult::allowed()->cachePerPermissions();
    }

    return match ($operation) {
      'view' => AccessResult::allowedIfHasPermission($account, 'view sto fichas'),
      'update' => AccessResult::forbidden('Las fichas STO son inmutables (ENTITY-APPEND-001).'),
      'delete' => AccessResult::forbidden('Las fichas STO no se pueden eliminar (ENTITY-APPEND-001).'),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, ['administer institutional', 'generate sto fichas'], 'OR');
  }

}
