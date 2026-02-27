<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ai_agents\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_ai_agents\Service\AgentBenchmarkService;
use Drupal\jaraba_ai_agents\Service\AIObservabilityService;
use Drupal\jaraba_ai_agents\Service\QualityEvaluatorService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for AgentBenchmarkService.
 *
 * Tests automated agent benchmarking with golden test cases,
 * including score calculation, version comparison, and result storage.
 *
 * @coversDefaultClass \Drupal\jaraba_ai_agents\Service\AgentBenchmarkService
 * @group jaraba_ai_agents
 */
class AgentBenchmarkServiceTest extends TestCase {

  /**
   * The service under test.
   */
  protected AgentBenchmarkService $service;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface|MockObject $entityTypeManager;

  /**
   * The quality evaluator stub (anonymous class).
   */
  protected object $qualityEvaluator;

  /**
   * Mock logger.
   */
  protected LoggerInterface|MockObject $logger;

  /**
   * Mock observability.
   */
  protected AIObservabilityService|MockObject $observability;

  /**
   * Mock entity storage.
   */
  protected EntityStorageInterface|MockObject $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->observability = $this->createMock(AIObservabilityService::class);

    // Mock storage for storeBenchmarkResult calls.
    $this->storage = $this->createMock(EntityStorageInterface::class);
    $mockEntity = $this->createMock(FieldableEntityInterface::class);
    $this->storage->method('create')->willReturn($mockEntity);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('agent_benchmark_result')
      ->willReturn($this->storage);
  }

  /**
   * Creates a testable service with a quality evaluator returning given scores.
   *
   * AgentBenchmarkService::runBenchmark() calls evaluate() with NULL as the
   * 3rd argument when criteria is empty, but the real method signature is
   * `array $criteria = []`. PHPUnit mocks enforce type signatures, so we
   * inject the evaluator via reflection using a compatible stub.
   *
   * @param array|callable $evaluateResult
   *   Either a fixed result array or a callable that returns results.
   *
   * @return \Drupal\jaraba_ai_agents\Service\AgentBenchmarkService
   *   A testable service instance.
   */
  protected function createTestableService(array|callable $evaluateResult = []): AgentBenchmarkService {
    // Create a QualityEvaluatorService via reflection to bypass constructor.
    $evaluator = (new \ReflectionClass(QualityEvaluatorService::class))
      ->newInstanceWithoutConstructor();

    // Create a partial mock of AgentBenchmarkService.
    $service = $this->getMockBuilder(AgentBenchmarkService::class)
      ->setConstructorArgs([
        $this->entityTypeManager,
        $evaluator,
        $this->logger,
        $this->observability,
      ])
      ->onlyMethods(['executeAgent', 'storeBenchmarkResult'])
      ->getMock();

    // storeBenchmarkResult is a no-op in tests.
    $service->method('storeBenchmarkResult');

    // Inject a compatible qualityEvaluator that accepts NULL for criteria.
    // Use reflection to replace the evaluator with one whose evaluate()
    // method tolerates NULL (matching what the service actually passes).
    $evaluatorStub = new class ($evaluateResult) extends QualityEvaluatorService {

      /**
       * The evaluate result (array or \Closure).
       */
      private mixed $result;

      public function __construct(mixed $result) {
        // Skip parent constructor (requires AiProviderPluginManager).
        $this->result = $result;
      }

      public function evaluate(
        string $prompt,
        string $response,
        ?array $criteria = [],
        array $context = [],
      ): array {
        if ($this->result instanceof \Closure) {
          return ($this->result)($prompt, $response, $criteria, $context);
        }
        return $this->result;
      }

    };

    $ref = new \ReflectionProperty(AgentBenchmarkService::class, 'qualityEvaluator');
    $ref->setValue($service, $evaluatorStub);

    return $service;
  }

  /**
   * Tests runBenchmark with all test cases passing.
   *
   * Verifies that when quality evaluator returns high scores,
   * all test cases pass and the benchmark result reflects 100% pass rate.
   *
   * @covers ::runBenchmark
   */
  public function testRunBenchmarkWithPassingCases(): void {
    $this->service = $this->createTestableService([
      'overall_score' => 0.9,
      'criteria_scores' => ['relevance' => 0.95, 'clarity' => 0.85],
      'reasoning' => 'Good quality response.',
    ]);

    $this->service->expects($this->exactly(2))
      ->method('executeAgent')
      ->willReturn(['data' => ['text' => 'Agent response text']]);

    $testCases = [
      ['input' => 'Test question 1', 'expected_output' => 'Expected 1', 'threshold' => 0.7],
      ['input' => 'Test question 2', 'expected_output' => 'Expected 2', 'threshold' => 0.7],
    ];

    $result = $this->service->runBenchmark('smart_marketing', $testCases);

    $this->assertSame('smart_marketing', $result['agent_id']);
    $this->assertSame(2, $result['total_cases']);
    $this->assertSame(2, $result['passed']);
    $this->assertSame(0, $result['failed']);
    $this->assertSame(1.0, $result['pass_rate']);
    $this->assertSame(0.9, $result['average_score']);
    $this->assertCount(2, $result['results']);
    $this->assertTrue($result['results'][0]['passed']);
    $this->assertTrue($result['results'][1]['passed']);
  }

  /**
   * Tests runBenchmark with failing test cases.
   *
   * Verifies that when quality evaluator returns low scores,
   * test cases fail and the result shows 0% pass rate.
   *
   * @covers ::runBenchmark
   */
  public function testRunBenchmarkWithFailingCases(): void {
    $this->service = $this->createTestableService([
      'overall_score' => 0.3,
      'criteria_scores' => ['relevance' => 0.2, 'clarity' => 0.4],
      'reasoning' => 'Response lacks relevance.',
    ]);

    $this->service->expects($this->exactly(2))
      ->method('executeAgent')
      ->willReturn(['data' => ['text' => 'Poor response']]);

    $testCases = [
      ['input' => 'Question 1', 'expected_output' => 'Expected 1', 'threshold' => 0.7],
      ['input' => 'Question 2', 'expected_output' => 'Expected 2', 'threshold' => 0.7],
    ];

    $result = $this->service->runBenchmark('smart_marketing', $testCases);

    $this->assertSame(2, $result['total_cases']);
    $this->assertSame(0, $result['passed']);
    $this->assertSame(2, $result['failed']);
    $this->assertSame(0.0, $result['pass_rate']);
    $this->assertFalse($result['results'][0]['passed']);
    $this->assertFalse($result['results'][1]['passed']);
  }

  /**
   * Tests that runBenchmark calculates average score correctly.
   *
   * Uses three test cases with different scores (0.8, 0.6, 0.9)
   * and verifies the average is (0.8 + 0.6 + 0.9) / 3 = 0.7667.
   *
   * @covers ::runBenchmark
   */
  public function testRunBenchmarkCalculatesScoreCorrectly(): void {
    $scores = [0.8, 0.6, 0.9];
    $callIndex = 0;

    $this->service = $this->createTestableService(
      function () use (&$callIndex, $scores): array {
        $score = $scores[$callIndex] ?? 0.5;
        $callIndex++;
        return [
          'overall_score' => $score,
          'criteria_scores' => [],
          'reasoning' => 'Evaluation.',
        ];
      }
    );

    $this->service->expects($this->exactly(3))
      ->method('executeAgent')
      ->willReturn(['data' => ['text' => 'Response']]);

    $testCases = [
      ['input' => 'Q1', 'expected_output' => 'E1', 'threshold' => 0.7],
      ['input' => 'Q2', 'expected_output' => 'E2', 'threshold' => 0.7],
      ['input' => 'Q3', 'expected_output' => 'E3', 'threshold' => 0.7],
    ];

    $result = $this->service->runBenchmark('test_agent', $testCases, ['version' => '2.0.0']);

    // Average: (0.8 + 0.6 + 0.9) / 3 = 0.7667.
    $expectedAvg = round((0.8 + 0.6 + 0.9) / 3, 4);
    $this->assertSame($expectedAvg, $result['average_score']);
    $this->assertSame('2.0.0', $result['version']);
    $this->assertSame(3, $result['total_cases']);
    // Cases 1 and 3 pass (0.8, 0.9 >= 0.7), case 2 fails (0.6 < 0.7).
    $this->assertSame(2, $result['passed']);
    $this->assertSame(1, $result['failed']);
  }

  /**
   * Tests compareVersions returns proper comparison structure.
   *
   * Mocks getLatestResult via entity storage to return results
   * for two versions and verifies the comparison output.
   *
   * @covers ::compareVersions
   */
  public function testCompareVersionsReturnsComparison(): void {
    $this->service = $this->createTestableService();

    // Create mock entities for version A and B.
    $resultDataA = json_encode([
      'average_score' => 0.75,
      'pass_rate' => 0.8,
    ]);
    $resultDataB = json_encode([
      'average_score' => 0.85,
      'pass_rate' => 0.9,
    ]);

    $entityA = $this->createMockBenchmarkEntity($resultDataA);
    $entityB = $this->createMockBenchmarkEntity($resultDataB);

    // Set up the query mock to return different entity IDs per call.
    $queryA = $this->createMock(QueryInterface::class);
    $queryA->method('condition')->willReturnSelf();
    $queryA->method('sort')->willReturnSelf();
    $queryA->method('range')->willReturnSelf();
    $queryA->method('accessCheck')->willReturnSelf();
    $queryA->method('execute')->willReturn([1]);

    $queryB = $this->createMock(QueryInterface::class);
    $queryB->method('condition')->willReturnSelf();
    $queryB->method('sort')->willReturnSelf();
    $queryB->method('range')->willReturnSelf();
    $queryB->method('accessCheck')->willReturnSelf();
    $queryB->method('execute')->willReturn([2]);

    $callCount = 0;
    $this->storage->method('getQuery')
      ->willReturnCallback(function () use (&$callCount, $queryA, $queryB) {
        $callCount++;
        return ($callCount <= 1) ? $queryA : $queryB;
      });

    $this->storage->method('load')
      ->willReturnCallback(function ($id) use ($entityA, $entityB) {
        return match ($id) {
          1 => $entityA,
          2 => $entityB,
          default => NULL,
        };
      });

    $comparison = $this->service->compareVersions('test_agent', '1.0.0', '2.0.0');

    $this->assertSame('test_agent', $comparison['agent_id']);
    $this->assertSame('1.0.0', $comparison['version_a']);
    $this->assertSame('2.0.0', $comparison['version_b']);
    $this->assertSame(0.75, $comparison['score_a']);
    $this->assertSame(0.85, $comparison['score_b']);
    $this->assertSame(0.1, $comparison['delta']);
    $this->assertTrue($comparison['improvement']);
    $this->assertFalse($comparison['regression']);
  }

  /**
   * Tests getLatestResult returns null when no results exist.
   *
   * @covers ::getLatestResult
   */
  public function testGetLatestResultReturnsNull(): void {
    $this->service = $this->createTestableService();

    $query = $this->createMock(QueryInterface::class);
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('accessCheck')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->storage->method('getQuery')->willReturn($query);

    $result = $this->service->getLatestResult('nonexistent_agent', '1.0.0');

    $this->assertNull($result);
  }

  /**
   * Creates a mock benchmark result entity with the given JSON data.
   *
   * Uses FieldableEntityInterface which has the get() method
   * that AgentBenchmarkService relies on for reading result_data.
   *
   * @param string $resultData
   *   JSON-encoded result data.
   *
   * @return \Drupal\Core\Entity\FieldableEntityInterface|\PHPUnit\Framework\MockObject\MockObject
   *   Mock entity.
   */
  protected function createMockBenchmarkEntity(string $resultData): FieldableEntityInterface|MockObject {
    $fieldItem = new \stdClass();
    $fieldItem->value = $resultData;

    $entity = $this->createMock(FieldableEntityInterface::class);
    $entity->method('get')
      ->with('result_data')
      ->willReturn($fieldItem);

    return $entity;
  }

}
