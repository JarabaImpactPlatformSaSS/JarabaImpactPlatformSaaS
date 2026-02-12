<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad ScheduledPublish.
 *
 * P1-05: Permisos basados en propiedad y roles.
 * - administer page builder: acceso completo.
 * - edit page builder content: gestionar programaciones propias.
 * - view page builder content: ver programaciones propias.
 */
class ScheduledPublishAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer page builder')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $isOwner = ((int) $entity->getOwnerId() === (int) $account->id());

    switch ($operation) {
      case 'view':
        if ($isOwner && $account->hasPermission('access page builder')) {
          return AccessResult::allowed()
            ->cachePerPermissions()
            ->cachePerUser()
            ->addCacheableDependency($entity);
        }
        return AccessResult::neutral();

      case 'update':
        // Solo permitir editar si esta pendiente.
        $isPending = $entity->getScheduleStatus() === 'pending';
        if ($isOwner && $isPending && $account->hasPermission('edit page builder content')) {
          return AccessResult::allowed()
            ->cachePerPermissions()
            ->cachePerUser()
            ->addCacheableDependency($entity);
        }
        return AccessResult::neutral();

      case 'delete':
        $isPending = $entity->getScheduleStatus() === 'pending';
        if ($isOwner && $isPending && $account->hasPermission('edit page builder content')) {
          return AccessResult::allowed()
            ->cachePerPermissions()
            ->cachePerUser()
            ->addCacheableDependency($entity);
        }
        return AccessResult::neutral();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer page builder',
      'edit page builder content',
    ], 'OR');
  }

}
