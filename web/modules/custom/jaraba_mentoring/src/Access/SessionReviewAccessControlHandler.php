<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Control de acceso para session_review con verificacion de tenant.
 *
 * REV-S2/REV-D4: Nuevo handler — la entidad no tenia access handler.
 * TENANT-ISOLATION-ACCESS-001: update/delete requieren mismo tenant.
 */
class SessionReviewAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

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
    if ($account->hasPermission('manage sessions')) {
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
      'update' => $this->checkUpdateAccess($entity, $account),
      'delete' => AccessResult::neutral()->cachePerPermissions(),
      default => AccessResult::neutral(),
    };
  }

  protected function checkViewAccess(EntityInterface $entity, AccountInterface $account): AccessResult {
    // Reviews aprobadas son publicas.
    $status = NULL;
    if ($entity->hasField('review_status') && !$entity->get('review_status')->isEmpty()) {
      $status = $entity->get('review_status')->value;
    }
    if ($status === 'approved') {
      return AccessResult::allowed()->addCacheableDependency($entity);
    }
    // El reviewer o reviewee puede ver la review en cualquier estado.
    $userId = (int) $account->id();
    if ($entity->hasField('reviewer_id') && (int) $entity->get('reviewer_id')->target_id === $userId) {
      return AccessResult::allowed()->cachePerUser()->addCacheableDependency($entity);
    }
    if ($entity->hasField('reviewee_id') && (int) $entity->get('reviewee_id')->target_id === $userId) {
      return AccessResult::allowed()->cachePerUser()->addCacheableDependency($entity);
    }
    return AccessResult::neutral()->cachePerUser()->addCacheableDependency($entity);
  }

  protected function checkUpdateAccess(EntityInterface $entity, AccountInterface $account): AccessResult {
    // Solo el reviewer puede editar su propia review.
    if ($entity->hasField('reviewer_id') && (int) $entity->get('reviewer_id')->target_id === (int) $account->id()) {
      return AccessResult::allowedIfHasPermission($account, 'edit own reviews')
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
      'manage sessions',
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
