<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the Mentoring Session entity.
 *
 * TENANT-ISOLATION-ACCESS-001: Verifies tenant match for update/delete.
 */
class MentoringSessionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    /** @var \Drupal\jaraba_mentoring\Entity\MentoringSession $entity */

    // Admin permission grants full access.
    if ($account->hasPermission('manage sessions')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    // TENANT-ISOLATION-ACCESS-001: Verify tenant match for update/delete.
    if (in_array($operation, ['update', 'delete'], TRUE)) {
      $tenantCheck = $this->checkTenantIsolation($entity, $account);
      if ($tenantCheck !== NULL) {
        return $tenantCheck;
      }
    }

    // Check if user is the mentor or mentee of the session.
    $mentor = $entity->get('mentor_id')->entity;
    $mentee_id = (int) $entity->get('mentee_id')->target_id;

    $is_mentor = $mentor && (int) $mentor->get('user_id')->target_id === (int) $account->id();
    $is_mentee = $mentee_id === (int) $account->id();

    switch ($operation) {
      case 'view':
        // Mentor y mentee pueden ver sus propias sesiones.
        if ($is_mentor || $is_mentee) {
          return AccessResult::allowed()
            ->cachePerUser()
            ->addCacheableDependency($entity);
        }
        return AccessResult::neutral();

      case 'update':
        // Solo mentor puede actualizar (notas, estado).
        if ($is_mentor || $account->hasPermission('edit any session')) {
          return AccessResult::allowed()
            ->cachePerUser()
            ->addCacheableDependency($entity);
        }
        return AccessResult::neutral();

      case 'delete':
        // Solo admins pueden eliminar.
        return AccessResult::neutral();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResultInterface {
    // Solo mentores y admins pueden crear sesiones.
    if ($account->hasPermission('manage sessions') || $account->hasPermission('book sessions')) {
      return AccessResult::allowed()->cachePerPermissions();
    }
    return AccessResult::neutral();
  }

  /**
   * Verifies tenant isolation (TENANT-ISOLATION-ACCESS-001).
   *
   * @return \Drupal\Core\Access\AccessResult|null
   *   Forbidden if mismatch, NULL if no check needed.
   */
  protected function checkTenantIsolation(EntityInterface $entity, AccountInterface $account): ?AccessResult {
    if (!$entity->hasField('tenant_id')) {
      return NULL;
    }

    $tenantField = $entity->get('tenant_id');
    $entityTenantId = (int) ($tenantField->target_id ?? $tenantField->value ?? 0);

    if ($entityTenantId === 0) {
      return NULL;
    }

    if (!\Drupal::hasService('ecosistema_jaraba_core.tenant_context')) {
      return NULL;
    }

    try {
      /** @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService $tenantContext */
      $tenantContext = \Drupal::service('ecosistema_jaraba_core.tenant_context');
      $currentTenant = $tenantContext->getCurrentTenant();

      if (!$currentTenant) {
        return NULL;
      }

      $userTenantId = (int) $currentTenant->id();

      // ACCESS-STRICT-001: Strict integer comparison.
      if ($entityTenantId !== $userTenantId) {
        return AccessResult::forbidden('Tenant mismatch: entity belongs to a different tenant.')
          ->addCacheContexts(['user'])
          ->addCacheableDependency($entity);
      }
    }
    catch (\Throwable) {
      return NULL;
    }

    return NULL;
  }

}
