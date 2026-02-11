<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_heatmap\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Delete;
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

}
