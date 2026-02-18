<?php

declare(strict_types=1);

namespace Drupal\jaraba_security_compliance\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad EnsCompliance.
 *
 * LOGICA:
 * - view: requiere 'view ens compliance' o 'administer security compliance'
 * - update/delete: requiere 'administer security compliance'
 * - create: requiere 'administer security compliance'
 */
class EnsComplianceAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    return match ($operation) {
      'view' => AccessResult::allowedIfHasPermissions($account, [
        'administer security compliance',
        'view ens compliance',
      ], 'OR'),
      'update', 'delete' => AccessResult::allowedIfHasPermission($account, 'administer security compliance'),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'administer security compliance');
  }

}
