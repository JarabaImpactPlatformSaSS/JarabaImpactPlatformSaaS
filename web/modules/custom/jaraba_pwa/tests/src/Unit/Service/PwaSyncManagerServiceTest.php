<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_pwa\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_pwa\Entity\PendingSyncAction;
use Drupal\jaraba_pwa\Service\PwaSyncManagerService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for PwaSyncManagerService.
 *
 * @coversDefaultClass \Drupal\jaraba_pwa\Service\PwaSyncManagerService
 * @group jaraba_pwa
 */
class PwaSyncManagerServiceTest extends TestCase {

  /**
   * The service under test.
   */
  protected PwaSyncManagerService $service;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock logger.
   */
  protected LoggerInterface $logger;

  /**
   * Mock sync action storage.
   */
  protected EntityStorageInterface $syncStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->syncStorage = $this->createMock(EntityStorageInterface::class);

    $this->service = new PwaSyncManagerService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * Tests queueAction() creates a new pending sync action.
   *
   * @covers ::queueAction
   */
  public function testQueueActionCreatesEntity(): void {
    $this->entityTypeManager
      ->method('getStorage')
      ->with('pending_sync_action')
      ->willReturn($this->syncStorage);

    $entity = $this->createMock(PendingSyncAction::class);
    $entity->method('id')->willReturn(42);
    $entity->expects($this->once())->method('save');

    $this->syncStorage
      ->expects($this->once())
      ->method('create')
      ->with($this->callback(function ($values) {
        return $values['action_type'] === 'create'
          && $values['target_entity_type'] === 'node'
          && $values['target_entity_id'] === 0
          && $values['sync_status'] === 'pending';
      }))
      ->willReturn($entity);

    $result = $this->service->queueAction('create', 'node', 0, [
      'title' => 'Test Node',
      'user_id' => 1,
    ]);

    $this->assertSame(42, $result);
  }

  /**
   * Tests queueAction() returns NULL on exception.
   *
   * @covers ::queueAction
   */
  public function testQueueActionReturnsNullOnError(): void {
    $this->entityTypeManager
      ->method('getStorage')
      ->willThrowException(new \RuntimeException('Storage error'));

    $this->logger
      ->expects($this->once())
      ->method('error');

    $result = $this->service->queueAction('create', 'node', 0, []);

    $this->assertNull($result);
  }

  /**
   * Tests processPendingActions() returns 0 when no pending actions.
   *
   * @covers ::processPendingActions
   */
  public function testProcessPendingActionsReturnsZeroWhenEmpty(): void {
    $this->entityTypeManager
      ->method('getStorage')
      ->with('pending_sync_action')
      ->willReturn($this->syncStorage);

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->syncStorage
      ->method('getQuery')
      ->willReturn($query);

    $result = $this->service->processPendingActions();

    $this->assertSame(0, $result);
  }

  /**
   * Tests processPendingActions() with user filter.
   *
   * @covers ::processPendingActions
   */
  public function testProcessPendingActionsWithUserFilter(): void {
    $this->entityTypeManager
      ->method('getStorage')
      ->with('pending_sync_action')
      ->willReturn($this->syncStorage);

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->expects($this->atLeastOnce())->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->syncStorage
      ->method('getQuery')
      ->willReturn($query);

    $result = $this->service->processPendingActions(42);

    $this->assertSame(0, $result);
  }

  /**
   * Tests retryFailed() returns 0 when no failed actions.
   *
   * @covers ::retryFailed
   */
  public function testRetryFailedReturnsZeroWhenNoFailedActions(): void {
    $this->entityTypeManager
      ->method('getStorage')
      ->with('pending_sync_action')
      ->willReturn($this->syncStorage);

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->syncStorage
      ->method('getQuery')
      ->willReturn($query);

    $result = $this->service->retryFailed();

    $this->assertSame(0, $result);
  }

  /**
   * Tests retryFailed() resets eligible actions to pending.
   *
   * @covers ::retryFailed
   */
  public function testRetryFailedResetsEligibleActions(): void {
    $this->entityTypeManager
      ->method('getStorage')
      ->with('pending_sync_action')
      ->willReturn($this->syncStorage);

    // First query: find failed actions.
    $failedQuery = $this->createMock(QueryInterface::class);
    $failedQuery->method('accessCheck')->willReturnSelf();
    $failedQuery->method('condition')->willReturnSelf();
    $failedQuery->method('execute')->willReturn([10, 11]);

    // Second query (from processPendingActions): find pending actions.
    $pendingQuery = $this->createMock(QueryInterface::class);
    $pendingQuery->method('accessCheck')->willReturnSelf();
    $pendingQuery->method('condition')->willReturnSelf();
    $pendingQuery->method('sort')->willReturnSelf();
    $pendingQuery->method('execute')->willReturn([]);

    $queryCallCount = 0;
    $this->syncStorage
      ->method('getQuery')
      ->willReturnCallback(function () use ($failedQuery, $pendingQuery, &$queryCallCount) {
        $queryCallCount++;
        return $queryCallCount === 1 ? $failedQuery : $pendingQuery;
      });

    // Create mock actions that can be retried.
    $action1 = $this->createMock(PendingSyncAction::class);
    $action1->method('canRetry')->willReturn(TRUE);
    $action1->expects($this->once())->method('set')->with('sync_status', 'pending');
    $action1->expects($this->once())->method('save');

    $action2 = $this->createMock(PendingSyncAction::class);
    $action2->method('canRetry')->willReturn(FALSE);
    $action2->expects($this->never())->method('set');

    $this->syncStorage
      ->method('loadMultiple')
      ->with([10, 11])
      ->willReturn([$action1, $action2]);

    $result = $this->service->retryFailed();

    // processPendingActions returns 0 because no pending actions found.
    $this->assertSame(0, $result);
  }

  /**
   * Tests processPendingActions() handles exceptions gracefully.
   *
   * @covers ::processPendingActions
   */
  public function testProcessPendingActionsHandlesException(): void {
    $this->entityTypeManager
      ->method('getStorage')
      ->willThrowException(new \RuntimeException('Database error'));

    $this->logger
      ->expects($this->once())
      ->method('error');

    $result = $this->service->processPendingActions();

    $this->assertSame(0, $result);
  }

  /**
   * Tests queueAction() extracts user_id and tenant_id from payload.
   *
   * @covers ::queueAction
   */
  public function testQueueActionExtractsUserAndTenantFromPayload(): void {
    $this->entityTypeManager
      ->method('getStorage')
      ->with('pending_sync_action')
      ->willReturn($this->syncStorage);

    $entity = $this->createMock(PendingSyncAction::class);
    $entity->method('id')->willReturn(55);
    $entity->expects($this->once())->method('save');

    $this->syncStorage
      ->expects($this->once())
      ->method('create')
      ->with($this->callback(function ($values) {
        return $values['user_id'] === 42
          && $values['tenant_id'] === 10
          && $values['action_type'] === 'update';
      }))
      ->willReturn($entity);

    $result = $this->service->queueAction('update', 'node', 5, [
      'title' => 'Updated Node',
      'user_id' => 42,
      'tenant_id' => 10,
    ]);

    $this->assertSame(55, $result);
  }

}
