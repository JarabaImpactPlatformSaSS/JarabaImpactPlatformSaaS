<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para SecureConversation y ConversationParticipant.
 *
 * LÓGICA:
 * - View: requiere 'view own conversations' o 'manage all conversations'.
 * - Update/Delete: requiere 'administer jaraba messaging'.
 * - Create: requiere 'create conversations'.
 */
class SecureConversationAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    return match ($operation) {
      'view' => AccessResult::allowedIfHasPermissions($account, [
        'view own conversations',
        'manage all conversations',
      ], 'OR'),
      'update', 'delete' => AccessResult::allowedIfHasPermission($account, 'administer jaraba messaging'),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'create conversations',
      'administer jaraba messaging',
    ], 'OR');
  }

}
