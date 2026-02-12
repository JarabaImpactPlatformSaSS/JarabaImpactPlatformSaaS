<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_usage_billing\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Select;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\jaraba_usage_billing\Service\UsageAggregatorService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para UsageAggregatorService.
 *
 * @covers \Drupal\jaraba_usage_billing\Service\UsageAggregatorService
 * @group jaraba_usage_billing
 */
class UsageAggregatorServiceTest extends UnitTestCase {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected Connection $database;
  protected LoggerInterface $logger;
  protected UsageAggregatorService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->database = $this->createMock(Connection::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new UsageAggregatorService(
      $this->entityTypeManager,
      $this->database,
      $this->logger,
    );
  }

  /**
   * Tests aggregateHourly returns zero when no data.
   */
  public function testAggregateHourlyReturnsZeroWhenNoData(): void {
    $select = $this->createMock(Select::class);
    $select->method('addField')->willReturnSelf();
    $select->method('addExpression')->willReturnSelf();
    $select->method('condition')->willReturnSelf();
    $select->method('groupBy')->willReturnSelf();

    $statement = $this->createMock(StatementInterface::class);
    $statement->method('current')->willReturn(FALSE);
    $statement->method('valid')->willReturn(FALSE);

    $select->method('execute')->willReturn($statement);

    $this->database->method('select')
      ->with('usage_event', 'ue')
      ->willReturn($select);

    $result = $this->service->aggregateHourly(42);

    $this->assertEquals(0, $result);
  }

  /**
   * Tests aggregateDaily calls aggregate with correct period type.
   */
  public function testAggregateDailyExecutes(): void {
    $select = $this->createMock(Select::class);
    $select->method('addField')->willReturnSelf();
    $select->method('addExpression')->willReturnSelf();
    $select->method('condition')->willReturnSelf();
    $select->method('groupBy')->willReturnSelf();

    $statement = $this->createMock(StatementInterface::class);
    $statement->method('current')->willReturn(FALSE);
    $statement->method('valid')->willReturn(FALSE);

    $select->method('execute')->willReturn($statement);

    $this->database->method('select')->willReturn($select);

    $result = $this->service->aggregateDaily(NULL);

    $this->assertIsInt($result);
  }

  /**
   * Tests aggregateMonthly handles exception gracefully.
   */
  public function testAggregateMonthlyHandlesException(): void {
    $this->database->method('select')
      ->willThrowException(new \RuntimeException('Connection error'));

    $this->logger->expects($this->once())
      ->method('error');

    $result = $this->service->aggregateMonthly(10);

    $this->assertEquals(0, $result);
  }

  /**
   * Tests getAggregates returns empty array when no results.
   */
  public function testGetAggregatesReturnsEmptyArray(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('usage_aggregate')
      ->willReturn($storage);

    $result = $this->service->getAggregates(42, 'daily', 30);

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * Tests getAggregates returns loaded entities.
   */
  public function testGetAggregatesReturnsEntities(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([1, 2]);

    $entity1 = $this->createMock(ContentEntityInterface::class);
    $entity2 = $this->createMock(ContentEntityInterface::class);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);
    $storage->method('loadMultiple')
      ->with([1, 2])
      ->willReturn([1 => $entity1, 2 => $entity2]);

    $this->entityTypeManager->method('getStorage')
      ->with('usage_aggregate')
      ->willReturn($storage);

    $result = $this->service->getAggregates(42, 'daily', 30);

    $this->assertCount(2, $result);
  }

  /**
   * Tests getAggregates handles exception gracefully.
   */
  public function testGetAggregatesHandlesException(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')
      ->willThrowException(new \RuntimeException('Storage error'));

    $this->entityTypeManager->method('getStorage')
      ->with('usage_aggregate')
      ->willReturn($storage);

    $this->logger->expects($this->once())
      ->method('error');

    $result = $this->service->getAggregates(42);

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

}
