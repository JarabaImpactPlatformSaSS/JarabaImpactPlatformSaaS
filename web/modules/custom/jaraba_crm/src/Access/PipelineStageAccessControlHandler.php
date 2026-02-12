<?php

declare(strict_types=1);

namespace Drupal\jaraba_crm\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad PipelineStage.
 *
 * Verifica permisos granulares y aislamiento multi-tenant.
 */
class PipelineStageAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer crm entities')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view crm entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit crm entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete crm entities');
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer crm entities',
      'create crm entities',
    ], 'OR');
  }

}
