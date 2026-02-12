<?php

declare(strict_types=1);

namespace Drupal\jaraba_whitelabel\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_whitelabel\Entity\WhitelabelReseller;
use Psr\Log\LoggerInterface;

/**
 * Reseller management service.
 *
 * Provides CRUD-style access to resellers and calculates commissions
 * based on managed tenant revenue.
 */
class ResellerManagerService {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The database connection.
   */
  protected Connection $database;

  /**
   * The logger channel.
   */
  protected LoggerInterface $logger;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    Connection $database,
    LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->logger = $logger;
  }

  /**
   * Loads a reseller by ID.
   *
   * @param int $id
   *   The reseller entity ID.
   *
   * @return array|null
   *   Reseller data array or NULL.
   */
  public function getReseller(int $id): ?array {
    try {
      $storage = $this->entityTypeManager->getStorage('whitelabel_reseller');
      $entity = $storage->load($id);

      if (!$entity instanceof WhitelabelReseller) {
        return NULL;
      }

      return $this->entityToArray($entity);
    }
    catch (\Throwable $e) {
      $this->logger->error('Error loading reseller @id: @message', [
        '@id' => $id,
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Finds a reseller by contact email.
   *
   * @param string $email
   *   The contact email address.
   *
   * @return array|null
   *   Reseller data array or NULL.
   */
  public function getResellerByEmail(string $email): ?array {
    try {
      $storage = $this->entityTypeManager->getStorage('whitelabel_reseller');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('contact_email', $email)
        ->range(0, 1)
        ->execute();

      if (empty($ids)) {
        return NULL;
      }

      $entity = $storage->load(reset($ids));
      if (!$entity instanceof WhitelabelReseller) {
        return NULL;
      }

      return $this->entityToArray($entity);
    }
    catch (\Throwable $e) {
      $this->logger->error('Error finding reseller by email @email: @message', [
        '@email' => $email,
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Calculates commissions for a reseller in a given period.
   *
   * @param int $resellerId
   *   The reseller entity ID.
   * @param string $period
   *   Period in Y-m format (e.g. 2026-02).
   *
   * @return array
   *   Commission summary with keys:
   *   - total_tenants: int
   *   - total_revenue: float
   *   - commission_earned: float
   *   - pending_payout: float
   */
  public function calculateCommissions(int $resellerId, string $period = ''): array {
    $summary = [
      'total_tenants' => 0,
      'total_revenue' => 0.0,
      'commission_earned' => 0.0,
      'pending_payout' => 0.0,
    ];

    try {
      $storage = $this->entityTypeManager->getStorage('whitelabel_reseller');
      $reseller = $storage->load($resellerId);

      if (!$reseller instanceof WhitelabelReseller) {
        return $summary;
      }

      $commissionRate = (float) ($reseller->get('commission_rate')->value ?? 0);
      $tenantRefs = $reseller->get('managed_tenant_ids')->referencedEntities();
      $summary['total_tenants'] = count($tenantRefs);

      if (empty($tenantRefs)) {
        return $summary;
      }

      $groupIds = [];
      foreach ($tenantRefs as $group) {
        $groupIds[] = $group->id();
      }

      // Look up tenant entities associated to these groups.
      $tenantStorage = $this->entityTypeManager->getStorage('tenant');
      $tenantIds = $tenantStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('group_id', $groupIds, 'IN')
        ->execute();

      if (empty($tenantIds)) {
        return $summary;
      }

      $tenants = $tenantStorage->loadMultiple($tenantIds);
      $totalRevenue = 0.0;

      foreach ($tenants as $tenant) {
        $plan = $tenant->get('plan_id')->entity ?? NULL;
        if ($plan) {
          $monthlyPrice = (float) ($plan->get('monthly_price')->value ?? 0);
          $totalRevenue += $monthlyPrice;
        }
      }

      $summary['total_revenue'] = round($totalRevenue, 2);
      $summary['commission_earned'] = round($totalRevenue * ($commissionRate / 100), 2);
      $summary['pending_payout'] = $summary['commission_earned'];
    }
    catch (\Throwable $e) {
      $this->logger->error('Error calculating commissions for reseller @id: @message', [
        '@id' => $resellerId,
        '@message' => $e->getMessage(),
      ]);
    }

    return $summary;
  }

  /**
   * Gets managed tenants for a reseller.
   *
   * @param int $resellerId
   *   The reseller entity ID.
   *
   * @return array
   *   Array of tenant data arrays with keys: name, plan, status, created.
   */
  public function getManagedTenants(int $resellerId): array {
    $tenants = [];

    try {
      $storage = $this->entityTypeManager->getStorage('whitelabel_reseller');
      $reseller = $storage->load($resellerId);

      if (!$reseller instanceof WhitelabelReseller) {
        return $tenants;
      }

      $tenantRefs = $reseller->get('managed_tenant_ids')->referencedEntities();

      foreach ($tenantRefs as $group) {
        $tenantData = [
          'name' => $group->label(),
          'plan' => '',
          'status' => 'active',
          'created' => '',
        ];

        $tenantStorage = $this->entityTypeManager->getStorage('tenant');
        $tenantEntities = $tenantStorage->getQuery()
          ->accessCheck(FALSE)
          ->condition('group_id', $group->id())
          ->range(0, 1)
          ->execute();

        if (!empty($tenantEntities)) {
          $tenantEntity = $tenantStorage->load(reset($tenantEntities));
          if ($tenantEntity) {
            $tenantData['status'] = $tenantEntity->get('subscription_status')->value ?? 'active';

            $plan = $tenantEntity->get('plan_id')->entity ?? NULL;
            $tenantData['plan'] = $plan ? $plan->label() : '';
          }
        }

        $tenants[] = $tenantData;
      }
    }
    catch (\Throwable $e) {
      $this->logger->error('Error loading managed tenants for reseller @id: @message', [
        '@id' => $resellerId,
        '@message' => $e->getMessage(),
      ]);
    }

    return $tenants;
  }

  /**
   * Converts a WhitelabelReseller entity to a data array.
   *
   * @param \Drupal\jaraba_whitelabel\Entity\WhitelabelReseller $entity
   *   The reseller entity.
   *
   * @return array
   *   Reseller data.
   */
  protected function entityToArray(WhitelabelReseller $entity): array {
    return [
      'id' => (int) $entity->id(),
      'name' => $entity->get('name')->value,
      'company_name' => $entity->get('company_name')->value,
      'contact_email' => $entity->get('contact_email')->value,
      'commission_rate' => (float) ($entity->get('commission_rate')->value ?? 0),
      'territory' => $entity->getDecodedTerritory(),
      'reseller_status' => $entity->get('reseller_status')->value,
      'revenue_share_model' => $entity->get('revenue_share_model')->value,
    ];
  }

}
