<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para entidades LegalSource.
 *
 * ESTRUCTURA: Extiende EntityAccessControlHandler con logica
 *   de permisos por operacion (view, update, delete).
 *
 * LOGICA: Entidad exclusiva de administracion. Solo usuarios con
 *   'administer legal intelligence' tienen acceso a cualquier
 *   operacion sobre fuentes legales.
 *
 * RELACIONES:
 * - LegalSourceAccessControlHandler -> LegalSource entity (controla acceso)
 * - LegalSourceAccessControlHandler <- Drupal core (invocado por)
 */
class LegalSourceAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer legal intelligence')) {
      return AccessResult::allowed()->cachePerPermissions();
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
