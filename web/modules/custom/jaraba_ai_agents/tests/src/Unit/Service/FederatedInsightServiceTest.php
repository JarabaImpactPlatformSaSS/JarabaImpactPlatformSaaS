<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ai_agents\Unit\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_ai_agents\Service\FederatedInsightService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for FederatedInsightService (GAP-L5-H).
 *
 * @coversDefaultClass \Drupal\jaraba_ai_agents\Service\FederatedInsightService
 * @group jaraba_ai_agents
 */
class FederatedInsightServiceTest extends TestCase {

  /**
   * The service under test.
   */
  protected FederatedInsightService $service;

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

    $this->service = new FederatedInsightService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * @covers ::generateInsights
   */
  public function testGenerateInsightsRejectsInsufficientTenants(): void {
    $result = $this->service->generateInsights(['t1', 't2', 't3']);
    $this->assertEmpty($result);
  }

  /**
   * @covers ::generateInsights
   */
  public function testGenerateInsightsRejectsExactlyFourTenants(): void {
    $result = $this->service->generateInsights(['t1', 't2', 't3', 't4']);
    $this->assertEmpty($result);
  }

  /**
   * @covers ::generateInsights
   */
  public function testGenerateInsightsAcceptsFiveTenants(): void {
    // Without observability service, metrics will be empty, so no insights generated.
    // But the method should proceed past the k-anonymity check.
    $result = $this->service->generateInsights(['t1', 't2', 't3', 't4', 't5']);
    // With no observability, no metrics to aggregate -> no insights.
    $this->assertIsArray($result);
  }

  /**
   * @covers ::addNoise
   */
  public function testAddNoiseReturnsZeroForZero(): void {
    $result = $this->service->addNoise(0.0);
    $this->assertEquals(0.0, $result);
  }

  /**
   * @covers ::addNoise
   */
  public function testAddNoisePreservesOrderOfMagnitude(): void {
    $original = 1000.0;
    $results = [];
    for ($i = 0; $i < 100; $i++) {
      $results[] = $this->service->addNoise($original);
    }

    $avg = array_sum($results) / count($results);

    // Average should be within 20% of original (noise is bounded).
    $this->assertGreaterThan($original * 0.8, $avg);
    $this->assertLessThan($original * 1.2, $avg);
  }

  /**
   * @covers ::addNoise
   */
  public function testAddNoiseProducesVariation(): void {
    $original = 500.0;
    $results = [];
    for ($i = 0; $i < 50; $i++) {
      $results[] = $this->service->addNoise($original);
    }

    // Not all values should be exactly the same (noise adds variation).
    $unique = array_unique(array_map(fn($v) => round($v, 2), $results));
    $this->assertGreaterThan(1, count($unique));
  }

  /**
   * Tests k-anonymity constant.
   */
  public function testKAnonymityThreshold(): void {
    $this->assertEquals(5, FederatedInsightService::K_ANONYMITY_THRESHOLD);
  }

  /**
   * Tests noise epsilon constant.
   */
  public function testNoiseEpsilon(): void {
    $this->assertEquals(1.0, FederatedInsightService::NOISE_EPSILON);
  }

  /**
   * @covers ::generateInsights
   */
  public function testGenerateInsightsWithMockObservability(): void {
    $mockObservability = new class {

      public function getStats(string $period, string $tenantId): array {
        return [
          'total_executions' => rand(50, 200),
          'successful' => rand(40, 180),
          'failed' => rand(1, 20),
          'success_rate' => rand(80, 99),
          'total_cost' => rand(1, 50) / 10,
          'total_tokens' => rand(10000, 100000),
          'avg_duration_ms' => rand(500, 3000),
          'avg_quality_score' => rand(60, 95) / 100,
        ];
      }

    };

    $service = new FederatedInsightService(
      $this->entityTypeManager,
      $this->logger,
      $mockObservability,
    );

    // Need to mock entity storage for persistence.
    $mockStorage = $this->createMock(\Drupal\Core\Entity\EntityStorageInterface::class);
    $mockEntity = $this->createMock(\Drupal\Core\Entity\ContentEntityInterface::class);
    $mockStorage->method('create')->willReturn($mockEntity);
    $this->entityTypeManager->method('getStorage')
      ->with('aggregated_insight')
      ->willReturn($mockStorage);

    $tenants = ['t1', 't2', 't3', 't4', 't5', 't6', 't7'];
    $insights = $service->generateInsights($tenants);

    // Should generate at least usage pattern + cost optimization insights.
    $this->assertNotEmpty($insights);
    $this->assertGreaterThanOrEqual(2, count($insights));

    foreach ($insights as $insight) {
      $this->assertArrayHasKey('insight_type', $insight);
      $this->assertArrayHasKey('contributing_tenants', $insight);
      $this->assertGreaterThanOrEqual(5, $insight['contributing_tenants']);
      $this->assertArrayHasKey('confidence_score', $insight);
    }
  }

  /**
   * @covers ::generateInsights
   */
  public function testGenerateInsightsContainExpectedTypes(): void {
    $mockObservability = new class {

      public function getStats(string $period, string $tenantId): array {
        return [
          'total_executions' => 100,
          'successful' => 90,
          'failed' => 10,
          'success_rate' => 90.0,
          'total_cost' => 5.0,
          'total_tokens' => 50000,
          'avg_duration_ms' => 1500,
          'avg_quality_score' => 0.85,
        ];
      }

    };

    $mockStorage = $this->createMock(\Drupal\Core\Entity\EntityStorageInterface::class);
    $mockEntity = $this->createMock(\Drupal\Core\Entity\ContentEntityInterface::class);
    $mockStorage->method('create')->willReturn($mockEntity);
    $this->entityTypeManager->method('getStorage')
      ->with('aggregated_insight')
      ->willReturn($mockStorage);

    $service = new FederatedInsightService(
      $this->entityTypeManager,
      $this->logger,
      $mockObservability,
    );

    $tenants = ['t1', 't2', 't3', 't4', 't5'];
    $insights = $service->generateInsights($tenants);

    $types = array_column($insights, 'insight_type');
    $this->assertContains('ai_usage_pattern', $types);
    $this->assertContains('cost_optimization', $types);
    $this->assertContains('quality_trend', $types);
  }

}
