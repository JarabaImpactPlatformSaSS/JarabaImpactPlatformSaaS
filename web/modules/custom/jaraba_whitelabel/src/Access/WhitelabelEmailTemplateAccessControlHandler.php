<?php

declare(strict_types=1);

namespace Drupal\jaraba_whitelabel\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for WhitelabelEmailTemplate entities.
 */
class WhitelabelEmailTemplateAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    return match ($operation) {
      'view' => AccessResult::allowedIfHasPermissions($account, [
        'administer whitelabel',
        'manage email templates',
      ], 'OR'),
      'update', 'delete' => AccessResult::allowedIfHasPermission($account, 'administer whitelabel'),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer whitelabel',
      'manage email templates',
    ], 'OR');
  }

}
