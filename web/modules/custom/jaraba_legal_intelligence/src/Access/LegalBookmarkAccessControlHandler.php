<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para entidades LegalBookmark.
 *
 * ESTRUCTURA: Extiende EntityAccessControlHandler con logica
 *   de permisos por operacion (view, update, delete) y
 *   verificacion de propietario (user_id).
 *
 * LOGICA: Los administradores con 'administer legal intelligence' tienen
 *   acceso completo. Los usuarios con 'bookmark legal resolutions'
 *   pueden gestionar sus propios marcadores; todas las operaciones
 *   requieren que user_id coincida con el usuario actual.
 *
 * RELACIONES:
 * - LegalBookmarkAccessControlHandler -> LegalBookmark entity (controla acceso)
 * - LegalBookmarkAccessControlHandler <- Drupal core (invocado por)
 */
class LegalBookmarkAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer legal intelligence')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $isOwner = ((int) $entity->get('user_id')->target_id === (int) $account->id());

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIf(
          $account->hasPermission('bookmark legal resolutions') && $isOwner
        )->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);

      case 'update':
        return AccessResult::allowedIf(
          $account->hasPermission('bookmark legal resolutions') && $isOwner
        )->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);

      case 'delete':
        return AccessResult::allowedIf(
          $account->hasPermission('bookmark legal resolutions') && $isOwner
        )->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer legal intelligence',
      'bookmark legal resolutions',
    ], 'OR');
  }

}
