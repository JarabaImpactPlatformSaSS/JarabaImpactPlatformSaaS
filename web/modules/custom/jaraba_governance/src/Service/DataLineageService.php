<?php

declare(strict_types=1);

namespace Drupal\jaraba_governance\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_governance\Entity\DataLineageEventInterface;

/**
 * Service for recording and querying data lineage events.
 *
 * Maintains an append-only audit trail of all data lifecycle events.
 * Every creation, read, update, export, deletion, anonymization, or
 * transfer is recorded with actor, timestamp, and contextual metadata.
 */
class DataLineageService {

  /**
   * Constructs a DataLineageService.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly TenantContextService $tenantContext,
    protected readonly AccountProxyInterface $currentUser,
    protected readonly Connection $database,
  ) {}

  /**
   * Records a new data lineage event.
   *
   * @param string $entityType
   *   The target entity type.
   * @param int $entityId
   *   The target entity ID.
   * @param string $eventType
   *   One of: created, updated, read, exported, deleted, anonymized, transferred.
   * @param array $metadata
   *   Optional metadata array (will be JSON-encoded).
   *
   * @return \Drupal\jaraba_governance\Entity\DataLineageEventInterface
   *   The created lineage event entity.
   */
  public function recordEvent(string $entityType, int $entityId, string $eventType, array $metadata = []): DataLineageEventInterface {
    $storage = $this->entityTypeManager->getStorage('data_lineage_event');

    $values = [
      'target_entity_type' => $entityType,
      'target_entity_id' => $entityId,
      'event_type' => $eventType,
      'actor_type' => 'user',
    ];

    $userId = (int) $this->currentUser->id();
    if ($userId > 0) {
      $values['actor_id'] = $userId;
    }
    else {
      $values['actor_type'] = 'system';
    }

    if (!empty($metadata)) {
      if (isset($metadata['actor_type'])) {
        $values['actor_type'] = $metadata['actor_type'];
        unset($metadata['actor_type']);
      }
      if (isset($metadata['source_system'])) {
        $values['source_system'] = $metadata['source_system'];
        unset($metadata['source_system']);
      }
      if (isset($metadata['destination_system'])) {
        $values['destination_system'] = $metadata['destination_system'];
        unset($metadata['destination_system']);
      }
      $values['metadata'] = json_encode($metadata, JSON_UNESCAPED_UNICODE);
    }

    $tenant = $this->tenantContext->getCurrentTenant();
    if ($tenant) {
      $values['tenant_id'] = $tenant->id();
    }

    /** @var \Drupal\jaraba_governance\Entity\DataLineageEventInterface $event */
    $event = $storage->create($values);
    $event->save();

    return $event;
  }

  /**
   * Gets the full lineage trail for an entity.
   *
   * @param string $entityType
   *   The target entity type.
   * @param int $entityId
   *   The target entity ID.
   *
   * @return array
   *   Array of lineage event data, ordered by created DESC.
   */
  public function getLineage(string $entityType, int $entityId): array {
    $storage = $this->entityTypeManager->getStorage('data_lineage_event');
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('target_entity_type', $entityType)
      ->condition('target_entity_id', $entityId)
      ->sort('created', 'DESC');

    $tenant = $this->tenantContext->getCurrentTenant();
    if ($tenant) {
      $query->condition('tenant_id', $tenant->id());
    }

    $ids = $query->execute();
    if (empty($ids)) {
      return [];
    }

    $events = $storage->loadMultiple($ids);
    $result = [];

    foreach ($events as $event) {
      /** @var \Drupal\jaraba_governance\Entity\DataLineageEventInterface $event */
      $result[] = [
        'id' => (int) $event->id(),
        'event_type' => $event->getEventType(),
        'actor_id' => $event->getActorId(),
        'actor_type' => $event->getActorType(),
        'source_system' => $event->getSourceSystem(),
        'destination_system' => $event->getDestinationSystem(),
        'metadata' => $event->getMetadataArray(),
        'created' => $event->getCreatedTime(),
      ];
    }

    return $result;
  }

  /**
   * Gets recent lineage activity across the tenant.
   *
   * @param int $limit
   *   Maximum number of events to return.
   *
   * @return array
   *   Array of recent lineage event data.
   */
  public function getRecentActivity(int $limit = 50): array {
    $storage = $this->entityTypeManager->getStorage('data_lineage_event');
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->sort('created', 'DESC')
      ->range(0, $limit);

    $tenant = $this->tenantContext->getCurrentTenant();
    if ($tenant) {
      $query->condition('tenant_id', $tenant->id());
    }

    $ids = $query->execute();
    if (empty($ids)) {
      return [];
    }

    $events = $storage->loadMultiple($ids);
    $result = [];

    foreach ($events as $event) {
      /** @var \Drupal\jaraba_governance\Entity\DataLineageEventInterface $event */
      $result[] = [
        'id' => (int) $event->id(),
        'target_entity_type' => $event->getTargetEntityType(),
        'target_entity_id' => $event->getTargetEntityId(),
        'event_type' => $event->getEventType(),
        'actor_id' => $event->getActorId(),
        'actor_type' => $event->getActorType(),
        'created' => $event->getCreatedTime(),
      ];
    }

    return $result;
  }

}
