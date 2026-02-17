<?php

declare(strict_types=1);

namespace Drupal\jaraba_institutional\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad Participante de Programa.
 *
 * Estructura: Extiende EntityAccessControlHandler con logica de
 *   permisos basada en roles y propiedad del tenant.
 *
 * Logica: view requiere 'view participants', update/delete requiere
 *   'manage participants'. Admin permission 'administer institutional'
 *   permite todo.
 */
class ProgramParticipantAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer institutional')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    return match ($operation) {
      'view' => AccessResult::allowedIfHasPermission($account, 'view participants'),
      'update' => AccessResult::allowedIfHasPermission($account, 'manage participants'),
      'delete' => AccessResult::allowedIfHasPermission($account, 'manage participants'),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, ['administer institutional', 'manage participants'], 'OR');
  }

}
