<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_heatmap\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Delete;
use Drupal\Core\Database\Query\Select;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\jaraba_heatmap\Service\HeatmapAggregatorService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for HeatmapAggregatorService.
 *
 * @group jaraba_heatmap
 * @coversDefaultClass \Drupal\jaraba_heatmap\Service\HeatmapAggregatorService
 */
class HeatmapAggregatorServiceTest extends TestCase {

  /**
   * The service under test.
   *
   * @var \Drupal\jaraba_heatmap\Service\HeatmapAggregatorService
   */
  protected HeatmapAggregatorService $service;

  /**
   * Mocked database connection.
   *
   * @var \Drupal\Core\Database\Connection|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $database;

  /**
   * Mocked logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * Mocked config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $configFactory;

  /**
   * Mocked config object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->database = $this->createMock(Connection::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->config = $this->createMock(ImmutableConfig::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);

    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $loggerFactory->method('get')
      ->with('jaraba_heatmap')
      ->willReturn($this->logger);

    $this->configFactory->method('get')
      ->with('jaraba_heatmap.settings')
      ->willReturn($this->config);

    $this->service = new HeatmapAggregatorService(
      $this->database,
      $loggerFactory,
      $this->configFactory,
    );
  }

  /**
   * Tests that BUCKET_X_SIZE constant is 5.
   *
   * @covers ::BUCKET_X_SIZE
   */
  public function testBucketXSizeIsFive(): void {
    $this->assertSame(5, HeatmapAggregatorService::BUCKET_X_SIZE);
  }

  /**
   * Tests that BUCKET_Y_SIZE constant is 50.
   *
   * @covers ::BUCKET_Y_SIZE
   */
  public function testBucketYSizeIsFifty(): void {
    $this->assertSame(50, HeatmapAggregatorService::BUCKET_Y_SIZE);
  }

  /**
   * Tests purgeOldEvents uses the configured retention days.
   *
   * When config returns 14 days, the delete condition should use
   * a timestamp corresponding to 14 days ago.
   *
   * @covers ::purgeOldEvents
   */
  public function testPurgeOldEventsUsesRetentionConfig(): void {
    $this->config->method('get')
      ->willReturnMap([
        ['retention_raw_days', 14],
        ['retention_aggregated_days', 90],
      ]);

    $expectedCutoff = strtotime('-14 days');

    $deleteQuery = $this->createMock(Delete::class);
    $deleteQuery->expects($this->once())
      ->method('condition')
      ->with(
        'created_at',
        $this->callback(function ($value) use ($expectedCutoff) {
          // Allow a 5-second tolerance for execution time.
          return abs($value - $expectedCutoff) < 5;
        }),
        '<'
      )
      ->willReturnSelf();
    $deleteQuery->method('execute')->willReturn(10);

    $this->database->expects($this->once())
      ->method('delete')
      ->with('heatmap_events')
      ->willReturn($deleteQuery);

    $result = $this->service->purgeOldEvents();
    $this->assertSame(10, $result);
  }

  /**
   * Tests purgeOldEvents defaults to 7 days when config returns null.
   *
   * @covers ::purgeOldEvents
   */
  public function testPurgeOldEventsDefaultsTo7Days(): void {
    $this->config->method('get')
      ->willReturnMap([
        ['retention_raw_days', NULL],
        ['retention_aggregated_days', NULL],
      ]);

    $expectedCutoff = strtotime('-7 days');

    $deleteQuery = $this->createMock(Delete::class);
    $deleteQuery->expects($this->once())
      ->method('condition')
      ->with(
        'created_at',
        $this->callback(function ($value) use ($expectedCutoff) {
          // Allow a 5-second tolerance for execution time.
          return abs($value - $expectedCutoff) < 5;
        }),
        '<'
      )
      ->willReturnSelf();
    $deleteQuery->method('execute')->willReturn(5);

    $this->database->expects($this->once())
      ->method('delete')
      ->with('heatmap_events')
      ->willReturn($deleteQuery);

    $result = $this->service->purgeOldEvents();
    $this->assertSame(5, $result);
  }

  /**
   * Tests purgeOldAggregated uses config for retention days.
   *
   * When config returns 30 days for aggregated retention,
   * the delete should use the correct cutoff date.
   *
   * @covers ::purgeOldAggregated
   */
  public function testPurgeOldAggregatedUsesConfig(): void {
    $this->config->method('get')
      ->willReturnMap([
        ['retention_raw_days', 7],
        ['retention_aggregated_days', 30],
      ]);

    $expectedCutoffDate = date('Y-m-d', strtotime('-30 days'));

    $deleteQueryAgg = $this->createMock(Delete::class);
    $deleteQueryAgg->expects($this->once())
      ->method('condition')
      ->with('date', $expectedCutoffDate, '<')
      ->willReturnSelf();
    $deleteQueryAgg->method('execute')->willReturn(15);

    $deleteQueryScroll = $this->createMock(Delete::class);
    $deleteQueryScroll->expects($this->once())
      ->method('condition')
      ->with('date', $expectedCutoffDate, '<')
      ->willReturnSelf();
    $deleteQueryScroll->method('execute')->willReturn(8);

    $this->database->expects($this->exactly(2))
      ->method('delete')
      ->willReturnCallback(function (string $table) use ($deleteQueryAgg, $deleteQueryScroll) {
        return match ($table) {
          'heatmap_aggregated' => $deleteQueryAgg,
          'heatmap_scroll_depth' => $deleteQueryScroll,
        };
      });

    $result = $this->service->purgeOldAggregated();
    $this->assertSame(23, $result);
  }

  /**
   * Tests purgeOldEvents with explicit days parameter bypasses config.
   *
   * @covers ::purgeOldEvents
   */
  public function testPurgeOldEventsWithExplicitDays(): void {
    $expectedCutoff = strtotime('-3 days');

    $deleteQuery = $this->createMock(Delete::class);
    $deleteQuery->expects($this->once())
      ->method('condition')
      ->with(
        'created_at',
        $this->callback(function ($value) use ($expectedCutoff) {
          return abs($value - $expectedCutoff) < 5;
        }),
        '<'
      )
      ->willReturnSelf();
    $deleteQuery->method('execute')->willReturn(20);

    $this->database->expects($this->once())
      ->method('delete')
      ->with('heatmap_events')
      ->willReturn($deleteQuery);

    // Config should NOT be consulted when explicit $days is passed.
    $result = $this->service->purgeOldEvents(3);
    $this->assertSame(20, $result);
  }

  /**
   * Tests purgeOldAggregated with explicit days parameter.
   *
   * @covers ::purgeOldAggregated
   */
  public function testPurgeOldAggregatedWithExplicitDays(): void {
    $expectedCutoffDate = date('Y-m-d', strtotime('-45 days'));

    $deleteQueryAgg = $this->createMock(Delete::class);
    $deleteQueryAgg->expects($this->once())
      ->method('condition')
      ->with('date', $expectedCutoffDate, '<')
      ->willReturnSelf();
    $deleteQueryAgg->method('execute')->willReturn(5);

    $deleteQueryScroll = $this->createMock(Delete::class);
    $deleteQueryScroll->expects($this->once())
      ->method('condition')
      ->with('date', $expectedCutoffDate, '<')
      ->willReturnSelf();
    $deleteQueryScroll->method('execute')->willReturn(3);

    $this->database->expects($this->exactly(2))
      ->method('delete')
      ->willReturnCallback(function (string $table) use ($deleteQueryAgg, $deleteQueryScroll) {
        return match ($table) {
          'heatmap_aggregated' => $deleteQueryAgg,
          'heatmap_scroll_depth' => $deleteQueryScroll,
        };
      });

    $result = $this->service->purgeOldAggregated(45);
    $this->assertSame(8, $result);
  }

  /**
   * Tests detectAnomalies returns empty when no data for yesterday.
   *
   * @covers ::detectAnomalies
   */
  public function testDetectAnomaliesReturnsEmptyWhenNoData(): void {
    $this->config->method('get')
      ->willReturn(NULL);

    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchAll')->willReturn([]);

    $selectQuery = $this->createMock(Select::class);
    $selectQuery->method('fields')->willReturnSelf();
    $selectQuery->method('addExpression')->willReturnSelf();
    $selectQuery->method('condition')->willReturnSelf();
    $selectQuery->method('groupBy')->willReturnSelf();
    $selectQuery->method('execute')->willReturn($statement);

    $this->database->method('select')->willReturn($selectQuery);

    $result = $this->service->detectAnomalies();
    $this->assertSame([], $result);
  }

  /**
   * Tests detectAnomalies detects a traffic drop.
   *
   * Yesterday has 10 events, avg of 7 days is 100 → ratio = 0.1 → drop alert.
   *
   * @covers ::detectAnomalies
   */
  public function testDetectAnomaliesDetectsDrop(): void {
    $this->config->method('get')
      ->willReturn(NULL);

    // Yesterday data: 10 events.
    $yesterdayRow = (object) [
      'tenant_id' => 1,
      'page_path' => '/productos',
      'total_events' => 10,
    ];

    $yesterdayStatement = $this->createMock(StatementInterface::class);
    $yesterdayStatement->method('fetchAll')->willReturn([$yesterdayRow]);

    // Average data: 100 avg events.
    $avgRow = (object) [
      'tenant_id' => 1,
      'page_path' => '/productos',
      'avg_events' => 100.0,
    ];

    $avgStatement = $this->createMock(StatementInterface::class);
    $avgStatement->method('fetchAll')->willReturn([$avgRow]);

    $selectCallCount = 0;
    $this->database->method('select')
      ->willReturnCallback(function () use (&$selectCallCount, $yesterdayStatement, $avgStatement) {
        $selectCallCount++;
        $selectQuery = $this->createMock(Select::class);
        $selectQuery->method('fields')->willReturnSelf();
        $selectQuery->method('addExpression')->willReturnSelf();
        $selectQuery->method('condition')->willReturnSelf();
        $selectQuery->method('groupBy')->willReturnSelf();
        $selectQuery->method('execute')->willReturn(
          $selectCallCount === 1 ? $yesterdayStatement : $avgStatement
        );
        return $selectQuery;
      });

    $result = $this->service->detectAnomalies();
    $this->assertCount(1, $result);
    $this->assertSame('drop', $result[0]['type']);
    $this->assertSame(1, $result[0]['tenant_id']);
    $this->assertSame('/productos', $result[0]['page_path']);
    $this->assertSame(10, $result[0]['yesterday_count']);
  }

  /**
   * Tests detectAnomalies detects a traffic spike.
   *
   * Yesterday has 500 events, avg of 7 days is 100 → ratio = 5.0 → spike alert.
   *
   * @covers ::detectAnomalies
   */
  public function testDetectAnomaliesDetectsSpike(): void {
    $this->config->method('get')
      ->willReturn(NULL);

    $yesterdayRow = (object) [
      'tenant_id' => 2,
      'page_path' => '/landing',
      'total_events' => 500,
    ];

    $yesterdayStatement = $this->createMock(StatementInterface::class);
    $yesterdayStatement->method('fetchAll')->willReturn([$yesterdayRow]);

    $avgRow = (object) [
      'tenant_id' => 2,
      'page_path' => '/landing',
      'avg_events' => 100.0,
    ];

    $avgStatement = $this->createMock(StatementInterface::class);
    $avgStatement->method('fetchAll')->willReturn([$avgRow]);

    $selectCallCount = 0;
    $this->database->method('select')
      ->willReturnCallback(function () use (&$selectCallCount, $yesterdayStatement, $avgStatement) {
        $selectCallCount++;
        $selectQuery = $this->createMock(Select::class);
        $selectQuery->method('fields')->willReturnSelf();
        $selectQuery->method('addExpression')->willReturnSelf();
        $selectQuery->method('condition')->willReturnSelf();
        $selectQuery->method('groupBy')->willReturnSelf();
        $selectQuery->method('execute')->willReturn(
          $selectCallCount === 1 ? $yesterdayStatement : $avgStatement
        );
        return $selectQuery;
      });

    $result = $this->service->detectAnomalies();
    $this->assertCount(1, $result);
    $this->assertSame('spike', $result[0]['type']);
    $this->assertSame(500, $result[0]['yesterday_count']);
    $this->assertSame(100.0, $result[0]['avg_count']);
  }

  /**
   * Tests detectAnomalies returns empty when traffic is within normal range.
   *
   * Yesterday has 90 events, avg is 100 → ratio = 0.9 → normal (within 50%).
   *
   * @covers ::detectAnomalies
   */
  public function testDetectAnomaliesNormalTraffic(): void {
    $this->config->method('get')
      ->willReturn(NULL);

    $yesterdayRow = (object) [
      'tenant_id' => 1,
      'page_path' => '/home',
      'total_events' => 90,
    ];

    $yesterdayStatement = $this->createMock(StatementInterface::class);
    $yesterdayStatement->method('fetchAll')->willReturn([$yesterdayRow]);

    $avgRow = (object) [
      'tenant_id' => 1,
      'page_path' => '/home',
      'avg_events' => 100.0,
    ];

    $avgStatement = $this->createMock(StatementInterface::class);
    $avgStatement->method('fetchAll')->willReturn([$avgRow]);

    $selectCallCount = 0;
    $this->database->method('select')
      ->willReturnCallback(function () use (&$selectCallCount, $yesterdayStatement, $avgStatement) {
        $selectCallCount++;
        $selectQuery = $this->createMock(Select::class);
        $selectQuery->method('fields')->willReturnSelf();
        $selectQuery->method('addExpression')->willReturnSelf();
        $selectQuery->method('condition')->willReturnSelf();
        $selectQuery->method('groupBy')->willReturnSelf();
        $selectQuery->method('execute')->willReturn(
          $selectCallCount === 1 ? $yesterdayStatement : $avgStatement
        );
        return $selectQuery;
      });

    $result = $this->service->detectAnomalies();
    $this->assertSame([], $result);
  }

}
