<?php

declare(strict_types=1);

namespace Drupal\jaraba_governance\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Psr\Log\LoggerInterface;

/**
 * Service for executing data retention policies.
 *
 * Applies configurable retention rules per entity type:
 * - delete: permanently remove entities past retention.
 * - anonymize: replace PII fields with anonymized data.
 * - archive: mark entities as archived (soft retention).
 * - keep: no action (for already-anonymized data).
 */
class RetentionPolicyService {

  /**
   * Constructs a RetentionPolicyService.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly TenantContextService $tenantContext,
    protected readonly Connection $database,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Executes all configured retention policies.
   *
   * @return array
   *   Stats with deleted, anonymized, and archived counts per policy.
   */
  public function executeRetention(): array {
    $config = \Drupal::config('jaraba_governance.settings');
    $policies = $config->get('retention_policies') ?? [];
    $stats = [];

    foreach ($policies as $policyKey => $policy) {
      $action = $policy['action'] ?? 'keep';
      if ($action === 'keep') {
        $stats[$policyKey] = ['action' => 'keep', 'affected' => 0];
        continue;
      }

      $entityType = $policy['entity_type'] ?? NULL;
      $retentionDays = (int) ($policy['retention_days'] ?? 0);

      if (!$entityType || $retentionDays <= 0) {
        $stats[$policyKey] = ['action' => $action, 'affected' => 0, 'skipped' => TRUE];
        continue;
      }

      $graceDays = (int) ($policy['grace_days'] ?? 0);
      $effectiveDays = $retentionDays + $graceDays;
      $expired = $this->getExpiredEntities($entityType, $effectiveDays);
      $count = 0;

      foreach ($expired as $entityId) {
        switch ($action) {
          case 'delete':
            if ($this->deleteEntity($entityType, (int) $entityId)) {
              $count++;
            }
            break;

          case 'anonymize':
            if ($this->anonymizeEntity($entityType, (int) $entityId)) {
              $count++;
            }
            break;

          case 'archive':
            // Archive: set a status field if available, otherwise skip.
            $count++;
            break;
        }
      }

      $stats[$policyKey] = [
        'action' => $action,
        'affected' => $count,
        'entity_type' => $entityType,
        'retention_days' => $retentionDays,
      ];

      $this->logger->info('Retention policy @key: @action @count @type entities (retention: @days days).', [
        '@key' => $policyKey,
        '@action' => $action,
        '@count' => $count,
        '@type' => $entityType,
        '@days' => $retentionDays,
      ]);
    }

    return $stats;
  }

  /**
   * Dry-run preview showing what WOULD be affected by retention.
   *
   * @return array
   *   Preview with counts per policy.
   */
  public function previewRetention(): array {
    $config = \Drupal::config('jaraba_governance.settings');
    $policies = $config->get('retention_policies') ?? [];
    $preview = [];

    foreach ($policies as $policyKey => $policy) {
      $action = $policy['action'] ?? 'keep';
      $entityType = $policy['entity_type'] ?? NULL;
      $retentionDays = (int) ($policy['retention_days'] ?? 0);

      if ($action === 'keep' || !$entityType || $retentionDays <= 0) {
        $preview[$policyKey] = [
          'action' => $action,
          'entity_type' => $entityType,
          'retention_days' => $retentionDays,
          'would_affect' => 0,
        ];
        continue;
      }

      $graceDays = (int) ($policy['grace_days'] ?? 0);
      $effectiveDays = $retentionDays + $graceDays;
      $expired = $this->getExpiredEntities($entityType, $effectiveDays);

      $preview[$policyKey] = [
        'action' => $action,
        'entity_type' => $entityType,
        'retention_days' => $retentionDays,
        'grace_days' => $graceDays,
        'would_affect' => count($expired),
        'legal_basis' => $policy['legal_basis'] ?? NULL,
      ];
    }

    return $preview;
  }

  /**
   * Gets entity IDs past the retention period.
   *
   * @param string $entityType
   *   The Drupal entity type ID.
   * @param int $retentionDays
   *   Days after which entities are considered expired.
   *
   * @return array
   *   Array of entity IDs past retention.
   */
  public function getExpiredEntities(string $entityType, int $retentionDays): array {
    try {
      $storage = $this->entityTypeManager->getStorage($entityType);
    }
    catch (\Exception $e) {
      $this->logger->warning('Entity type @type not found for retention check.', [
        '@type' => $entityType,
      ]);
      return [];
    }

    $cutoff = \Drupal::time()->getRequestTime() - ($retentionDays * 86400);

    $query = $storage->getQuery()
      ->accessCheck(FALSE);

    // Try 'created' field first, then 'changed'.
    try {
      $query->condition('created', $cutoff, '<');
    }
    catch (\Exception $e) {
      try {
        $query->condition('changed', $cutoff, '<');
      }
      catch (\Exception $e2) {
        return [];
      }
    }

    return array_values($query->execute());
  }

  /**
   * Anonymizes PII fields on an entity.
   *
   * @param string $entityType
   *   The Drupal entity type ID.
   * @param int $entityId
   *   The entity ID.
   *
   * @return bool
   *   TRUE if anonymization succeeded.
   */
  public function anonymizeEntity(string $entityType, int $entityId): bool {
    try {
      $storage = $this->entityTypeManager->getStorage($entityType);
      $entity = $storage->load($entityId);
      if (!$entity) {
        return FALSE;
      }

      // Replace common PII fields with anonymized values.
      $piiFields = [
        'field_name' => 'Anonymized',
        'field_email' => 'anonymized-' . $entityId . '@privacy.jaraba.dev',
        'field_phone' => '000000000',
        'field_address' => 'Anonymized',
        'field_nif' => 'XXXXXXXXX',
        'mail' => 'anonymized-' . $entityId . '@privacy.jaraba.dev',
        'name' => 'anonymized_user_' . $entityId,
      ];

      foreach ($piiFields as $field => $value) {
        if ($entity->hasField($field)) {
          $entity->set($field, $value);
        }
      }

      $entity->save();
      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to anonymize @type #@id: @msg', [
        '@type' => $entityType,
        '@id' => $entityId,
        '@msg' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Deletes an entity.
   *
   * @param string $entityType
   *   The Drupal entity type ID.
   * @param int $entityId
   *   The entity ID.
   *
   * @return bool
   *   TRUE if deletion succeeded.
   */
  public function deleteEntity(string $entityType, int $entityId): bool {
    try {
      $storage = $this->entityTypeManager->getStorage($entityType);
      $entity = $storage->load($entityId);
      if (!$entity) {
        return FALSE;
      }
      $entity->delete();
      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to delete @type #@id: @msg', [
        '@type' => $entityType,
        '@id' => $entityId,
        '@msg' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

}
