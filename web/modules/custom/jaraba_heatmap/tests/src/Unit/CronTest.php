<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_heatmap\Unit;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\jaraba_heatmap\Service\HeatmapAggregatorService;
use Drupal\jaraba_heatmap\Service\HeatmapScreenshotService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

// Load the .module file for access to procedural functions.
require_once dirname(__DIR__, 3) . '/jaraba_heatmap.module';

/**
 * Unit tests for jaraba_heatmap hook_cron auxiliary functions.
 *
 * Tests the three independent cron flows:
 * - _jaraba_heatmap_cron_aggregation (daily)
 * - _jaraba_heatmap_cron_cleanup (weekly)
 * - _jaraba_heatmap_cron_anomaly_detection (daily)
 *
 * @group jaraba_heatmap
 */
class CronTest extends TestCase {

  /**
   * Mocked state service.
   */
  protected $state;

  /**
   * Mocked aggregator service.
   */
  protected $aggregator;

  /**
   * Mocked screenshot service.
   */
  protected $screenshot;

  /**
   * Mocked logger.
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->state = $this->createMock(StateInterface::class);
    $this->aggregator = $this->createMock(HeatmapAggregatorService::class);
    $this->screenshot = $this->createMock(HeatmapScreenshotService::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $loggerFactory->method('get')->willReturn($this->logger);

    $time = $this->createMock(TimeInterface::class);
    $time->method('getRequestTime')->willReturn(1700000000);

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')
      ->willReturnMap([
        ['retention_raw_days', 7],
        ['retention_aggregated_days', 90],
      ]);

    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->method('get')
      ->with('jaraba_heatmap.settings')
      ->willReturn($config);

    $container = new ContainerBuilder();
    $container->set('state', $this->state);
    $container->set('datetime.time', $time);
    $container->set('jaraba_heatmap.aggregator', $this->aggregator);
    $container->set('jaraba_heatmap.screenshot', $this->screenshot);
    $container->set('logger.factory', $loggerFactory);
    $container->set('config.factory', $configFactory);
    \Drupal::setContainer($container);
  }

  /**
   * Tests aggregation runs when it hasn't run today.
   *
   * @covers ::_jaraba_heatmap_cron_aggregation
   */
  public function testAggregationRunsWhenNotRunToday(): void {
    // Last run was yesterday.
    $this->state->method('get')
      ->with('jaraba_heatmap.last_aggregation', 0)
      ->willReturn(strtotime('yesterday'));

    $this->aggregator->expects($this->once())->method('aggregateDaily');
    $this->state->expects($this->once())
      ->method('set')
      ->with('jaraba_heatmap.last_aggregation', 1700000000);

    _jaraba_heatmap_cron_aggregation(1700000000);
  }

  /**
   * Tests aggregation does NOT run if it already ran today.
   *
   * @covers ::_jaraba_heatmap_cron_aggregation
   */
  public function testAggregationSkipsIfAlreadyRanToday(): void {
    // Last run was today (timestamp >= today's midnight).
    $this->state->method('get')
      ->with('jaraba_heatmap.last_aggregation', 0)
      ->willReturn(strtotime('today') + 100);

    $this->aggregator->expects($this->never())->method('aggregateDaily');

    _jaraba_heatmap_cron_aggregation(1700000000);
  }

  /**
   * Tests cleanup runs when more than a week has passed.
   *
   * @covers ::_jaraba_heatmap_cron_cleanup
   */
  public function testCleanupRunsAfterOneWeek(): void {
    $now = 1700000000;
    $eightDaysAgo = $now - (8 * 86400);

    $this->state->method('get')
      ->with('jaraba_heatmap.last_cleanup', 0)
      ->willReturn($eightDaysAgo);

    $this->aggregator->expects($this->once())->method('purgeOldEvents')->with(7);
    $this->aggregator->expects($this->once())->method('purgeOldAggregated')->with(90);
    $this->screenshot->expects($this->once())->method('cleanupExpiredScreenshots')->with(30);

    $this->state->expects($this->once())
      ->method('set')
      ->with('jaraba_heatmap.last_cleanup', $now);

    _jaraba_heatmap_cron_cleanup($now);
  }

  /**
   * Tests cleanup does NOT run before a week has passed.
   *
   * @covers ::_jaraba_heatmap_cron_cleanup
   */
  public function testCleanupSkipsBeforeOneWeek(): void {
    $now = 1700000000;
    $threeDaysAgo = $now - (3 * 86400);

    $this->state->method('get')
      ->with('jaraba_heatmap.last_cleanup', 0)
      ->willReturn($threeDaysAgo);

    $this->aggregator->expects($this->never())->method('purgeOldEvents');
    $this->aggregator->expects($this->never())->method('purgeOldAggregated');

    _jaraba_heatmap_cron_cleanup($now);
  }

  /**
   * Tests anomaly detection runs when it hasn't run today.
   *
   * @covers ::_jaraba_heatmap_cron_anomaly_detection
   */
  public function testAnomalyDetectionRunsWhenNotRunToday(): void {
    $this->state->method('get')
      ->with('jaraba_heatmap.last_anomaly_check', 0)
      ->willReturn(strtotime('yesterday'));

    $this->aggregator->expects($this->once())
      ->method('detectAnomalies')
      ->willReturn([]);

    $this->state->expects($this->once())
      ->method('set')
      ->with('jaraba_heatmap.last_anomaly_check', 1700000000);

    _jaraba_heatmap_cron_anomaly_detection(1700000000);
  }

  /**
   * Tests anomaly detection skips if already ran today.
   *
   * @covers ::_jaraba_heatmap_cron_anomaly_detection
   */
  public function testAnomalyDetectionSkipsIfAlreadyRanToday(): void {
    $this->state->method('get')
      ->with('jaraba_heatmap.last_anomaly_check', 0)
      ->willReturn(strtotime('today') + 100);

    $this->aggregator->expects($this->never())->method('detectAnomalies');

    _jaraba_heatmap_cron_anomaly_detection(1700000000);
  }

  /**
   * Tests anomaly detection logs warning when anomalies are found.
   *
   * @covers ::_jaraba_heatmap_cron_anomaly_detection
   */
  public function testAnomalyDetectionLogsWarning(): void {
    $this->state->method('get')
      ->with('jaraba_heatmap.last_anomaly_check', 0)
      ->willReturn(0);

    $anomalies = [
      ['tenant_id' => 1, 'page_path' => '/home', 'type' => 'drop', 'ratio' => 0.2],
      ['tenant_id' => 1, 'page_path' => '/shop', 'type' => 'spike', 'ratio' => 3.5],
    ];

    $this->aggregator->expects($this->once())
      ->method('detectAnomalies')
      ->willReturn($anomalies);

    $this->logger->expects($this->once())
      ->method('warning')
      ->with(
        'Anomalies detected: @count pages with unusual activity.',
        ['@count' => 2]
      );

    _jaraba_heatmap_cron_anomaly_detection(1700000000);
  }

}
