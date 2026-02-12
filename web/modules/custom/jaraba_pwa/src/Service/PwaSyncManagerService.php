<?php

declare(strict_types=1);

namespace Drupal\jaraba_pwa\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Background sync manager service.
 *
 * Manages the queue of offline actions that need to be synced
 * to the server once connectivity is restored. Works with the
 * Background Sync API on the client side.
 *
 * Responsibilities:
 * - Queue new sync actions from the service worker.
 * - Process pending actions in order.
 * - Retry failed actions with exponential backoff.
 * - Clean up completed/permanently failed actions.
 */
class PwaSyncManagerService {

  /**
   * Constructor.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {
  }

  /**
   * Queues a new sync action for later processing.
   *
   * @param string $actionType
   *   The action type: 'create', 'update', or 'delete'.
   * @param string $entityType
   *   The target entity type machine name.
   * @param int $entityId
   *   The target entity ID (0 for create actions).
   * @param array $payload
   *   The action payload data.
   *
   * @return int|null
   *   The created PendingSyncAction entity ID, or NULL on failure.
   */
  public function queueAction(string $actionType, string $entityType, int $entityId, array $payload): ?int {
    try {
      $storage = $this->entityTypeManager->getStorage('pending_sync_action');

      $values = [
        'action_type' => $actionType,
        'target_entity_type' => $entityType,
        'target_entity_id' => $entityId,
        'payload' => json_encode($payload),
        'sync_status' => 'pending',
        'retry_count' => 0,
        'max_retries' => 3,
      ];

      // Extract user_id and tenant_id from payload if present.
      if (!empty($payload['user_id'])) {
        $values['user_id'] = $payload['user_id'];
      }
      if (!empty($payload['tenant_id'])) {
        $values['tenant_id'] = $payload['tenant_id'];
      }

      $entity = $storage->create($values);
      $entity->save();

      $this->logger->info('Sync action queued: @type @entity_type #@entity_id.', [
        '@type' => $actionType,
        '@entity_type' => $entityType,
        '@entity_id' => $entityId,
      ]);

      return (int) $entity->id();
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to queue sync action: @error', [
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Processes all pending sync actions, optionally filtered by user.
   *
   * @param int|null $userId
   *   If provided, only process actions for this user.
   *
   * @return int
   *   Number of actions successfully processed.
   */
  public function processPendingActions(?int $userId = NULL): int {
    try {
      $storage = $this->entityTypeManager->getStorage('pending_sync_action');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('sync_status', 'pending')
        ->sort('created', 'ASC');

      if ($userId !== NULL) {
        $query->condition('user_id', $userId);
      }

      $ids = $query->execute();

      if (empty($ids)) {
        return 0;
      }

      $actions = $storage->loadMultiple($ids);
      $processed = 0;

      foreach ($actions as $action) {
        $action->markSyncing();
        $action->save();

        try {
          $success = $this->executeAction($action);

          if ($success) {
            $action->markSynced();
            $action->save();
            $processed++;
          }
          else {
            $action->incrementRetry();
            if ($action->canRetry()) {
              $action->set('sync_status', 'pending');
            }
            else {
              $action->markFailed();
            }
            $action->save();
          }
        }
        catch (\Exception $e) {
          $action->incrementRetry();
          if ($action->canRetry()) {
            $action->set('sync_status', 'pending');
          }
          else {
            $action->markFailed();
          }
          $action->save();

          $this->logger->error('Sync action @id failed: @error', [
            '@id' => $action->id(),
            '@error' => $e->getMessage(),
          ]);
        }
      }

      $this->logger->info('Processed @processed/@total pending sync actions.', [
        '@processed' => $processed,
        '@total' => count($actions),
      ]);

      return $processed;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to process pending sync actions: @error', [
        '@error' => $e->getMessage(),
      ]);
      return 0;
    }
  }

  /**
   * Retries all failed sync actions that still have retries remaining.
   *
   * @return int
   *   Number of actions successfully retried.
   */
  public function retryFailed(): int {
    try {
      $storage = $this->entityTypeManager->getStorage('pending_sync_action');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('sync_status', 'failed');

      $ids = $query->execute();

      if (empty($ids)) {
        return 0;
      }

      $actions = $storage->loadMultiple($ids);
      $retried = 0;

      foreach ($actions as $action) {
        // Reset to pending for re-processing if retries remain.
        if ($action->canRetry()) {
          $action->set('sync_status', 'pending');
          $action->save();
          $retried++;
        }
      }

      if ($retried > 0) {
        // Process the re-queued actions.
        $processed = $this->processPendingActions();
        $this->logger->info('Retried @retried failed actions, @processed processed.', [
          '@retried' => $retried,
          '@processed' => $processed,
        ]);
        return $processed;
      }

      return 0;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to retry sync actions: @error', [
        '@error' => $e->getMessage(),
      ]);
      return 0;
    }
  }

  /**
   * Executes a single sync action against the target entity.
   *
   * @param \Drupal\jaraba_pwa\Entity\PendingSyncAction $action
   *   The sync action to execute.
   *
   * @return bool
   *   TRUE if the action was executed successfully.
   */
  protected function executeAction($action): bool {
    $actionType = $action->getActionType();
    $entityType = $action->getTargetEntityType();
    $entityId = $action->getTargetEntityId();
    $payload = $action->getPayload();

    try {
      $storage = $this->entityTypeManager->getStorage($entityType);
    }
    catch (\Exception $e) {
      $this->logger->error('Unknown entity type @type in sync action @id.', [
        '@type' => $entityType,
        '@id' => $action->id(),
      ]);
      return FALSE;
    }

    switch ($actionType) {
      case 'create':
        $entity = $storage->create($payload);
        $entity->save();
        return TRUE;

      case 'update':
        if ($entityId <= 0) {
          return FALSE;
        }
        $entity = $storage->load($entityId);
        if (!$entity) {
          $this->logger->warning('Entity @type #@id not found for update.', [
            '@type' => $entityType,
            '@id' => $entityId,
          ]);
          return FALSE;
        }
        foreach ($payload as $field => $value) {
          if ($entity->hasField($field)) {
            $entity->set($field, $value);
          }
        }
        $entity->save();
        return TRUE;

      case 'delete':
        if ($entityId <= 0) {
          return FALSE;
        }
        $entity = $storage->load($entityId);
        if (!$entity) {
          // Entity already deleted, consider success.
          return TRUE;
        }
        $entity->delete();
        return TRUE;

      default:
        $this->logger->warning('Unknown action type @type in sync action @id.', [
          '@type' => $actionType,
          '@id' => $action->id(),
        ]);
        return FALSE;
    }
  }

}
