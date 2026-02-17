<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_templates\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad LegalTemplate.
 *
 * Estructura: Permisos manage legal templates para CRUD,
 *   access legal templates para lectura.
 */
class LegalTemplateAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer legal templates')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    return match ($operation) {
      'view' => AccessResult::allowedIfHasPermission($account, 'access legal templates'),
      'update' => $this->checkUpdateAccess($entity, $account),
      'delete' => $this->checkDeleteAccess($entity, $account),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'manage legal templates');
  }

  /**
   * Comprueba acceso de edicion — no se puede editar templates del sistema.
   */
  protected function checkUpdateAccess(EntityInterface $entity, AccountInterface $account): AccessResult {
    if (!$account->hasPermission('manage legal templates')) {
      return AccessResult::forbidden()->cachePerPermissions();
    }

    return AccessResult::allowed()
      ->cachePerPermissions()
      ->addCacheableDependency($entity);
  }

  /**
   * Comprueba acceso de eliminacion — no se puede eliminar templates del sistema.
   */
  protected function checkDeleteAccess(EntityInterface $entity, AccountInterface $account): AccessResult {
    if (!$account->hasPermission('manage legal templates')) {
      return AccessResult::forbidden()->cachePerPermissions();
    }

    if ($entity->get('is_system')->value) {
      return AccessResult::forbidden('Las plantillas del sistema no se pueden eliminar.')
        ->addCacheableDependency($entity);
    }

    return AccessResult::allowed()
      ->cachePerPermissions()
      ->addCacheableDependency($entity);
  }

}
