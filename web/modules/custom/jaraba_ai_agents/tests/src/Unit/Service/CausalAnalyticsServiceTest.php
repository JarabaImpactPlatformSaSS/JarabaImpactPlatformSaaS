<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ai_agents\Unit\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_ai_agents\Service\CausalAnalyticsService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for CausalAnalyticsService (GAP-L5-I).
 *
 * @coversDefaultClass \Drupal\jaraba_ai_agents\Service\CausalAnalyticsService
 * @group jaraba_ai_agents
 */
class CausalAnalyticsServiceTest extends TestCase {

  /**
   * The service under test.
   */
  protected CausalAnalyticsService $service;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock logger.
   */
  protected LoggerInterface $logger;

  /**
   * Mock storage.
   */
  protected EntityStorageInterface $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->storage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->with('causal_analysis')
      ->willReturn($this->storage);

    // Create mock entity for persistence.
    $mockEntity = $this->createMock(ContentEntityInterface::class);
    $mockEntity->method('id')->willReturn('1');
    $this->storage->method('create')->willReturn($mockEntity);

    $this->service = new CausalAnalyticsService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * @covers ::analyze
   */
  public function testAnalyzeRejectsInvalidQueryType(): void {
    $result = $this->service->analyze('test query', 'invalid', [], 'tenant-1');
    $this->assertFalse($result['success']);
    $this->assertStringContainsString('Invalid query type', $result['error']);
  }

  /**
   * @covers ::analyze
   */
  public function testAnalyzeDiagnosticQueryReturnsStructuredResult(): void {
    $result = $this->service->analyze(
      'Why did conversions drop?',
      'diagnostic',
      ['metrics' => ['conversions' => [100, 80, 60]]],
      'tenant-1',
    );

    $this->assertTrue($result['success']);
    $this->assertEquals('diagnostic', $result['query_type']);
    $this->assertArrayHasKey('causal_factors', $result);
    $this->assertArrayHasKey('recommendations', $result);
    $this->assertArrayHasKey('confidence_score', $result);
    $this->assertArrayHasKey('duration_ms', $result);
  }

  /**
   * @covers ::analyze
   */
  public function testAnalyzeCounterfactualQueryReturnsResult(): void {
    $result = $this->service->analyze(
      'What if we raise prices 10%?',
      'counterfactual',
      ['pricing' => ['current' => 99, 'proposed' => 109]],
      'tenant-1',
    );

    $this->assertTrue($result['success']);
    $this->assertEquals('counterfactual', $result['query_type']);
  }

  /**
   * @covers ::analyze
   */
  public function testAnalyzePredictiveQueryReturnsResult(): void {
    $result = $this->service->analyze(
      'What will happen next quarter?',
      'predictive',
      ['trends' => [1, 2, 3, 4]],
      'tenant-1',
    );

    $this->assertTrue($result['success']);
    $this->assertEquals('predictive', $result['query_type']);
  }

  /**
   * @covers ::analyze
   */
  public function testAnalyzePrescriptiveQueryReturnsResult(): void {
    $result = $this->service->analyze(
      'How to increase retention?',
      'prescriptive',
      [],
      'tenant-1',
    );

    $this->assertTrue($result['success']);
    $this->assertEquals('prescriptive', $result['query_type']);
  }

  /**
   * @covers ::analyze
   */
  public function testAnalyzeWithoutAiProviderUsesRuleBasedFallback(): void {
    $result = $this->service->analyze(
      'Why did traffic drop?',
      'diagnostic',
      ['metrics' => ['traffic' => [1000, 800, 500]]],
      'tenant-1',
    );

    $this->assertTrue($result['success']);
    $this->assertStringContainsString('Rule-based', $result['summary']);
    $this->assertEquals(0.3, $result['confidence_score']);
    $this->assertEquals(0.0, $result['cost']);
  }

  /**
   * @covers ::analyze
   */
  public function testAnalyzeRuleBasedDetectsCausalFactorsFromMetrics(): void {
    $result = $this->service->analyze(
      'Why?',
      'diagnostic',
      ['metrics' => ['revenue' => [1000, 500]]],
      'tenant-1',
    );

    $this->assertTrue($result['success']);
    // Rule-based should detect the revenue factor.
    $this->assertNotEmpty($result['causal_factors']);

    $factor = $result['causal_factors'][0];
    $this->assertEquals('revenue', $factor['factor']);
    $this->assertEquals('negative', $factor['direction']);
  }

  /**
   * @covers ::analyze
   */
  public function testAnalyzeRuleBasedRecommendsEnablingAiProvider(): void {
    $result = $this->service->analyze('test', 'diagnostic', [], 'tenant-1');

    $this->assertNotEmpty($result['recommendations']);
    $hasAiRecommendation = FALSE;
    foreach ($result['recommendations'] as $rec) {
      if (str_contains($rec['action'], 'AI provider')) {
        $hasAiRecommendation = TRUE;
      }
    }
    $this->assertTrue($hasAiRecommendation);
  }

  /**
   * @covers ::analyze
   */
  public function testAnalyzePersistsResult(): void {
    $this->storage->expects($this->once())->method('create');

    $this->service->analyze('test query', 'diagnostic', [], 'tenant-1');
  }

  /**
   * @covers ::analyze
   */
  public function testAnalyzeAllValidQueryTypes(): void {
    $validTypes = ['diagnostic', 'counterfactual', 'predictive', 'prescriptive'];

    foreach ($validTypes as $type) {
      $result = $this->service->analyze('test', $type, [], 'tenant-1');
      $this->assertTrue($result['success'], "Failed for type: {$type}");
      $this->assertEquals($type, $result['query_type']);
    }
  }

}
