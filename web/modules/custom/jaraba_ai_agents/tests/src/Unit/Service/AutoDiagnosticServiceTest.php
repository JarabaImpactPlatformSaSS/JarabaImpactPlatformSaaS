<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ai_agents\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_ai_agents\Service\AutoDiagnosticService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for AutoDiagnosticService (GAP-L5-G).
 *
 * @coversDefaultClass \Drupal\jaraba_ai_agents\Service\AutoDiagnosticService
 * @group jaraba_ai_agents
 */
class AutoDiagnosticServiceTest extends TestCase {

  /**
   * The service under test.
   */
  protected AutoDiagnosticService $service;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock logger.
   */
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new AutoDiagnosticService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * @covers ::detectAnomalies
   */
  public function testDetectAnomaliesNoDataReturnsEmpty(): void {
    $metrics = [
      'p95_latency_ms' => 0,
      'avg_quality_score' => 1.0,
      'error_rate' => 0.0,
      'cache_hit_rate' => 100.0,
      'daily_cost' => 0.0,
      'avg_daily_cost' => 0.0,
      'total_executions' => 0,
    ];

    $anomalies = $this->service->detectAnomalies($metrics);
    $this->assertEmpty($anomalies);
  }

  /**
   * @covers ::detectAnomalies
   */
  public function testDetectAnomaliesHealthyMetricsNoAnomalies(): void {
    $metrics = [
      'p95_latency_ms' => 2000,
      'avg_quality_score' => 0.85,
      'error_rate' => 2.0,
      'cache_hit_rate' => 50.0,
      'daily_cost' => 5.0,
      'avg_daily_cost' => 4.0,
      'total_executions' => 100,
    ];

    $anomalies = $this->service->detectAnomalies($metrics);
    $this->assertEmpty($anomalies);
  }

  /**
   * @covers ::detectAnomalies
   */
  public function testDetectHighLatencyAnomaly(): void {
    $metrics = [
      'p95_latency_ms' => 6000,
      'avg_quality_score' => 0.85,
      'error_rate' => 2.0,
      'cache_hit_rate' => 50.0,
      'daily_cost' => 5.0,
      'avg_daily_cost' => 4.0,
      'total_executions' => 100,
    ];

    $anomalies = $this->service->detectAnomalies($metrics);
    $this->assertCount(1, $anomalies);
    $this->assertEquals('high_latency', $anomalies[0]['type']);
    $this->assertEquals('critical', $anomalies[0]['severity']);
    $this->assertEquals(6000, $anomalies[0]['detected_value']);
  }

  /**
   * @covers ::detectAnomalies
   */
  public function testDetectLowQualityAnomaly(): void {
    $metrics = [
      'p95_latency_ms' => 2000,
      'avg_quality_score' => 0.4,
      'error_rate' => 2.0,
      'cache_hit_rate' => 50.0,
      'daily_cost' => 5.0,
      'avg_daily_cost' => 4.0,
      'total_executions' => 100,
    ];

    $anomalies = $this->service->detectAnomalies($metrics);
    $this->assertCount(1, $anomalies);
    $this->assertEquals('low_quality', $anomalies[0]['type']);
    $this->assertEquals(0.4, $anomalies[0]['detected_value']);
  }

  /**
   * @covers ::detectAnomalies
   */
  public function testDetectProviderErrorsAnomaly(): void {
    $metrics = [
      'p95_latency_ms' => 2000,
      'avg_quality_score' => 0.85,
      'error_rate' => 15.0,
      'cache_hit_rate' => 50.0,
      'daily_cost' => 5.0,
      'avg_daily_cost' => 4.0,
      'total_executions' => 100,
    ];

    $anomalies = $this->service->detectAnomalies($metrics);
    $this->assertCount(1, $anomalies);
    $this->assertEquals('provider_errors', $anomalies[0]['type']);
    $this->assertEquals('warning', $anomalies[0]['severity']);
  }

  /**
   * @covers ::detectAnomalies
   */
  public function testDetectProviderErrorsCriticalSeverity(): void {
    $metrics = [
      'p95_latency_ms' => 2000,
      'avg_quality_score' => 0.85,
      'error_rate' => 30.0,
      'cache_hit_rate' => 50.0,
      'daily_cost' => 5.0,
      'avg_daily_cost' => 4.0,
      'total_executions' => 100,
    ];

    $anomalies = $this->service->detectAnomalies($metrics);
    $this->assertCount(1, $anomalies);
    $this->assertEquals('provider_errors', $anomalies[0]['type']);
    $this->assertEquals('critical', $anomalies[0]['severity']);
  }

  /**
   * @covers ::detectAnomalies
   */
  public function testDetectLowCacheHitAnomaly(): void {
    $metrics = [
      'p95_latency_ms' => 2000,
      'avg_quality_score' => 0.85,
      'error_rate' => 2.0,
      'cache_hit_rate' => 10.0,
      'daily_cost' => 5.0,
      'avg_daily_cost' => 4.0,
      'total_executions' => 100,
    ];

    $anomalies = $this->service->detectAnomalies($metrics);
    $this->assertCount(1, $anomalies);
    $this->assertEquals('low_cache_hit', $anomalies[0]['type']);
    $this->assertEquals('warning', $anomalies[0]['severity']);
  }

  /**
   * @covers ::detectAnomalies
   */
  public function testDetectCostSpikeAnomaly(): void {
    $metrics = [
      'p95_latency_ms' => 2000,
      'avg_quality_score' => 0.85,
      'error_rate' => 2.0,
      'cache_hit_rate' => 50.0,
      'daily_cost' => 25.0,
      'avg_daily_cost' => 10.0,
      'total_executions' => 100,
    ];

    $anomalies = $this->service->detectAnomalies($metrics);
    $this->assertCount(1, $anomalies);
    $this->assertEquals('cost_spike', $anomalies[0]['type']);
    $this->assertEquals('critical', $anomalies[0]['severity']);
  }

  /**
   * @covers ::detectAnomalies
   */
  public function testDetectMultipleAnomaliesSimultaneously(): void {
    $metrics = [
      'p95_latency_ms' => 7000,
      'avg_quality_score' => 0.3,
      'error_rate' => 50.0,
      'cache_hit_rate' => 5.0,
      'daily_cost' => 30.0,
      'avg_daily_cost' => 10.0,
      'total_executions' => 100,
    ];

    $anomalies = $this->service->detectAnomalies($metrics);
    $this->assertCount(5, $anomalies);

    $types = array_column($anomalies, 'type');
    $this->assertContains('high_latency', $types);
    $this->assertContains('low_quality', $types);
    $this->assertContains('provider_errors', $types);
    $this->assertContains('low_cache_hit', $types);
    $this->assertContains('cost_spike', $types);
  }

  /**
   * @covers ::planRemediation
   */
  public function testPlanRemediationMapsCorrectActions(): void {
    $cases = [
      ['type' => 'high_latency', 'expected' => 'auto_downgrade_tier'],
      ['type' => 'low_quality', 'expected' => 'auto_refresh_prompt'],
      ['type' => 'provider_errors', 'expected' => 'auto_rotate_provider'],
      ['type' => 'low_cache_hit', 'expected' => 'auto_warm_cache'],
      ['type' => 'cost_spike', 'expected' => 'auto_throttle'],
    ];

    foreach ($cases as $case) {
      $anomaly = [
        'type' => $case['type'],
        'severity' => 'critical',
        'detected_value' => 100,
        'threshold_value' => 50,
        'message' => 'Test anomaly',
      ];

      $plan = $this->service->planRemediation($anomaly);
      $this->assertEquals($case['expected'], $plan['action'], "Failed for type: {$case['type']}");
      $this->assertEquals($case['type'], $plan['anomaly_type']);
    }
  }

  /**
   * @covers ::calculateHealthScore
   */
  public function testCalculateHealthScorePerfectHealth(): void {
    $metrics = [
      'p95_latency_ms' => 1000,
      'avg_quality_score' => 0.95,
      'error_rate' => 1.0,
      'cache_hit_rate' => 80.0,
      'daily_cost' => 5.0,
      'avg_daily_cost' => 5.0,
      'total_executions' => 100,
    ];

    $score = $this->service->calculateHealthScore($metrics);
    $this->assertEquals(100, $score);
  }

  /**
   * @covers ::calculateHealthScore
   */
  public function testCalculateHealthScoreAllAnomalies(): void {
    $metrics = [
      'p95_latency_ms' => 7000,
      'avg_quality_score' => 0.3,
      'error_rate' => 20.0,
      'cache_hit_rate' => 10.0,
      'daily_cost' => 30.0,
      'avg_daily_cost' => 10.0,
      'total_executions' => 100,
    ];

    $score = $this->service->calculateHealthScore($metrics);
    // 100 - 20(latency) - 25(quality) - 20(errors) - 10(cache) - 15(cost) = 10
    $this->assertEquals(10, $score);
  }

  /**
   * @covers ::calculateHealthScore
   */
  public function testCalculateHealthScoreNeverBelowZero(): void {
    $metrics = [
      'p95_latency_ms' => 99999,
      'avg_quality_score' => 0.0,
      'error_rate' => 100.0,
      'cache_hit_rate' => 0.0,
      'daily_cost' => 999.0,
      'avg_daily_cost' => 1.0,
    ];

    $score = $this->service->calculateHealthScore($metrics);
    $this->assertGreaterThanOrEqual(0, $score);
  }

  /**
   * @covers ::calculateHealthScore
   */
  public function testCalculateHealthScorePartialDegradation(): void {
    $metrics = [
      'p95_latency_ms' => 4000,
      'avg_quality_score' => 0.7,
      'error_rate' => 7.0,
      'cache_hit_rate' => 50.0,
      'daily_cost' => 5.0,
      'avg_daily_cost' => 5.0,
    ];

    $score = $this->service->calculateHealthScore($metrics);
    // 100 - 10(latency ~70% threshold) - 10(quality <0.8) - 10(errors >5) = 70
    $this->assertEquals(70, $score);
  }

  /**
   * @covers ::collectMetrics
   */
  public function testCollectMetricsWithoutObservability(): void {
    $metrics = $this->service->collectMetrics('tenant-1');

    $this->assertEquals(0, $metrics['p95_latency_ms']);
    $this->assertEquals(1.0, $metrics['avg_quality_score']);
    $this->assertEquals(0.0, $metrics['error_rate']);
    $this->assertEquals(0, $metrics['total_executions']);
  }

  /**
   * @covers ::runDiagnostic
   */
  public function testRunDiagnosticDryRunSkipsExecution(): void {
    $result = $this->service->runDiagnostic('tenant-1', ['dry_run' => TRUE]);

    $this->assertTrue($result['dry_run']);
    $this->assertEquals('tenant-1', $result['tenant_id']);
    $this->assertArrayHasKey('metrics', $result);
    $this->assertArrayHasKey('anomalies', $result);
    $this->assertArrayHasKey('health_score', $result);
  }

  /**
   * @covers ::runDiagnostic
   */
  public function testRunDiagnosticExecutesRemediationsWhenNotDryRun(): void {
    // With no observability, metrics are default healthy.
    // No anomalies detected, so no remediations.
    $storage = $this->createMock(EntityStorageInterface::class);
    $this->entityTypeManager->method('getStorage')
      ->with('remediation_log')
      ->willReturn($storage);

    $result = $this->service->runDiagnostic('tenant-1');

    $this->assertFalse($result['dry_run']);
    $this->assertEmpty($result['anomalies']);
    $this->assertEmpty($result['remediations']);
    $this->assertEquals(100, $result['health_score']);
  }

  /**
   * Tests that constants are well-defined.
   */
  public function testThresholdConstants(): void {
    $this->assertEquals(5000, AutoDiagnosticService::LATENCY_THRESHOLD_MS);
    $this->assertEquals(0.6, AutoDiagnosticService::QUALITY_THRESHOLD);
    $this->assertEquals(10.0, AutoDiagnosticService::ERROR_RATE_THRESHOLD);
    $this->assertEquals(20.0, AutoDiagnosticService::CACHE_HIT_THRESHOLD);
    $this->assertEquals(2.0, AutoDiagnosticService::COST_SPIKE_MULTIPLIER);
    $this->assertEquals(5, AutoDiagnosticService::MAX_AUTO_REMEDIATIONS);
  }

}
