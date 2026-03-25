<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResultInterface;

/**
 *
 */
class IntegrityProofAgroAccessControlHandler extends EntityAccessControlHandler {

  /**
   *
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    return match ($operation) {
      'view' => AccessResult::allowedIfHasPermission($account, 'view agro traceability'),
            'update', 'delete' => AccessResult::allowedIfHasPermission($account, 'manage agro traceability'),
            default => AccessResult::neutral(),
    };
  }

  /**
   *
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResultInterface {
    return AccessResult::allowedIfHasPermission($account, 'manage agro traceability');
  }

}
