<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para RolProgramaLog.
 *
 * ACCESS-RETURN-TYPE-001: checkAccess() retorna AccessResultInterface.
 */
class RolProgramaLogAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    if ($account->hasPermission('administer andalucia ei')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'administer andalucia ei')
          ->addCacheableDependency($entity)
          ->cachePerPermissions();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResultInterface {
    return AccessResult::allowedIfHasPermissions($account, ['administer andalucia ei', 'assign andalucia ei roles'], 'OR');
  }

}
