<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal_billing\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_legal_billing\Service\TimeTrackingService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for TimeTrackingService.
 *
 * @coversDefaultClass \Drupal\jaraba_legal_billing\Service\TimeTrackingService
 * @group jaraba_legal_billing
 */
class TimeTrackingServiceTest extends UnitTestCase {

  /**
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The mocked current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The mocked logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected LoggerInterface $logger;

  /**
   * The service under test.
   *
   * @var \Drupal\jaraba_legal_billing\Service\TimeTrackingService
   */
  protected TimeTrackingService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->currentUser = $this->createMock(AccountProxyInterface::class);
    $this->currentUser->method('id')->willReturn(42);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new TimeTrackingService(
      $this->entityTypeManager,
      $this->currentUser,
      $this->logger,
    );
  }

  /**
   * Helper to create a mock field item with a value property.
   */
  protected function createFieldItem(mixed $value): object {
    $field = new \stdClass();
    $field->value = $value;
    return $field;
  }

  /**
   * Helper to create a mock field item with a target_id property.
   */
  protected function createFieldItemRef(?int $targetId): object {
    $field = new \stdClass();
    $field->target_id = $targetId;
    return $field;
  }

  /**
   * Tests logTime creates an entry and returns id, uuid and duration.
   *
   * @covers ::logTime
   */
  public function testStartTimer(): void {
    $entry = $this->createMock(\stdClass::class);
    $entry->method('id')->willReturn(101);
    $entry->method('uuid')->willReturn('entry-uuid-abc');
    $durationField = $this->createFieldItem(90);
    $entry->method('get')
      ->with('duration_minutes')
      ->willReturn($durationField);
    $entry->expects($this->once())->method('save');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('create')->willReturn($entry);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('time_entry')
      ->willReturn($storage);

    $data = [
      'case_id' => 10,
      'description' => 'Research case law',
      'date' => '2025-03-01',
      'duration_minutes' => 90,
      'billing_rate' => 150.00,
      'is_billable' => TRUE,
    ];

    $result = $this->service->logTime($data);

    $this->assertSame(101, $result['id']);
    $this->assertSame('entry-uuid-abc', $result['uuid']);
    $this->assertSame(90, $result['duration_minutes']);
  }

  /**
   * Tests logTime returns empty array on exception (stop/error scenario).
   *
   * @covers ::logTime
   */
  public function testStopTimer(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('create')
      ->willThrowException(new \RuntimeException('Storage error'));

    $this->entityTypeManager
      ->method('getStorage')
      ->with('time_entry')
      ->willReturn($storage);

    $this->logger->expects($this->once())
      ->method('error');

    $result = $this->service->logTime([
      'case_id' => 10,
      'description' => 'Test',
      'date' => '2025-03-01',
      'duration_minutes' => 30,
    ]);

    $this->assertSame([], $result);
  }

  /**
   * Tests getTimeByCase returns serialized time entries for a given case.
   *
   * @covers ::getTimeByCase
   * @covers ::serializeTimeEntry
   */
  public function testGetEntriesForCase(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('condition')->willReturnSelf();
    $query->method('accessCheck')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([1, 2]);

    $entry1 = $this->createTimeEntryMock(1, 'uuid-1', 10, 42, 'Research', '2025-03-01', 60, 150.00, TRUE, NULL, '1709312400');
    $entry2 = $this->createTimeEntryMock(2, 'uuid-2', 10, 42, 'Drafting', '2025-03-02', 120, 150.00, TRUE, NULL, '1709398800');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);
    $storage->method('loadMultiple')
      ->with([1, 2])
      ->willReturn([$entry1, $entry2]);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('time_entry')
      ->willReturn($storage);

    $result = $this->service->getTimeByCase(10);

    $this->assertCount(2, $result);
    $this->assertSame(1, $result[0]['id']);
    $this->assertSame('Research', $result[0]['description']);
    $this->assertSame(60, $result[0]['duration_minutes']);
    $this->assertSame(2, $result[1]['id']);
    $this->assertSame('Drafting', $result[1]['description']);
    $this->assertSame(120, $result[1]['duration_minutes']);
  }

  /**
   * Tests getTimeByCase returns empty array on exception.
   *
   * @covers ::getTimeByCase
   */
  public function testGetEntriesForCaseReturnsEmptyOnException(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')
      ->willThrowException(new \RuntimeException('Query error'));

    $this->entityTypeManager
      ->method('getStorage')
      ->with('time_entry')
      ->willReturn($storage);

    $this->logger->expects($this->once())
      ->method('error');

    $result = $this->service->getTimeByCase(999);
    $this->assertSame([], $result);
  }

  /**
   * Creates a mock time entry entity with the given field values.
   */
  protected function createTimeEntryMock(
    int $id,
    string $uuid,
    int $caseId,
    int $userId,
    string $description,
    string $date,
    int $durationMinutes,
    float $billingRate,
    bool $isBillable,
    ?int $invoiceId,
    string $created,
  ): object {
    $entry = $this->createMock(\stdClass::class);
    $entry->method('id')->willReturn($id);
    $entry->method('uuid')->willReturn($uuid);

    $fieldMap = [
      'case_id' => $this->createFieldItemRef($caseId),
      'user_id' => $this->createFieldItemRef($userId),
      'description' => $this->createFieldItem($description),
      'date' => $this->createFieldItem($date),
      'duration_minutes' => $this->createFieldItem($durationMinutes),
      'billing_rate' => $this->createFieldItem($billingRate),
      'is_billable' => $this->createFieldItem($isBillable),
      'invoice_id' => $this->createFieldItemRef($invoiceId),
      'created' => $this->createFieldItem($created),
    ];

    $entry->method('get')->willReturnCallback(function (string $field) use ($fieldMap) {
      return $fieldMap[$field] ?? $this->createFieldItem(NULL);
    });

    return $entry;
  }

}
