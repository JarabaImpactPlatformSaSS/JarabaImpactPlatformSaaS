<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Access control handler para ExpedienteDocumento.
 *
 * TENANT-ISOLATION-ACCESS-001: Verifica tenant_id en update/delete.
 * Implements EntityHandlerInterface for DI (TenantContextService).
 *
 * Permisos:
 * - view: participante owner + revisor asignado + admin
 * - update: solo revisor + admin (participante NO puede editar revisión)
 * - delete: solo admin
 * - create: participante owner + admin
 */
class ExpedienteDocumentoAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * The tenant context service.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService|null
   */
  protected ?TenantContextService $tenantContext;

  /**
   * Constructs an ExpedienteDocumentoAccessControlHandler.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\ecosistema_jaraba_core\Service\TenantContextService|null $tenant_context
   *   The tenant context service.
   */
  public function __construct(EntityTypeInterface $entity_type, ?TenantContextService $tenant_context = NULL) {
    parent::__construct($entity_type);
    $this->tenantContext = $tenant_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): static {
    return new static(
      $entity_type,
      $container->has('ecosistema_jaraba_core.tenant_context')
        ? $container->get('ecosistema_jaraba_core.tenant_context')
        : NULL,
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    $admin_permission = $this->entityType->getAdminPermission();

    // Admin tiene acceso completo.
    if ($account->hasPermission($admin_permission)) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    // ACCESS-STRICT-001: Strict integer comparison for owner check.
    $is_owner = (int) $entity->getOwnerId() === (int) $account->id();

    // Check if user is the assigned reviewer.
    $is_reviewer = FALSE;
    if ($entity->hasField('revisor_id') && !$entity->get('revisor_id')->isEmpty()) {
      $is_reviewer = (int) $entity->get('revisor_id')->target_id === (int) $account->id();
    }

    switch ($operation) {
      case 'view':
        // Owner (participante que subió) + reviewer + view permission.
        if ($is_owner || $is_reviewer) {
          return AccessResult::allowed()
            ->cachePerUser()
            ->addCacheableDependency($entity);
        }
        return AccessResult::allowedIfHasPermission($account, 'view expediente documento')
          ->addCacheableDependency($entity);

      case 'update':
        // TENANT-ISOLATION-ACCESS-001: Tenant check obligatorio.
        if (!$this->isSameTenant($entity, $account)) {
          return AccessResult::forbidden('Cross-tenant update forbidden')
            ->cachePerPermissions()
            ->cachePerUser()
            ->addCacheableDependency($entity);
        }
        // Only reviewer + admin can update (participante cannot edit review).
        if ($is_reviewer && $account->hasPermission('edit expediente documento')) {
          return AccessResult::allowed()
            ->cachePerPermissions()
            ->cachePerUser()
            ->addCacheableDependency($entity);
        }
        return AccessResult::allowedIfHasPermission($account, 'edit expediente documento')
          ->addCacheableDependency($entity);

      case 'delete':
        // TENANT-ISOLATION-ACCESS-001: Tenant check obligatorio.
        if (!$this->isSameTenant($entity, $account)) {
          return AccessResult::forbidden('Cross-tenant delete forbidden')
            ->cachePerPermissions()
            ->cachePerUser()
            ->addCacheableDependency($entity);
        }
        // Solo admin puede eliminar documentos.
        return AccessResult::allowedIfHasPermission($account, 'delete expediente documento')
          ->addCacheableDependency($entity);

      default:
        return AccessResult::neutral();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResultInterface {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer andalucia ei',
      'create expediente documento',
    ], 'OR');
  }

  /**
   * Checks if the entity belongs to the same tenant as the user.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return bool
   *   TRUE if same tenant or entity has no tenant_id (legacy).
   */
  protected function isSameTenant(EntityInterface $entity, AccountInterface $account): bool {
    if (!$this->tenantContext) {
      return TRUE;
    }

    $entityTenantId = NULL;
    if ($entity->hasField('tenant_id') && !$entity->get('tenant_id')->isEmpty()) {
      $entityTenantId = (int) $entity->get('tenant_id')->target_id;
    }

    // Legacy entities without tenant_id — accessible for backwards compat.
    if ($entityTenantId === NULL) {
      return TRUE;
    }

    $userTenantId = $this->tenantContext->getCurrentTenantId();

    if ($userTenantId === NULL) {
      return FALSE;
    }

    return $entityTenantId === $userTenantId;
  }

}
