<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\AlertingService;
use Drupal\ecosistema_jaraba_core\Service\AuditLogService;
use Drupal\ecosistema_jaraba_core\Service\UsageAnomalyDetectorService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for UsageAnomalyDetectorService.
 *
 * @group ecosistema_jaraba_core
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\UsageAnomalyDetectorService
 */
class UsageAnomalyDetectorServiceTest extends TestCase {

  /**
   * The service under test.
   */
  protected UsageAnomalyDetectorService $service;

  /**
   * Mocked database connection.
   */
  protected Connection&MockObject $database;

  /**
   * Mocked config factory.
   */
  protected ConfigFactoryInterface&MockObject $configFactory;

  /**
   * Mocked alerting service.
   */
  protected AlertingService&MockObject $alertingService;

  /**
   * Mocked audit log service.
   */
  protected AuditLogService&MockObject $auditLogService;

  /**
   * Mocked logger.
   */
  protected LoggerInterface&MockObject $logger;

  /**
   * Mocked entity type manager.
   */
  protected EntityTypeManagerInterface&MockObject $entityTypeManager;

  /**
   * Mocked immutable config.
   */
  protected ImmutableConfig&MockObject $config;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->database = $this->createMock(Connection::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->alertingService = $this->createMock(AlertingService::class);
    $this->auditLogService = $this->createMock(AuditLogService::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);

    // Default config mock: anomaly_threshold = 2.0.
    $this->config = $this->createMock(ImmutableConfig::class);
    $this->config->method('get')
      ->with('anomaly_threshold')
      ->willReturn(2.0);

    $this->configFactory->method('get')
      ->with('ecosistema_jaraba_core.finops')
      ->willReturn($this->config);

    $this->service = new UsageAnomalyDetectorService(
      $this->database,
      $this->configFactory,
      $this->alertingService,
      $this->auditLogService,
      $this->logger,
      $this->entityTypeManager,
    );
  }

  /**
   * Tests that getAnomalyThreshold() returns the configured value.
   *
   * @covers ::getAnomalyThreshold
   */
  public function testGetAnomalyThresholdReturnsConfigValue(): void {
    // Config returns 3.5 as the threshold.
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')
      ->with('anomaly_threshold')
      ->willReturn(3.5);

    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->method('get')
      ->with('ecosistema_jaraba_core.finops')
      ->willReturn($config);

    $service = new UsageAnomalyDetectorService(
      $this->database,
      $configFactory,
      $this->alertingService,
      $this->auditLogService,
      $this->logger,
      $this->entityTypeManager,
    );

    $this->assertSame(3.5, $service->getAnomalyThreshold());
  }

  /**
   * Tests that getAnomalyThreshold() defaults to 2.0 when config is NULL.
   *
   * @covers ::getAnomalyThreshold
   */
  public function testGetAnomalyThresholdDefaultsTo2(): void {
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')
      ->with('anomaly_threshold')
      ->willReturn(NULL);

    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->method('get')
      ->with('ecosistema_jaraba_core.finops')
      ->willReturn($config);

    $service = new UsageAnomalyDetectorService(
      $this->database,
      $configFactory,
      $this->alertingService,
      $this->auditLogService,
      $this->logger,
      $this->entityTypeManager,
    );

    $this->assertSame(2.0, $service->getAnomalyThreshold());
  }

  /**
   * Tests analyzeMetric returns null for insufficient data points.
   *
   * The service requires at least 5 daily data points to produce a
   * statistically valid result. With fewer points, it should return null.
   *
   * @covers ::analyzeMetric
   */
  public function testAnalyzeMetricReturnsNullForInsufficientData(): void {
    // We need to create a partial mock that overrides protected methods.
    // Since getDailyAggregatedValues is protected, we use a reflection-based
    // approach: mock the database to return fewer than 5 rows.
    $service = $this->getMockBuilder(UsageAnomalyDetectorService::class)
      ->setConstructorArgs([
        $this->database,
        $this->configFactory,
        $this->alertingService,
        $this->auditLogService,
        $this->logger,
        $this->entityTypeManager,
      ])
      ->onlyMethods(['getDailyAggregatedValues', 'getTodayValue'])
      ->getMock();

    // Return only 3 data points (less than MIN_DATA_POINTS = 5).
    $service->method('getDailyAggregatedValues')
      ->willReturn([10.0, 12.0, 11.0]);

    $result = $service->analyzeMetric('tenant_1', 'api_calls');

    $this->assertNull($result);
  }

  /**
   * Tests analyzeMetric detects anomaly when value exceeds threshold.
   *
   * When today's value is more than mean + threshold * stddev,
   * the method should return an anomaly array.
   *
   * @covers ::analyzeMetric
   */
  public function testAnalyzeMetricDetectsAnomaly(): void {
    $service = $this->getMockBuilder(UsageAnomalyDetectorService::class)
      ->setConstructorArgs([
        $this->database,
        $this->configFactory,
        $this->alertingService,
        $this->auditLogService,
        $this->logger,
        $this->entityTypeManager,
      ])
      ->onlyMethods(['getDailyAggregatedValues', 'getTodayValue'])
      ->getMock();

    // Provide 10 data points that are all 10.0 except one variation.
    // Mean = 10.0, StdDev ~= 0 with all same values.
    // Let's use values with clear mean and stddev:
    // Values: [10, 10, 10, 10, 10, 10, 10, 10, 10, 10]
    // Mean = 10, StdDev = 0
    // With stddev = 0 and today value != mean, returns anomaly.
    // Let's use more varied data:
    // [100, 100, 100, 100, 100, 100, 100] -> mean=100, stddev=0
    // Today value = 200 -> different from constant series -> anomaly.
    $service->method('getDailyAggregatedValues')
      ->willReturn([100.0, 100.0, 100.0, 100.0, 100.0, 100.0, 100.0]);

    $service->method('getTodayValue')
      ->willReturn(200.0);

    $result = $service->analyzeMetric('tenant_1', 'api_calls');

    $this->assertNotNull($result);
    $this->assertIsArray($result);
    $this->assertEquals('tenant_1', $result['tenant_id']);
    $this->assertEquals('api_calls', $result['metric']);
    $this->assertEquals(200.0, $result['current_value']);
    $this->assertEquals(100.0, $result['mean']);
    $this->assertEquals('above', $result['direction']);
    $this->assertArrayHasKey('detected_at', $result);
  }

  /**
   * Tests analyzeMetric returns null for normal usage within threshold.
   *
   * @covers ::analyzeMetric
   */
  public function testAnalyzeMetricReturnsNullForNormalUsage(): void {
    $service = $this->getMockBuilder(UsageAnomalyDetectorService::class)
      ->setConstructorArgs([
        $this->database,
        $this->configFactory,
        $this->alertingService,
        $this->auditLogService,
        $this->logger,
        $this->entityTypeManager,
      ])
      ->onlyMethods(['getDailyAggregatedValues', 'getTodayValue'])
      ->getMock();

    // Values with some variance: mean ~= 100, stddev ~= 10
    // [90, 95, 100, 105, 110, 90, 95, 100, 105, 110]
    // Mean = 100, stddev ~ 7.07
    // Today = 105 -> deviation = 5 / 7.07 ~ 0.71 < threshold 2.0
    $service->method('getDailyAggregatedValues')
      ->willReturn([90.0, 95.0, 100.0, 105.0, 110.0, 90.0, 95.0, 100.0, 105.0, 110.0]);

    $service->method('getTodayValue')
      ->willReturn(105.0);

    $result = $service->analyzeMetric('tenant_1', 'api_calls');

    $this->assertNull($result);
  }

  /**
   * Tests notifyAnomalies sends alerts for each anomaly.
   *
   * @covers ::notifyAnomalies
   */
  public function testNotifyAnomaliesSendsAlerts(): void {
    $anomalies = [
      [
        'tenant_id' => '1',
        'metric' => 'api_calls',
        'current_value' => 500.0,
        'mean' => 100.0,
        'std_dev' => 20.0,
        'deviation' => 20.0,
        'threshold' => 2.0,
        'direction' => 'above',
        'detected_at' => time(),
      ],
      [
        'tenant_id' => '2',
        'metric' => 'storage_mb',
        'current_value' => 5000.0,
        'mean' => 1000.0,
        'std_dev' => 200.0,
        'deviation' => 20.0,
        'threshold' => 2.0,
        'direction' => 'above',
        'detected_at' => time(),
      ],
    ];

    // AlertingService::send() should be called once per anomaly.
    $this->alertingService->expects($this->exactly(2))
      ->method('send');

    $this->service->notifyAnomalies($anomalies);
  }

  /**
   * Tests notifyAnomalies logs each anomaly to the audit log.
   *
   * @covers ::notifyAnomalies
   */
  public function testNotifyAnomaliesLogsToAuditLog(): void {
    $anomalies = [
      [
        'tenant_id' => '1',
        'metric' => 'api_calls',
        'current_value' => 500.0,
        'mean' => 100.0,
        'std_dev' => 20.0,
        'deviation' => 20.0,
        'threshold' => 2.0,
        'direction' => 'above',
        'detected_at' => time(),
      ],
    ];

    // AuditLogService::log() should be called with the anomaly event.
    $this->auditLogService->expects($this->once())
      ->method('log')
      ->with(
        'usage_anomaly_detected',
        $this->callback(function (array $context): bool {
          return $context['severity'] === 'warning'
            && $context['tenant_id'] === 1
            && $context['target_type'] === 'tenant_metering'
            && isset($context['details']['metric'])
            && $context['details']['metric'] === 'api_calls';
        })
      );

    $this->service->notifyAnomalies($anomalies);
  }

  /**
   * Tests notifyAnomalies handles empty array gracefully.
   *
   * @covers ::notifyAnomalies
   */
  public function testNotifyAnomaliesHandlesEmptyArray(): void {
    // Neither alerting nor audit should be called.
    $this->alertingService->expects($this->never())
      ->method('send');

    $this->auditLogService->expects($this->never())
      ->method('log');

    $this->service->notifyAnomalies([]);
  }

  /**
   * Tests analyzeMetric returns null when today has no activity.
   *
   * @covers ::analyzeMetric
   */
  public function testAnalyzeMetricReturnsNullWhenNoTodayValue(): void {
    $service = $this->getMockBuilder(UsageAnomalyDetectorService::class)
      ->setConstructorArgs([
        $this->database,
        $this->configFactory,
        $this->alertingService,
        $this->auditLogService,
        $this->logger,
        $this->entityTypeManager,
      ])
      ->onlyMethods(['getDailyAggregatedValues', 'getTodayValue'])
      ->getMock();

    $service->method('getDailyAggregatedValues')
      ->willReturn([100.0, 100.0, 100.0, 100.0, 100.0]);

    $service->method('getTodayValue')
      ->willReturn(NULL);

    $result = $service->analyzeMetric('tenant_1', 'api_calls');

    $this->assertNull($result);
  }

  /**
   * Tests that anomaly detection with varied data reports correct direction.
   *
   * @covers ::analyzeMetric
   */
  public function testAnalyzeMetricReportsCorrectDirectionBelow(): void {
    $service = $this->getMockBuilder(UsageAnomalyDetectorService::class)
      ->setConstructorArgs([
        $this->database,
        $this->configFactory,
        $this->alertingService,
        $this->auditLogService,
        $this->logger,
        $this->entityTypeManager,
      ])
      ->onlyMethods(['getDailyAggregatedValues', 'getTodayValue'])
      ->getMock();

    // Constant values at 100, today's value at 0 -> below direction.
    $service->method('getDailyAggregatedValues')
      ->willReturn([100.0, 100.0, 100.0, 100.0, 100.0, 100.0, 100.0]);

    $service->method('getTodayValue')
      ->willReturn(0.0);

    $result = $service->analyzeMetric('tenant_1', 'api_calls');

    $this->assertNotNull($result);
    $this->assertEquals('below', $result['direction']);
  }

}
