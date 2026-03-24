<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para StaffProfileEi.
 *
 * ACCESS-RETURN-TYPE-001: checkAccess() retorna AccessResultInterface.
 * TENANT-ISOLATION-ACCESS-001: Verifica tenant match en update/delete.
 */
class StaffProfileEiAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    if ($account->hasPermission('administer andalucia ei')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $is_owner = $entity->hasField('user_id')
      && (int) $entity->get('user_id')->target_id === $account->id();

    switch ($operation) {
      case 'view':
        // Owner puede ver su propio perfil.
        if ($is_owner) {
          return AccessResult::allowed()
            ->addCacheableDependency($entity)
            ->cachePerUser();
        }
        return AccessResult::allowedIfHasPermission($account, 'administer andalucia ei')
          ->addCacheableDependency($entity)
          ->cachePerPermissions();

      case 'update':
        // TENANT-ISOLATION-ACCESS-001: verificar tenant match.
        $tenant_match = $this->checkTenantMatch($entity, $account);
        if (!$tenant_match) {
          return AccessResult::forbidden('Tenant mismatch.')
            ->addCacheableDependency($entity)
            ->cachePerUser();
        }
        if ($is_owner && $account->hasPermission('edit own staff profile')) {
          return AccessResult::allowed()
            ->addCacheableDependency($entity)
            ->cachePerUser();
        }
        return AccessResult::neutral();

      case 'delete':
        // TENANT-ISOLATION-ACCESS-001: verificar tenant match.
        $tenant_match = $this->checkTenantMatch($entity, $account);
        if (!$tenant_match) {
          return AccessResult::forbidden('Tenant mismatch.')
            ->addCacheableDependency($entity)
            ->cachePerUser();
        }
        // Solo administradores pueden eliminar.
        return AccessResult::neutral();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResultInterface {
    return AccessResult::allowedIfHasPermissions($account, ['administer andalucia ei', 'assign andalucia ei roles'], 'OR');
  }

  /**
   * Verifica que el usuario pertenece al mismo tenant que la entidad.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   La entidad a verificar.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   La cuenta del usuario.
   *
   * @return bool
   *   TRUE si el tenant coincide o no hay tenant asignado.
   */
  protected function checkTenantMatch(EntityInterface $entity, AccountInterface $account): bool {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    if (!$entity->hasField('tenant_id') || $entity->get('tenant_id')->isEmpty()) {
      return TRUE;
    }

    $entity_tenant_id = (int) $entity->get('tenant_id')->target_id;
    if ($entity_tenant_id === 0) {
      return TRUE;
    }

    // Intentar resolver tenant del usuario via servicio.
    try {
      if (\Drupal::hasService('ecosistema_jaraba_core.tenant_context')) {
        /** @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService $tenant_context */
        $tenant_context = \Drupal::service('ecosistema_jaraba_core.tenant_context');
        $current_tenant_id = (int) $tenant_context->getCurrentTenantId();
        return $current_tenant_id === $entity_tenant_id;
      }
    }
    catch (\Throwable $e) {
      // Si el servicio falla, permitir acceso (fail-open con log).
      \Drupal::logger('jaraba_andalucia_ei')->warning('Tenant match check failed: @message', [
        '@message' => $e->getMessage(),
      ]);
    }

    return TRUE;
  }

}
