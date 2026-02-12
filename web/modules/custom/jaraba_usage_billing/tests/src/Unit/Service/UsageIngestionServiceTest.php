<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_usage_billing\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\jaraba_usage_billing\Service\UsageIngestionService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

// SAVED_NEW is defined in core/includes/common.inc, not loaded in unit tests.
if (!defined('SAVED_NEW')) {
  define('SAVED_NEW', 1);
}

/**
 * Tests para UsageIngestionService.
 *
 * @covers \Drupal\jaraba_usage_billing\Service\UsageIngestionService
 * @group jaraba_usage_billing
 */
class UsageIngestionServiceTest extends UnitTestCase {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected Connection $database;
  protected LoggerInterface $logger;
  protected UsageIngestionService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->database = $this->createMock(Connection::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new UsageIngestionService(
      $this->entityTypeManager,
      $this->database,
      $this->logger,
    );
  }

  /**
   * Tests ingestEvent returns NULL when required fields are missing.
   */
  public function testIngestEventReturnsNullOnMissingFields(): void {
    $this->logger->expects($this->once())
      ->method('warning');

    $result = $this->service->ingestEvent([
      'event_type' => 'api_call',
      // Missing metric_name, quantity, tenant_id.
    ]);

    $this->assertNull($result);
  }

  /**
   * Tests ingestEvent creates entity and returns ID.
   */
  public function testIngestEventCreatesEntity(): void {
    $idField = $this->createMock(FieldItemListInterface::class);
    $idField->method('__get')->with('value')->willReturn(42);

    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('id')->willReturn(42);
    $entity->method('save')->willReturn(SAVED_NEW);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('create')
      ->willReturn($entity);

    $this->entityTypeManager->method('getStorage')
      ->with('usage_event')
      ->willReturn($storage);

    $result = $this->service->ingestEvent([
      'event_type' => 'api_call',
      'metric_name' => 'api_requests',
      'quantity' => 1.0,
      'tenant_id' => 10,
      'unit' => 'requests',
    ]);

    $this->assertEquals(42, $result);
  }

  /**
   * Tests ingestEvent returns NULL on exception.
   */
  public function testIngestEventReturnsNullOnException(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('create')
      ->willThrowException(new \RuntimeException('Database error'));

    $this->entityTypeManager->method('getStorage')
      ->with('usage_event')
      ->willReturn($storage);

    $this->logger->expects($this->once())
      ->method('error');

    $result = $this->service->ingestEvent([
      'event_type' => 'api_call',
      'metric_name' => 'api_requests',
      'quantity' => 1.0,
      'tenant_id' => 10,
    ]);

    $this->assertNull($result);
  }

  /**
   * Tests batchIngest processes multiple events.
   */
  public function testBatchIngestCountsSuccesses(): void {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('id')->willReturn(1);
    $entity->method('save')->willReturn(SAVED_NEW);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('create')->willReturn($entity);

    $this->entityTypeManager->method('getStorage')
      ->with('usage_event')
      ->willReturn($storage);

    // Use an anonymous class to avoid Transaction's readonly property
    // issue when __destruct runs on mock objects.
    $transaction = new class () {
      public function commitOrRelease(): void {}
      public function rollBack(): void {}
    };
    $this->database->method('startTransaction')
      ->willReturn($transaction);

    $events = [
      [
        'event_type' => 'api_call',
        'metric_name' => 'api_requests',
        'quantity' => 1.0,
        'tenant_id' => 10,
      ],
      [
        'event_type' => 'storage',
        'metric_name' => 'disk_usage_gb',
        'quantity' => 0.5,
        'tenant_id' => 10,
      ],
    ];

    $result = $this->service->batchIngest($events);

    $this->assertEquals(2, $result);
  }

  /**
   * Tests ingestEvent with metadata as array encodes to JSON.
   */
  public function testIngestEventEncodesMetadata(): void {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('id')->willReturn(99);
    $entity->method('save')->willReturn(SAVED_NEW);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('create')
      ->with($this->callback(function (array $values) {
        return isset($values['metadata'])
          && json_decode($values['metadata'], TRUE) === ['source' => 'test'];
      }))
      ->willReturn($entity);

    $this->entityTypeManager->method('getStorage')
      ->with('usage_event')
      ->willReturn($storage);

    $result = $this->service->ingestEvent([
      'event_type' => 'api_call',
      'metric_name' => 'api_requests',
      'quantity' => 1.0,
      'tenant_id' => 10,
      'metadata' => ['source' => 'test'],
    ]);

    $this->assertEquals(99, $result);
  }

}
