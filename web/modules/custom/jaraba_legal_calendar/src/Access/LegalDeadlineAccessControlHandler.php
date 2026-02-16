<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_calendar\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad LegalDeadline.
 */
class LegalDeadlineAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('manage legal deadlines')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $is_owner = ($entity->getOwnerId() == $account->id());

    if ($operation === 'view' && $is_owner && $account->hasPermission('access legal calendar')) {
      return AccessResult::allowed()->addCacheableDependency($entity)->cachePerUser();
    }

    if (in_array($operation, ['update', 'delete']) && $is_owner && $account->hasPermission('manage legal deadlines')) {
      return AccessResult::allowed()->addCacheableDependency($entity)->cachePerUser();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'manage legal deadlines',
      'access legal calendar',
    ], 'OR');
  }

}
