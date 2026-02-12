<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_knowledge\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para entidades del Legal Knowledge module.
 *
 * ESTRUCTURA: Extiende EntityAccessControlHandler con logica
 *   de permisos por operacion (view, update, delete).
 *
 * LOGICA: Los administradores con 'administer legal knowledge' tienen
 *   acceso completo. Los usuarios con 'view legal queries' pueden
 *   ver consultas. Los usuarios con 'manage legal norms' pueden
 *   gestionar normas. Los usuarios con 'view legal alerts' pueden
 *   ver alertas de cambios normativos.
 *
 * RELACIONES:
 * - LegalNormAccessControlHandler -> Legal entities (controla acceso)
 * - LegalNormAccessControlHandler <- Drupal core (invocado por)
 */
class LegalNormAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer legal knowledge')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermissions($account, [
          'view legal queries',
          'view legal alerts',
          'manage legal norms',
        ], 'OR');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'manage legal norms');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'administer legal knowledge');
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer legal knowledge',
      'manage legal norms',
    ], 'OR');
  }

}
