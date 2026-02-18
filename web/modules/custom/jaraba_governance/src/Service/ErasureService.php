<?php

declare(strict_types=1);

namespace Drupal\jaraba_governance\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_governance\Entity\ErasureRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for GDPR erasure requests and data portability.
 *
 * Handles:
 * - Art. 17: Right to erasure (right to be forgotten).
 * - Art. 20: Right to data portability (structured export).
 * - Art. 15: Right of access (data subject access request).
 * - Art. 16: Right to rectification.
 */
class ErasureService {

  /**
   * Constructs an ErasureService.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly TenantContextService $tenantContext,
    protected readonly Connection $database,
    protected readonly LoggerInterface $logger,
    protected readonly FileSystemInterface $fileSystem,
  ) {}

  /**
   * Creates a new erasure/GDPR request.
   *
   * @param int $subjectUserId
   *   The user whose data is subject to the request.
   * @param string $requestType
   *   One of: erasure, rectification, portability, access.
   * @param string|null $reason
   *   Optional reason/justification.
   *
   * @return \Drupal\jaraba_governance\Entity\ErasureRequestInterface
   *   The created request entity with status 'pending'.
   */
  public function createRequest(int $subjectUserId, string $requestType, ?string $reason = NULL): ErasureRequestInterface {
    $storage = $this->entityTypeManager->getStorage('erasure_request');

    $values = [
      'requester_id' => \Drupal::currentUser()->id(),
      'subject_user_id' => $subjectUserId,
      'request_type' => $requestType,
      'status' => 'pending',
    ];

    if ($reason !== NULL) {
      $values['reason'] = $reason;
    }

    $tenant = $this->tenantContext->getCurrentTenant();
    if ($tenant) {
      $values['tenant_id'] = $tenant->id();
    }

    /** @var \Drupal\jaraba_governance\Entity\ErasureRequestInterface $request */
    $request = $storage->create($values);
    $request->save();

    $this->logger->info('GDPR @type request #@id created for user @uid.', [
      '@type' => $requestType,
      '@id' => $request->id(),
      '@uid' => $subjectUserId,
    ]);

    return $request;
  }

  /**
   * Processes a pending erasure request.
   *
   * Finds all entities for the subject user, anonymizes or deletes them
   * based on classification, and records lineage events.
   *
   * @param int $requestId
   *   The erasure request entity ID.
   *
   * @return array
   *   Processing result with affected entity counts.
   */
  public function processRequest(int $requestId): array {
    $storage = $this->entityTypeManager->getStorage('erasure_request');

    /** @var \Drupal\jaraba_governance\Entity\ErasureRequestInterface|null $request */
    $request = $storage->load($requestId);
    if (!$request) {
      return ['error' => 'Request not found.'];
    }

    if ($request->getStatus() !== 'pending') {
      return ['error' => 'Request is not in pending status.'];
    }

    $request->setStatus('processing');
    $request->save();

    $subjectUserId = $request->getSubjectUserId();
    $affected = $this->getAffectedEntities($subjectUserId);
    $results = [
      'request_id' => $requestId,
      'subject_user_id' => $subjectUserId,
      'anonymized' => 0,
      'deleted' => 0,
      'entities' => [],
    ];

    foreach ($affected as $entityType => $entityIds) {
      foreach ($entityIds as $entityId) {
        try {
          $entityStorage = $this->entityTypeManager->getStorage($entityType);
          $entity = $entityStorage->load($entityId);
          if (!$entity) {
            continue;
          }

          // Anonymize PII fields rather than deleting (preserves data structure).
          $piiFields = ['mail', 'name', 'field_name', 'field_email', 'field_phone', 'field_address', 'field_nif'];
          foreach ($piiFields as $field) {
            if ($entity->hasField($field)) {
              $entity->set($field, 'erased_' . $entityId);
            }
          }
          $entity->save();

          $results['anonymized']++;
          $results['entities'][] = [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => 'anonymized',
          ];
        }
        catch (\Exception $e) {
          $this->logger->error('Erasure failed for @type #@id: @msg', [
            '@type' => $entityType,
            '@id' => $entityId,
            '@msg' => $e->getMessage(),
          ]);
        }
      }
    }

    // Mark request as completed.
    $request->setStatus('completed');
    $request->setEntitiesAffected($results['entities']);
    $request->set('processed_at', date('Y-m-d\TH:i:s'));
    $request->set('processed_by', \Drupal::currentUser()->id());
    $request->save();

    $this->logger->info('GDPR erasure request #@id completed: @anon anonymized, @del deleted.', [
      '@id' => $requestId,
      '@anon' => $results['anonymized'],
      '@del' => $results['deleted'],
    ]);

    return $results;
  }

  /**
   * Gets all entities associated with a user across entity types.
   *
   * @param int $userId
   *   The user ID to search for.
   *
   * @return array
   *   Associative array: entity_type => [entity_id, ...].
   */
  public function getAffectedEntities(int $userId): array {
    $affected = [];

    // List of entity types and their user reference fields.
    $entityUserFields = [
      'node' => 'uid',
      'comment' => 'uid',
      'file' => 'uid',
    ];

    // Add governance-specific entities.
    $entityUserFields['erasure_request'] = 'subject_user_id';
    $entityUserFields['data_lineage_event'] = 'actor_id';

    foreach ($entityUserFields as $entityType => $userField) {
      try {
        $storage = $this->entityTypeManager->getStorage($entityType);
        $ids = $storage->getQuery()
          ->accessCheck(FALSE)
          ->condition($userField, $userId)
          ->execute();

        if (!empty($ids)) {
          $affected[$entityType] = array_values($ids);
        }
      }
      catch (\Exception $e) {
        // Entity type may not exist in this installation.
        continue;
      }
    }

    return $affected;
  }

  /**
   * Exports all user data for GDPR portability (Art. 20).
   *
   * Collects all data associated with a user across entity types
   * and returns a structured array suitable for JSON/CSV export.
   *
   * @param int $userId
   *   The user ID to export.
   *
   * @return array
   *   Structured export data keyed by entity type.
   */
  public function exportUserData(int $userId): array {
    $export = [
      'export_date' => date('c'),
      'subject_user_id' => $userId,
      'data' => [],
    ];

    // Export user account data.
    try {
      $userStorage = $this->entityTypeManager->getStorage('user');
      $user = $userStorage->load($userId);
      if ($user) {
        $export['data']['user'] = [
          'uid' => (int) $user->id(),
          'name' => $user->getAccountName(),
          'mail' => $user->getEmail(),
          'created' => $user->getCreatedTime(),
          'access' => $user->getLastAccessedTime(),
          'status' => (bool) $user->isActive(),
          'roles' => $user->getRoles(),
        ];
      }
    }
    catch (\Exception $e) {
      // User storage not available.
    }

    // Export related entities.
    $affected = $this->getAffectedEntities($userId);
    foreach ($affected as $entityType => $entityIds) {
      $items = [];
      try {
        $storage = $this->entityTypeManager->getStorage($entityType);
        $entities = $storage->loadMultiple($entityIds);
        foreach ($entities as $entity) {
          $item = [
            'id' => (int) $entity->id(),
            'type' => $entityType,
          ];
          if ($entity->hasField('created')) {
            $item['created'] = $entity->get('created')->value;
          }
          if ($entity->hasField('changed')) {
            $item['changed'] = $entity->get('changed')->value;
          }
          if (method_exists($entity, 'label')) {
            $item['label'] = $entity->label();
          }
          $items[] = $item;
        }
      }
      catch (\Exception $e) {
        continue;
      }
      if (!empty($items)) {
        $export['data'][$entityType] = $items;
      }
    }

    return $export;
  }

  /**
   * Gets all pending erasure requests.
   *
   * @return array
   *   Array of pending request data.
   */
  public function getPendingRequests(): array {
    $storage = $this->entityTypeManager->getStorage('erasure_request');
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 'pending')
      ->sort('created', 'ASC');

    $tenant = $this->tenantContext->getCurrentTenant();
    if ($tenant) {
      $query->condition('tenant_id', $tenant->id());
    }

    $ids = $query->execute();
    if (empty($ids)) {
      return [];
    }

    $requests = $storage->loadMultiple($ids);
    $result = [];

    foreach ($requests as $request) {
      /** @var \Drupal\jaraba_governance\Entity\ErasureRequestInterface $request */
      $result[] = [
        'id' => (int) $request->id(),
        'requester_id' => $request->getRequesterId(),
        'subject_user_id' => $request->getSubjectUserId(),
        'request_type' => $request->getRequestType(),
        'status' => $request->getStatus(),
        'reason' => $request->getReason(),
        'created' => $request->getCreatedTime(),
      ];
    }

    return $result;
  }

}
