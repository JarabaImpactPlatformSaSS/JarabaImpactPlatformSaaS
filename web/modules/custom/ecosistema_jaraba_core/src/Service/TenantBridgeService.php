<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;
use Drupal\group\Entity\GroupInterface;
use Psr\Log\LoggerInterface;

/**
 * Bridge service between Tenant entities (billing) and Group entities (content).
 *
 * TENANT-BRIDGE-001: Every service that needs to resolve between Tenant and
 * Group entities MUST use this service. NEVER load getStorage('group') with
 * Tenant IDs or vice-versa.
 *
 * Tenant entities own subscription/billing data (plan, status, Stripe IDs).
 * Group entities own content isolation (memberships, group content).
 * The link is Tenant->group_id field referencing the Group entity.
 */
class TenantBridgeService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Loads a Tenant entity by ID.
   *
   * @param int $id
   *   The tenant entity ID.
   *
   * @return \Drupal\ecosistema_jaraba_core\Entity\TenantInterface|null
   *   The tenant entity or NULL if not found.
   */
  public function loadTenant(int $id): ?TenantInterface {
    try {
      $entity = $this->entityTypeManager->getStorage('tenant')->load($id);
      return $entity instanceof TenantInterface ? $entity : NULL;
    }
    catch (\Exception $e) {
      $this->logger->error('TenantBridge: Error loading tenant @id: @error', [
        '@id' => $id,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Gets the Group entity associated with a Tenant.
   *
   * @param int $tenantId
   *   The tenant entity ID.
   *
   * @return \Drupal\group\Entity\GroupInterface|null
   *   The group entity or NULL.
   */
  public function getGroupForTenant(int $tenantId): ?GroupInterface {
    $tenant = $this->loadTenant($tenantId);
    if (!$tenant) {
      return NULL;
    }

    try {
      return $tenant->getGroup();
    }
    catch (\Exception $e) {
      $this->logger->error('TenantBridge: Error getting group for tenant @id: @error', [
        '@id' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Gets the Tenant entity that owns a given Group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   *
   * @return \Drupal\ecosistema_jaraba_core\Entity\TenantInterface|null
   *   The tenant entity or NULL.
   */
  public function getTenantForGroup(GroupInterface $group): ?TenantInterface {
    try {
      $storage = $this->entityTypeManager->getStorage('tenant');
      $tenants = $storage->loadByProperties([
        'group_id' => $group->id(),
      ]);

      if (!empty($tenants)) {
        $tenant = reset($tenants);
        return $tenant instanceof TenantInterface ? $tenant : NULL;
      }

      return NULL;
    }
    catch (\Exception $e) {
      $this->logger->error('TenantBridge: Error getting tenant for group @id: @error', [
        '@id' => $group->id(),
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Gets the Tenant entity for a user by admin_user or group membership.
   *
   * @param int $uid
   *   The user ID.
   *
   * @return \Drupal\ecosistema_jaraba_core\Entity\TenantInterface|null
   *   The tenant entity or NULL.
   */
  public function getTenantForUser(int $uid): ?TenantInterface {
    try {
      $storage = $this->entityTypeManager->getStorage('tenant');

      // Method 1: User is admin of tenant.
      $tenants = $storage->loadByProperties([
        'admin_user' => $uid,
      ]);

      if (!empty($tenants)) {
        $tenant = reset($tenants);
        return $tenant instanceof TenantInterface ? $tenant : NULL;
      }

      // Method 2: User is member of a Group that belongs to a Tenant.
      if ($this->entityTypeManager->hasDefinition('group_relationship')) {
        $relationshipStorage = $this->entityTypeManager->getStorage('group_relationship');
        $relationships = $relationshipStorage->loadByProperties([
          'plugin_id' => 'group_membership',
          'entity_id' => $uid,
        ]);

        foreach ($relationships as $relationship) {
          $group = $relationship->getGroup();
          if ($group) {
            $tenant = $this->getTenantForGroup($group);
            if ($tenant) {
              return $tenant;
            }
          }
        }
      }

      return NULL;
    }
    catch (\Exception $e) {
      $this->logger->error('TenantBridge: Error getting tenant for user @uid: @error', [
        '@uid' => $uid,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

}
