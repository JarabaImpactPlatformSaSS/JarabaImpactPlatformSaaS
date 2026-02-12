<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad KbCategory.
 *
 * PROPÓSITO:
 * Gestiona permisos para categorías de la base de conocimiento.
 *
 * LÓGICA:
 * - Admin global puede todo
 * - view: requiere 'view knowledge base'
 * - update/delete: requiere 'manage kb categories'
 * - create: requiere 'manage kb categories'
 */
class KbCategoryAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($account->hasPermission('administer tenant knowledge')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    return match ($operation) {
      'view' => AccessResult::allowedIfHasPermission($account, 'view knowledge base'),
      'update', 'delete' => AccessResult::allowedIfHasPermission($account, 'manage kb categories'),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'manage kb categories');
  }

}
