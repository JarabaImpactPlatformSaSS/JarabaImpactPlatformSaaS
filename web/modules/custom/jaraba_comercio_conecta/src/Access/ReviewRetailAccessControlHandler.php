<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Control de acceso para comercio_review con verificacion de tenant.
 *
 * Implementa EntityHandlerInterface para DI de TenantContextService.
 * TENANT-ISOLATION-ACCESS-001: update/delete requieren mismo tenant.
 * ACCESS-STRICT-001: comparaciones con (int) cast.
 */
class ReviewRetailAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  public function __construct(
    EntityTypeInterface $entity_type,
    protected readonly TenantContextService $tenantContext,
  ) {
    parent::__construct($entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): static {
    return new static(
      $entity_type,
      $container->get('ecosistema_jaraba_core.tenant_context'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    // Admin bypass.
    if ($account->hasPermission('manage comercio reviews')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    // TENANT-ISOLATION-ACCESS-001: tenant mismatch deniega update/delete.
    if (in_array($operation, ['update', 'delete'], TRUE)) {
      $tenantMismatch = $this->checkTenantIsolation($entity);
      if ($tenantMismatch !== NULL) {
        return $tenantMismatch;
      }
    }

    return match ($operation) {
      'view' => $this->checkViewAccess($entity, $account),
      'update' => $this->checkUpdateAccess($entity, $account),
      'delete' => AccessResult::neutral()->cachePerPermissions(),
      default => AccessResult::neutral(),
    };
  }

  /**
   * Reviews aprobadas son publicas. Owner ve las suyas en cualquier estado.
   */
  protected function checkViewAccess(EntityInterface $entity, AccountInterface $account): AccessResult {
    $status = $entity->hasField('status') ? $entity->get('status')->value : NULL;
    if ($status === 'approved') {
      return AccessResult::allowed()->addCacheableDependency($entity);
    }
    if ((int) $entity->getOwnerId() === (int) $account->id()) {
      return AccessResult::allowed()->cachePerUser()->addCacheableDependency($entity);
    }
    return AccessResult::neutral()->cachePerUser()->addCacheableDependency($entity);
  }

  /**
   * Owner puede editar si tiene permiso.
   */
  protected function checkUpdateAccess(EntityInterface $entity, AccountInterface $account): AccessResult {
    if ((int) $entity->getOwnerId() === (int) $account->id()) {
      return AccessResult::allowedIfHasPermission($account, 'edit own comercio reviews')
        ->cachePerUser()
        ->addCacheableDependency($entity);
    }
    return AccessResult::neutral()->cachePerPermissions();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'manage comercio reviews',
      'submit reviews',
    ], 'OR');
  }

  /**
   * Verifica aislamiento de tenant (TENANT-ISOLATION-ACCESS-001).
   */
  protected function checkTenantIsolation(EntityInterface $entity): ?AccessResult {
    if (!$entity->hasField('tenant_id') || $entity->get('tenant_id')->isEmpty()) {
      return NULL;
    }
    $entityGroupId = (int) $entity->get('tenant_id')->target_id;
    if ($entityGroupId === 0) {
      return NULL;
    }
    try {
      $currentTenant = $this->tenantContext->getCurrentTenant();
      if ($currentTenant === NULL) {
        return NULL;
      }
      // Resolver group_id del tenant para comparar con entity's tenant_id (que apunta a group).
      $currentGroupId = $currentTenant->hasField('group_id')
        ? (int) $currentTenant->get('group_id')->target_id
        : (int) $currentTenant->id();
      if ($currentGroupId > 0 && $entityGroupId !== $currentGroupId) {
        return AccessResult::forbidden('Tenant mismatch.')
          ->addCacheableDependency($entity)
          ->cachePerUser();
      }
    }
    catch (\Exception) {
      return NULL;
    }
    return NULL;
  }

}
