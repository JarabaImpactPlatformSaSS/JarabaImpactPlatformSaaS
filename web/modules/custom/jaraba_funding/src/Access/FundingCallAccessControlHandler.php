<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para entidades del Funding Intelligence module.
 *
 * ESTRUCTURA: Extiende EntityAccessControlHandler con logica
 *   de permisos por operacion (view, update, delete).
 *
 * LOGICA: Los administradores con 'administer jaraba funding' tienen
 *   acceso completo. Los usuarios con 'view funding dashboard' pueden
 *   ver convocatorias. Los usuarios con 'view own funding matches' pueden
 *   ver sus matches. Los usuarios con 'manage funding subscriptions' pueden
 *   gestionar suscripciones a alertas.
 *
 * RELACIONES:
 * - FundingCallAccessControlHandler -> Funding entities (controla acceso)
 * - FundingCallAccessControlHandler <- Drupal core (invocado por)
 */
class FundingCallAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer jaraba funding')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view funding dashboard');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'administer jaraba funding');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'administer jaraba funding');
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'administer jaraba funding');
  }

}
