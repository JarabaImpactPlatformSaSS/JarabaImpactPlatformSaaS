<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Access control handler for Support entities.
 *
 * TENANT-ISOLATION-ACCESS-001: Verifies that the entity's tenant matches
 * the user's tenant for update/delete operations.
 *
 * Implements EntityHandlerInterface for dependency injection (canonical pattern).
 */
class SupportTicketAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * Constructs a SupportTicketAccessControlHandler.
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    private readonly ?TenantContextService $tenantContext = NULL,
  ) {
    parent::__construct($entity_type);
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
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // Admin bypass.
    $admin = AccessResult::allowedIfHasPermission($account, 'administer support system');
    if ($admin->isAllowed()) {
      return $admin;
    }

    $is_owner = $entity->hasField('reporter_uid')
      ? (int) $entity->get('reporter_uid')->target_id === (int) $account->id()
      : ((method_exists($entity, 'getOwnerId')) ? (int) $entity->getOwnerId() === (int) $account->id() : FALSE);

    switch ($operation) {
      case 'view':
        // Platform admins / support leads can view all.
        if ($account->hasPermission('view all support tickets')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        // Tenant admins can view all tenant tickets.
        if ($account->hasPermission('view tenant support tickets') && $this->isSameTenant($entity, $account)) {
          return AccessResult::allowed()
            ->cachePerPermissions()
            ->cachePerUser()
            ->addCacheableDependency($entity);
        }
        // Users can view their own tickets.
        if ($is_owner) {
          return AccessResult::allowed()
            ->cachePerUser()
            ->addCacheableDependency($entity);
        }
        return AccessResult::forbidden()
          ->cachePerUser()
          ->addCacheableDependency($entity);

      case 'update':
        // TENANT-ISOLATION-ACCESS-001: Tenant check FIRST.
        if ($entity->hasField('tenant_id') && !$this->isSameTenant($entity, $account)) {
          return AccessResult::forbidden('Cross-tenant update forbidden')
            ->cachePerPermissions()
            ->cachePerUser()
            ->addCacheableDependency($entity);
        }
        if ($account->hasPermission('edit any support ticket')) {
          return AccessResult::allowed()
            ->cachePerPermissions()
            ->addCacheableDependency($entity);
        }
        if ($account->hasPermission('edit own support ticket') && $is_owner) {
          return AccessResult::allowed()
            ->cachePerPermissions()
            ->cachePerUser()
            ->addCacheableDependency($entity);
        }
        return AccessResult::forbidden()
          ->cachePerPermissions()
          ->cachePerUser();

      case 'delete':
        if ($entity->hasField('tenant_id') && !$this->isSameTenant($entity, $account)) {
          return AccessResult::forbidden('Cross-tenant delete forbidden')
            ->cachePerPermissions()
            ->cachePerUser()
            ->addCacheableDependency($entity);
        }
        return AccessResult::allowedIfHasPermission($account, 'delete support tickets')
          ->addCacheableDependency($entity);
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'create support ticket');
  }

  /**
   * Checks if the entity belongs to the same tenant as the user.
   */
  private function isSameTenant(EntityInterface $entity, AccountInterface $account): bool {
    if (!$this->tenantContext) {
      return TRUE;
    }

    if (!$entity->hasField('tenant_id') || $entity->get('tenant_id')->isEmpty()) {
      return TRUE;
    }

    try {
      $entityTenantId = (int) $entity->get('tenant_id')->target_id;
      $userTenantId = $this->tenantContext->getCurrentTenantId();

      if ($userTenantId === NULL) {
        return FALSE;
      }

      return $entityTenantId === $userTenantId;
    }
    catch (\Exception) {
      return FALSE;
    }
  }

}
