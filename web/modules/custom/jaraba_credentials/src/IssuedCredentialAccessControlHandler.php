<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResultInterface;

/**
 * Access control handler para IssuedCredential.
 */
class IssuedCredentialAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    /** @var \Drupal\jaraba_credentials\Entity\IssuedCredential $entity */

    // Administradores tienen acceso completo.
    if ($account->hasPermission('administer credentials')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    // Usuarios pueden ver sus propias credenciales.
    if ($operation === 'view') {
      $recipientId = $entity->get('recipient_id')->target_id ?? NULL;
      if ($recipientId && (int) $recipientId === (int) $account->id()) {
        return AccessResult::allowed()
          ->cachePerUser()
          ->addCacheableDependency($entity);
      }
    }

    return AccessResult::forbidden()->cachePerPermissions();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResultInterface {
    return AccessResult::allowedIfHasPermission($account, 'issue credentials');
  }

}
