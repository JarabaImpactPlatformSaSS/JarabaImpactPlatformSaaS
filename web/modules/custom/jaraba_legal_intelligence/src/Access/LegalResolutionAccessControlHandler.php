<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para entidades LegalResolution.
 *
 * ESTRUCTURA: Extiende EntityAccessControlHandler con logica
 *   de permisos por operacion (view, update, delete).
 *
 * LOGICA: Los administradores con 'administer legal intelligence' tienen
 *   acceso completo. Los usuarios con 'view legal resolutions' o
 *   'search legal resolutions' pueden ver resoluciones. Solo
 *   administradores pueden actualizar o eliminar.
 *
 * RELACIONES:
 * - LegalResolutionAccessControlHandler -> LegalResolution entity (controla acceso)
 * - LegalResolutionAccessControlHandler <- Drupal core (invocado por)
 */
class LegalResolutionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer legal intelligence')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermissions($account, [
          'view legal resolutions',
          'search legal resolutions',
        ], 'OR');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'administer legal intelligence');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'administer legal intelligence');
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'administer legal intelligence');
  }

}
