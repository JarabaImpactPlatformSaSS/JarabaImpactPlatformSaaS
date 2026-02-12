<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad EntrepreneurProfile.
 *
 * Administradores con 'administer entrepreneur profiles' tienen acceso total.
 * Usuarios autenticados pueden ver y editar su propio perfil.
 */
class EntrepreneurProfileAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    /** @var \Drupal\jaraba_copilot_v2\Entity\EntrepreneurProfile $entity */
    if ($account->hasPermission('administer entrepreneur profiles')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        if ((int) $entity->getOwnerId() === (int) $account->id()) {
          return AccessResult::allowed()->cachePerUser()->addCacheableDependency($entity);
        }
        return AccessResult::neutral();

      case 'update':
        if ((int) $entity->getOwnerId() === (int) $account->id()) {
          return AccessResult::allowed()->cachePerUser()->addCacheableDependency($entity);
        }
        return AccessResult::neutral();

      case 'delete':
        return AccessResult::neutral();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer entrepreneur profiles',
      'access copilot',
    ], 'OR');
  }

}
