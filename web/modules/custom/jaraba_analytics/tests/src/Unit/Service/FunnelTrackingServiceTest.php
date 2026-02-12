<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_analytics\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Select;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_analytics\Entity\FunnelDefinition;
use Drupal\jaraba_analytics\Service\FunnelTrackingService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for the FunnelTrackingService.
 *
 * @group jaraba_analytics
 * @coversDefaultClass \Drupal\jaraba_analytics\Service\FunnelTrackingService
 */
class FunnelTrackingServiceTest extends TestCase {

  /**
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The mocked database connection.
   *
   * @var \Drupal\Core\Database\Connection|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $database;

  /**
   * The mocked logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * The mocked entity storage for funnel_definition.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $storage;

  /**
   * The service under test.
   *
   * @var \Drupal\jaraba_analytics\Service\FunnelTrackingService
   */
  protected $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->database = $this->createMock(Connection::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->storage = $this->createMock(EntityStorageInterface::class);
    $this->entityTypeManager->method('getStorage')
      ->with('funnel_definition')
      ->willReturn($this->storage);

    $this->service = new FunnelTrackingService(
      $this->entityTypeManager,
      $this->database,
    );
  }

  /**
   * Helper to create a mock FunnelDefinition entity.
   *
   * @param array $options
   *   Overrides: steps, conversion_window, label.
   *
   * @return \Drupal\jaraba_analytics\Entity\FunnelDefinition|\PHPUnit\Framework\MockObject\MockObject
   *   The mocked funnel definition.
   */
  protected function createMockFunnel(array $options = []) {
    $funnel = $this->createMock(FunnelDefinition::class);

    $funnel->method('getSteps')
      ->willReturn($options['steps'] ?? []);
    $funnel->method('getConversionWindow')
      ->willReturn($options['conversion_window'] ?? 72);
    $funnel->method('label')
      ->willReturn($options['label'] ?? 'Test Funnel');

    return $funnel;
  }

  /**
   * Helper to create a mock Select query returning given session IDs.
   *
   * @param array $sessionIds
   *   Session IDs to return from fetchCol().
   *
   * @return \Drupal\Core\Database\Query\Select|\PHPUnit\Framework\MockObject\MockObject
   *   The mocked select query.
   */
  protected function createMockSelectQuery(array $sessionIds = []) {
    $query = $this->createMock(Select::class);
    $query->method('fields')->willReturnSelf();
    $query->method('addField')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('addExpression')->willReturnSelf();
    $query->method('distinct')->willReturnSelf();
    $query->method('groupBy')->willReturnSelf();

    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchCol')->willReturn($sessionIds);

    $query->method('execute')->willReturn($statement);

    return $query;
  }

  /**
   * Tests calculateFunnel returns empty array when funnel has no steps.
   *
   * When the FunnelDefinition entity has an empty steps array,
   * calculateFunnel should return an empty result without querying
   * the database.
   *
   * @covers ::calculateFunnel
   */
  public function testCalculateFunnelReturnsEmptyForNoFunnel(): void {
    $funnel = $this->createMockFunnel([
      'steps' => [],
    ]);

    // The database should never be queried.
    $this->database->expects($this->never())->method('select');

    $result = $this->service->calculateFunnel(
      $funnel,
      1,
      '2025-01-01',
      '2025-01-31'
    );

    $this->assertSame([], $result);
  }

  /**
   * Tests calculateFunnel processes steps sequentially with decreasing counts.
   *
   * A funnel with 3 steps should produce 3 result entries where each
   * subsequent step has fewer (or equal) sessions than the previous,
   * since later steps filter sessions from the prior step.
   *
   * @covers ::calculateFunnel
   */
  public function testCalculateFunnelProcessesStepsSequentially(): void {
    $funnel = $this->createMockFunnel([
      'steps' => [
        ['event_type' => 'page_view', 'label' => 'Visit Page'],
        ['event_type' => 'add_to_cart', 'label' => 'Add to Cart'],
        ['event_type' => 'purchase', 'label' => 'Purchase'],
      ],
      'conversion_window' => 48,
    ]);

    // Step 1: 100 sessions.
    $step1Query = $this->createMockSelectQuery([
      'sess-1', 'sess-2', 'sess-3', 'sess-4', 'sess-5',
      'sess-6', 'sess-7', 'sess-8', 'sess-9', 'sess-10',
    ]);
    // Step 2: 6 sessions (subset of step 1).
    $step2Query = $this->createMockSelectQuery([
      'sess-1', 'sess-2', 'sess-3', 'sess-5', 'sess-7', 'sess-9',
    ]);
    // Step 3: 3 sessions (subset of step 2).
    $step3Query = $this->createMockSelectQuery([
      'sess-1', 'sess-3', 'sess-9',
    ]);

    $queryIndex = 0;
    $this->database->method('select')
      ->willReturnCallback(function () use (&$queryIndex, $step1Query, $step2Query, $step3Query) {
        $queries = [$step1Query, $step2Query, $step3Query];
        $query = $queries[$queryIndex] ?? $step3Query;
        $queryIndex++;
        return $query;
      });

    $result = $this->service->calculateFunnel(
      $funnel,
      1,
      '2025-01-01',
      '2025-01-31'
    );

    $this->assertCount(3, $result);

    // Step 1: first step always has 100% conversion rate.
    $this->assertSame('Visit Page', $result[0]['label']);
    $this->assertSame('page_view', $result[0]['event_type']);
    $this->assertSame(10, $result[0]['entered']);
    $this->assertSame(100.0, $result[0]['conversion_rate']);
    $this->assertSame(0.0, $result[0]['drop_off_rate']);

    // Step 2: 6 out of 10 = 60% conversion.
    $this->assertSame('Add to Cart', $result[1]['label']);
    $this->assertSame(6, $result[1]['entered']);
    $this->assertSame(60.0, $result[1]['conversion_rate']);
    $this->assertSame(40.0, $result[1]['drop_off_rate']);

    // Step 3: 3 out of 6 = 50% conversion.
    $this->assertSame('Purchase', $result[2]['label']);
    $this->assertSame(3, $result[2]['entered']);
    $this->assertSame(50.0, $result[2]['conversion_rate']);
    $this->assertSame(50.0, $result[2]['drop_off_rate']);
  }

  /**
   * Tests getFunnelSummary includes overall conversion rate.
   *
   * The summary should calculate overall_conversion_rate as:
   * (last step converted / first step entered) * 100.
   *
   * @covers ::getFunnelSummary
   */
  public function testGetFunnelSummaryIncludesConversionRates(): void {
    $funnel = $this->createMockFunnel([
      'steps' => [
        ['event_type' => 'page_view', 'label' => 'Visit'],
        ['event_type' => 'purchase', 'label' => 'Buy'],
      ],
      'conversion_window' => 24,
      'label' => 'Sales Funnel',
    ]);

    $this->storage->method('load')
      ->with(5)
      ->willReturn($funnel);

    // Step 1: 20 sessions, Step 2: 4 sessions.
    $step1Sessions = array_map(fn($i) => "sess-$i", range(1, 20));
    $step2Sessions = ['sess-1', 'sess-5', 'sess-10', 'sess-15'];

    $step1Query = $this->createMockSelectQuery($step1Sessions);
    $step2Query = $this->createMockSelectQuery($step2Sessions);

    $queryIndex = 0;
    $this->database->method('select')
      ->willReturnCallback(function () use (&$queryIndex, $step1Query, $step2Query) {
        $queries = [$step1Query, $step2Query];
        $query = $queries[$queryIndex] ?? $step2Query;
        $queryIndex++;
        return $query;
      });

    $summary = $this->service->getFunnelSummary(5, 1, '2025-01-01', '2025-01-31');

    $this->assertSame(5, $summary['funnel_id']);
    $this->assertSame('Sales Funnel', $summary['funnel_name']);
    $this->assertSame(20, $summary['total_entered']);
    $this->assertSame(4, $summary['total_converted']);
    // Overall: 4/20 * 100 = 20.0%.
    $this->assertSame(20.0, $summary['overall_conversion_rate']);
    $this->assertCount(2, $summary['steps']);
    $this->assertSame('2025-01-01', $summary['period']['start']);
    $this->assertSame('2025-01-31', $summary['period']['end']);
  }

  /**
   * Tests getFunnelSummary handles zero entries without division by zero.
   *
   * When the funnel has steps but no sessions match any step (0 entries),
   * the overall_conversion_rate should be 0.0 without triggering a
   * division by zero error.
   *
   * @covers ::getFunnelSummary
   */
  public function testGetFunnelSummaryHandlesZeroEntries(): void {
    $funnel = $this->createMockFunnel([
      'steps' => [
        ['event_type' => 'page_view', 'label' => 'Visit'],
        ['event_type' => 'purchase', 'label' => 'Buy'],
      ],
      'label' => 'Empty Funnel',
    ]);

    $this->storage->method('load')
      ->with(99)
      ->willReturn($funnel);

    // All steps return 0 sessions.
    $emptyQuery = $this->createMockSelectQuery([]);
    $this->database->method('select')
      ->willReturn($emptyQuery);

    $summary = $this->service->getFunnelSummary(99, 1, '2025-01-01', '2025-01-31');

    $this->assertSame(99, $summary['funnel_id']);
    $this->assertSame('Empty Funnel', $summary['funnel_name']);
    $this->assertSame(0, $summary['total_entered']);
    $this->assertSame(0, $summary['total_converted']);
    $this->assertSame(0.0, $summary['overall_conversion_rate']);
    $this->assertCount(2, $summary['steps']);
  }

  /**
   * Tests getFunnelSummary returns error when funnel entity is not found.
   *
   * @covers ::getFunnelSummary
   */
  public function testGetFunnelSummaryReturnsErrorForMissingFunnel(): void {
    $this->storage->method('load')
      ->with(999)
      ->willReturn(NULL);

    $result = $this->service->getFunnelSummary(999, 1, '2025-01-01', '2025-01-31');

    $this->assertArrayHasKey('error', $result);
    $this->assertSame('Funnel definition not found.', $result['error']);
  }

  /**
   * Tests getFunnelSummary returns empty steps when funnel has no steps.
   *
   * @covers ::getFunnelSummary
   */
  public function testGetFunnelSummaryEmptyStepsReturnsZeroMetrics(): void {
    $funnel = $this->createMockFunnel([
      'steps' => [],
      'label' => 'No Steps Funnel',
    ]);

    $this->storage->method('load')
      ->with(50)
      ->willReturn($funnel);

    $summary = $this->service->getFunnelSummary(50, 1, '2025-03-01', '2025-03-31');

    $this->assertSame(50, $summary['funnel_id']);
    $this->assertSame(0, $summary['total_entered']);
    $this->assertSame(0, $summary['total_converted']);
    $this->assertSame(0.0, $summary['overall_conversion_rate']);
    $this->assertSame([], $summary['steps']);
  }

  /**
   * Tests calculateFunnel step-to-step conversion with single step funnel.
   *
   * A single-step funnel should always show 100% conversion rate.
   *
   * @covers ::calculateFunnel
   */
  public function testCalculateFunnelSingleStep(): void {
    $funnel = $this->createMockFunnel([
      'steps' => [
        ['event_type' => 'signup', 'label' => 'Sign Up'],
      ],
    ]);

    $signupQuery = $this->createMockSelectQuery(['sess-a', 'sess-b', 'sess-c']);
    $this->database->method('select')
      ->willReturn($signupQuery);

    $result = $this->service->calculateFunnel($funnel, 1, '2025-01-01', '2025-06-30');

    $this->assertCount(1, $result);
    $this->assertSame('Sign Up', $result[0]['label']);
    $this->assertSame(3, $result[0]['entered']);
    $this->assertSame(100.0, $result[0]['conversion_rate']);
    $this->assertSame(0.0, $result[0]['drop_off_rate']);
  }

}
