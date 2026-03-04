<?php

declare(strict_types=1);

namespace Drupal\jaraba_social\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad SocialPostVariant.
 *
 * PROPOSITO:
 * Gestiona permisos de lectura, edicion y eliminacion de variantes de posts.
 *
 * LOGICA:
 * - admin: requiere 'administer social media' (acceso total)
 * - view: requiere 'view social media'
 * - edit/delete/create: requiere 'manage social media'
 */
class SocialPostVariantAccessControlHandler extends DefaultEntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    // TENANT-ISOLATION-ACCESS-001: Tenant isolation via parent.
    $parentResult = parent::checkAccess($entity, $operation, $account);
    if ($parentResult->isForbidden()) {
      return $parentResult;
    }

    if ($account->hasPermission('administer social media')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    return match ($operation) {
      'view' => AccessResult::allowedIfHasPermission($account, 'view social media'),
      'update', 'delete' => AccessResult::allowedIfHasPermission($account, 'manage social media'),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer social media',
      'manage social media',
    ], 'OR');
  }

}
