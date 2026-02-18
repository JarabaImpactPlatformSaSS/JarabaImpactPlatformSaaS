<?php

declare(strict_types=1);

namespace Drupal\jaraba_governance\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_governance\Entity\DataClassificationInterface;

/**
 * Service for managing data classification levels.
 *
 * Classifies entity types and individual fields into security tiers
 * (C1_PUBLIC through C4_RESTRICTED), driving downstream decisions
 * for encryption, masking, retention, and cross-border transfer.
 */
class DataClassifierService {

  /**
   * Constructs a DataClassifierService.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly TenantContextService $tenantContext,
    protected readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Gets classification for an entity type, optionally a specific field.
   *
   * @param string $entityType
   *   The Drupal entity type ID.
   * @param string|null $fieldName
   *   Optional field name. NULL returns classification for entire entity.
   *
   * @return \Drupal\jaraba_governance\Entity\DataClassificationInterface|null
   *   The classification entity, or NULL if not classified.
   */
  public function getClassification(string $entityType, ?string $fieldName = NULL): ?DataClassificationInterface {
    $storage = $this->entityTypeManager->getStorage('data_classification');
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('entity_type_id', $entityType);

    if ($fieldName !== NULL) {
      $query->condition('field_name', $fieldName);
    }
    else {
      $query->notExists('field_name');
    }

    $tenant = $this->tenantContext->getCurrentTenant();
    if ($tenant) {
      $query->condition('tenant_id', $tenant->id());
    }

    $query->range(0, 1);
    $ids = $query->execute();

    if (empty($ids)) {
      return NULL;
    }

    $entity = $storage->load(reset($ids));
    return $entity instanceof DataClassificationInterface ? $entity : NULL;
  }

  /**
   * Creates or updates a classification for an entity type / field.
   *
   * @param string $entityType
   *   The Drupal entity type ID.
   * @param string|null $fieldName
   *   Optional field name. NULL classifies the entire entity.
   * @param string $level
   *   Classification level (C1_PUBLIC, C2_INTERNAL, C3_CONFIDENTIAL, C4_RESTRICTED).
   * @param array $options
   *   Additional options: is_pii, is_sensitive, retention_days,
   *   encryption_required, masking_required, cross_border_allowed, legal_basis.
   *
   * @return \Drupal\jaraba_governance\Entity\DataClassificationInterface
   *   The saved classification entity.
   */
  public function setClassification(string $entityType, ?string $fieldName, string $level, array $options = []): DataClassificationInterface {
    $existing = $this->getClassification($entityType, $fieldName);

    if ($existing) {
      $entity = $existing;
    }
    else {
      $values = [
        'entity_type_id' => $entityType,
        'classification_level' => $level,
      ];
      if ($fieldName !== NULL) {
        $values['field_name'] = $fieldName;
      }
      $tenant = $this->tenantContext->getCurrentTenant();
      if ($tenant) {
        $values['tenant_id'] = $tenant->id();
      }
      $entity = $this->entityTypeManager->getStorage('data_classification')->create($values);
    }

    $entity->set('classification_level', $level);

    // Apply classification level defaults from config.
    $config = $this->configFactory->get('jaraba_governance.settings');
    $levelConfig = $config->get('classification_levels.' . $level) ?? [];

    $entity->set('encryption_required', $options['encryption_required'] ?? ($levelConfig['encryption_required'] ?? FALSE));
    $entity->set('masking_required', $options['masking_required'] ?? ($levelConfig['masking_required'] ?? FALSE));
    $entity->set('cross_border_allowed', $options['cross_border_allowed'] ?? !($levelConfig['cross_border_restricted'] ?? FALSE));
    $entity->set('is_pii', $options['is_pii'] ?? FALSE);
    $entity->set('is_sensitive', $options['is_sensitive'] ?? FALSE);

    if (isset($options['retention_days'])) {
      $entity->set('retention_days', $options['retention_days']);
    }
    if (isset($options['legal_basis'])) {
      $entity->set('legal_basis', $options['legal_basis']);
    }

    $entity->save();

    /** @var \Drupal\jaraba_governance\Entity\DataClassificationInterface $entity */
    return $entity;
  }

  /**
   * Gets all field-level classifications for an entity type.
   *
   * @param string $entityType
   *   The Drupal entity type ID.
   *
   * @return \Drupal\jaraba_governance\Entity\DataClassificationInterface[]
   *   Array of classification entities keyed by field_name.
   */
  public function getClassificationsForEntity(string $entityType): array {
    $storage = $this->entityTypeManager->getStorage('data_classification');
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('entity_type_id', $entityType);

    $tenant = $this->tenantContext->getCurrentTenant();
    if ($tenant) {
      $query->condition('tenant_id', $tenant->id());
    }

    $ids = $query->execute();
    if (empty($ids)) {
      return [];
    }

    $entities = $storage->loadMultiple($ids);
    $result = [];
    foreach ($entities as $entity) {
      /** @var \Drupal\jaraba_governance\Entity\DataClassificationInterface $entity */
      $key = $entity->getFieldName() ?? '__entity__';
      $result[$key] = $entity;
    }

    return $result;
  }

  /**
   * Gets all entity types that contain PII data.
   *
   * @return array
   *   Array of entity type IDs that have is_pii = TRUE.
   */
  public function getPiiEntities(): array {
    $storage = $this->entityTypeManager->getStorage('data_classification');
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('is_pii', TRUE);

    $tenant = $this->tenantContext->getCurrentTenant();
    if ($tenant) {
      $query->condition('tenant_id', $tenant->id());
    }

    $ids = $query->execute();
    if (empty($ids)) {
      return [];
    }

    $entities = $storage->loadMultiple($ids);
    $entityTypes = [];
    foreach ($entities as $entity) {
      /** @var \Drupal\jaraba_governance\Entity\DataClassificationInterface $entity */
      $entityTypes[$entity->getEntityTypeClassified()] = $entity->getEntityTypeClassified();
    }

    return array_values($entityTypes);
  }

  /**
   * Gets a summary of classification coverage for the dashboard.
   *
   * @return array
   *   Summary with total_classified, by_level counts, pii_count, sensitive_count.
   */
  public function getClassificationSummary(): array {
    $storage = $this->entityTypeManager->getStorage('data_classification');
    $query = $storage->getQuery()->accessCheck(FALSE);

    $tenant = $this->tenantContext->getCurrentTenant();
    if ($tenant) {
      $query->condition('tenant_id', $tenant->id());
    }

    $ids = $query->execute();
    $entities = !empty($ids) ? $storage->loadMultiple($ids) : [];

    $summary = [
      'total_classified' => count($entities),
      'by_level' => [
        'C1_PUBLIC' => 0,
        'C2_INTERNAL' => 0,
        'C3_CONFIDENTIAL' => 0,
        'C4_RESTRICTED' => 0,
      ],
      'pii_count' => 0,
      'sensitive_count' => 0,
      'entity_types' => [],
    ];

    foreach ($entities as $entity) {
      /** @var \Drupal\jaraba_governance\Entity\DataClassificationInterface $entity */
      $level = $entity->getClassificationLevel();
      if (isset($summary['by_level'][$level])) {
        $summary['by_level'][$level]++;
      }
      if ($entity->isPii()) {
        $summary['pii_count']++;
      }
      if ($entity->isSensitive()) {
        $summary['sensitive_count']++;
      }
      $summary['entity_types'][$entity->getEntityTypeClassified()] = TRUE;
    }

    $summary['entity_types'] = array_keys($summary['entity_types']);

    return $summary;
  }

}
