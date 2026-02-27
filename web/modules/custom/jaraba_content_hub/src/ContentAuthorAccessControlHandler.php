<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Controlador de acceso para la entidad ContentAuthor.
 *
 * PROPOSITO:
 * Los perfiles de autor son visibles publicamente en el blog
 * (pagina de autor, bio card en articulos) pero solo los
 * administradores pueden crear/editar/eliminar.
 *
 * REGLAS:
 * - view: Permitido con 'view content authors' o 'access content'.
 * - update/delete: Requiere 'administer content authors' + tenant isolation.
 * - create: Requiere 'administer content authors'.
 *
 * TENANT-ISOLATION-ACCESS-001: Verificacion de tenant para escritura.
 *
 * ESPECIFICACION: Plan Consolidacion Content Hub + Blog v1 — Seccion 7.10
 */
class ContentAuthorAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // Administradores tienen acceso completo.
    if ($account->hasPermission('administer content authors')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    // TENANT-ISOLATION-ACCESS-001: Verificar tenant para update/delete.
    if (in_array($operation, ['update', 'delete'], TRUE)) {
      $tenantMismatch = $this->checkTenantIsolation($entity, $account);
      if ($tenantMismatch !== NULL) {
        return $tenantMismatch;
      }
    }

    switch ($operation) {
      case 'view':
        // Los perfiles de autor son publicos en el blog.
        return AccessResult::allowedIfHasPermissions($account, [
          'view content authors',
          'access content',
        ], 'OR');

      case 'update':
      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'administer content authors');
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'administer content authors');
  }

  /**
   * Verifica aislamiento de tenant (TENANT-ISOLATION-ACCESS-001).
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return \Drupal\Core\Access\AccessResult|null
   *   AccessResult::forbidden() if tenant mismatch, NULL to continue.
   */
  protected function checkTenantIsolation(EntityInterface $entity, AccountInterface $account): ?AccessResult {
    if (!$entity->hasField('tenant_id')) {
      return NULL;
    }

    $entityTenantId = (int) ($entity->get('tenant_id')->target_id ?? 0);
    if ($entityTenantId === 0) {
      return NULL;
    }

    try {
      if (!\Drupal::hasService('ecosistema_jaraba_core.tenant_context')) {
        return NULL;
      }
      /** @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService $tenantContext */
      $tenantContext = \Drupal::service('ecosistema_jaraba_core.tenant_context');
      $currentTenant = $tenantContext->getCurrentTenant();
      if ($currentTenant === NULL) {
        return NULL;
      }
      $userTenantId = (int) $currentTenant->id();

      if ($entityTenantId !== $userTenantId) {
        return AccessResult::forbidden('Tenant mismatch: author belongs to a different tenant.')
          ->addCacheableDependency($entity)
          ->cachePerUser();
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('jaraba_content_hub')->warning(
        'Tenant isolation check failed for author @id: @error',
        ['@id' => $entity->id(), '@error' => $e->getMessage()]
      );
      return NULL;
    }

    return NULL;
  }

}
