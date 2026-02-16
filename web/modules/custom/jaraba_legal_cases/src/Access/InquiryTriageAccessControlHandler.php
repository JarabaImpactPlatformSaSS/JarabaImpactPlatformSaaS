<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_cases\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad InquiryTriage.
 *
 * Estructura: Extiende EntityAccessControlHandler para triajes IA.
 *
 * Logica: Solo usuarios con permiso de gestion de consultas pueden
 *   ver y crear triajes. Los triajes son de solo lectura una vez creados.
 */
class InquiryTriageAccessControlHandler extends EntityAccessControlHandler {

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
        // Triajes son solo-lectura una vez creados.
        return AccessResult::neutral();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'manage legal inquiries');
  }

}
