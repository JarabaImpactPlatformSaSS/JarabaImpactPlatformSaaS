<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_templates\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad GeneratedDocument.
 *
 * Estructura: Permisos generate legal documents para creacion,
 *   manage legal templates para edicion, owner-aware para lectura.
 */
class GeneratedDocumentAccessControlHandler extends DefaultEntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    // TENANT-ISOLATION-ACCESS-001: Tenant isolation via parent.
    $parentResult = parent::checkAccess($entity, $operation, $account);
    if ($parentResult->isForbidden()) {
      return $parentResult;
    }

    if ($account->hasPermission('administer legal templates')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    return match ($operation) {
      'view' => $this->checkViewAccess($entity, $account),
      'update' => AccessResult::allowedIfHasPermission($account, 'manage legal templates'),
      'delete' => AccessResult::allowedIfHasPermission($account, 'manage legal templates'),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'generate legal documents');
  }

  /**
   * Comprueba acceso de lectura: propietario o permiso general.
   */
  protected function checkViewAccess(EntityInterface $entity, AccountInterface $account): AccessResult {
    $isOwner = (int) $entity->getOwnerId() === (int) $account->id();

    if ($isOwner) {
      return AccessResult::allowed()
        ->cachePerUser()
        ->addCacheableDependency($entity);
    }

    return AccessResult::allowedIfHasPermission($account, 'access legal templates')
      ->addCacheableDependency($entity);
  }

}
