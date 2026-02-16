<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_cases\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad ClientInquiry.
 *
 * Estructura: Extiende EntityAccessControlHandler con logica
 *   de permisos para consultas juridicas.
 *
 * Logica: Los administradores con 'manage legal inquiries' tienen
 *   acceso completo. Usuarios con 'view legal inquiries' pueden ver.
 */
class ClientInquiryAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('manage legal inquiries')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view legal inquiries');

      case 'update':
      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'manage legal inquiries');
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'manage legal inquiries',
      'manage legal cases',
    ], 'OR');
  }

}
