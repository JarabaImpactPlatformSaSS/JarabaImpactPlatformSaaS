<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Control de acceso para review_agro con verificacion de tenant.
 *
 * REV-A5: Movido de Entity\ a Access\ namespace.
 * TENANT-ISOLATION-ACCESS-001: update/delete requieren mismo tenant.
 */
class ReviewAgroAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

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
    /** @var \Drupal\jaraba_agroconecta_core\Entity\ReviewAgro $entity */
    $admin_permission = $this->entityType->getAdminPermission();

    if ($account->hasPermission($admin_permission)) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    // TENANT-ISOLATION-ACCESS-001.
    if (in_array($operation, ['update', 'delete'], TRUE)) {
      $tenantMismatch = $this->checkTenantIsolation($entity);
      if ($tenantMismatch !== NULL) {
        return $tenantMismatch;
      }
    }

    return match ($operation) {
      'view' => $this->checkViewAccess($entity, $account),
      'update' => AccessResult::allowedIfHasPermission($account, 'manage agro reviews'),
      'delete' => AccessResult::allowedIfHasPermission($account, 'manage agro reviews'),
      default => AccessResult::neutral(),
    };
  }

  protected function checkViewAccess(EntityInterface $entity, AccountInterface $account): AccessResult {
    $status = $entity->hasField('state') ? $entity->get('state')->value : NULL;
    if ($status === 'approved') {
      return AccessResult::allowedIfHasPermission($account, 'view agro reviews')
        ->addCacheableDependency($entity);
    }
    if ((int) $entity->getOwnerId() === (int) $account->id()) {
      return AccessResult::allowed()->cachePerUser()->addCacheableDependency($entity);
    }
    return AccessResult::allowedIfHasPermission($account, 'manage agro reviews');
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      $this->entityType->getAdminPermission(),
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
