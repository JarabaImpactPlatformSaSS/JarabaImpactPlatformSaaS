<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para entidades LegalAlert.
 *
 * ESTRUCTURA: Extiende EntityAccessControlHandler con logica
 *   de permisos por operacion (view, update, delete) y
 *   verificacion de propietario (provider_id).
 *
 * LOGICA: Los administradores con 'administer legal intelligence' tienen
 *   acceso completo. Los usuarios con 'manage legal alerts' pueden
 *   ver alertas; para actualizar y eliminar se requiere ademas
 *   ser propietario (provider_id = uid actual). Eliminar tambien
 *   se permite con permiso de administracion.
 *
 * RELACIONES:
 * - LegalAlertAccessControlHandler -> LegalAlert entity (controla acceso)
 * - LegalAlertAccessControlHandler <- Drupal core (invocado por)
 */
class LegalAlertAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer legal intelligence')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $isOwner = ((int) $entity->get('provider_id')->target_id === (int) $account->id());

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIf(
          $account->hasPermission('manage legal alerts') || $isOwner
        )->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);

      case 'update':
        return AccessResult::allowedIf(
          $account->hasPermission('manage legal alerts') && $isOwner
        )->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);

      case 'delete':
        return AccessResult::allowedIf(
          ($account->hasPermission('manage legal alerts') && $isOwner)
          || $account->hasPermission('administer legal intelligence')
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
      'manage legal alerts',
    ], 'OR');
  }

}
