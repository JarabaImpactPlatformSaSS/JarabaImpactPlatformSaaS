<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\JarabaLexHealthScoreService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for JarabaLexHealthScoreService.
 *
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\JarabaLexHealthScoreService
 * @group ecosistema_jaraba_core
 */
class JarabaLexHealthScoreServiceTest extends UnitTestCase {

  /**
   * The service under test.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\JarabaLexHealthScoreService
   */
  protected JarabaLexHealthScoreService $service;

  /**
   * Mock entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * Mock database connection.
   *
   * @var \Drupal\Core\Database\Connection|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $database;

  /**
   * Mock logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->database = $this->createMock(Connection::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new JarabaLexHealthScoreService(
      $this->entityTypeManager,
      $this->database,
      $this->logger,
    );
  }

  /**
   * Tests that DIMENSIONS constant defines exactly 5 dimensions.
   *
   * @covers ::__construct
   */
  public function testDimensionsCountIsFive(): void {
    $reflection = new \ReflectionClass(JarabaLexHealthScoreService::class);
    $dimensions = $reflection->getConstant('DIMENSIONS');

    $this->assertCount(5, $dimensions);
  }

  /**
   * Tests that all 5 scoring dimension keys are present.
   *
   * @covers ::__construct
   */
  public function testDimensionKeysAreComplete(): void {
    $expectedKeys = [
      'search_activity',
      'bookmark_engagement',
      'alert_utilization',
      'copilot_engagement',
      'citation_workflow',
    ];

    $reflection = new \ReflectionClass(JarabaLexHealthScoreService::class);
    $dimensions = $reflection->getConstant('DIMENSIONS');

    foreach ($expectedKeys as $key) {
      $this->assertArrayHasKey($key, $dimensions, "Dimension '$key' must exist.");
    }
  }

  /**
   * Tests that dimension weights match the specification exactly.
   *
   * @covers ::__construct
   */
  public function testDimensionWeightsMatchSpecification(): void {
    $expectedWeights = [
      'search_activity' => 0.30,
      'bookmark_engagement' => 0.20,
      'alert_utilization' => 0.20,
      'copilot_engagement' => 0.15,
      'citation_workflow' => 0.15,
    ];

    $reflection = new \ReflectionClass(JarabaLexHealthScoreService::class);
    $dimensions = $reflection->getConstant('DIMENSIONS');

    foreach ($expectedWeights as $key => $expectedWeight) {
      $this->assertSame(
        $expectedWeight,
        $dimensions[$key]['weight'],
        "Dimension '$key' weight must be $expectedWeight.",
      );
    }
  }

  /**
   * Tests that dimension weights sum to 1.0.
   *
   * @covers ::__construct
   */
  public function testDimensionWeightsSumToOne(): void {
    $reflection = new \ReflectionClass(JarabaLexHealthScoreService::class);
    $dimensions = $reflection->getConstant('DIMENSIONS');

    $totalWeight = 0.0;
    foreach ($dimensions as $dimension) {
      $totalWeight += $dimension['weight'];
    }

    $this->assertEqualsWithDelta(1.0, $totalWeight, 0.001, 'Dimension weights must sum to 1.0.');
  }

  /**
   * Tests that each dimension has a weight and label.
   *
   * @covers ::__construct
   */
  public function testDimensionsHaveRequiredFields(): void {
    $reflection = new \ReflectionClass(JarabaLexHealthScoreService::class);
    $dimensions = $reflection->getConstant('DIMENSIONS');

    foreach ($dimensions as $key => $dimension) {
      $this->assertArrayHasKey('weight', $dimension, "Dimension '$key' must have 'weight'.");
      $this->assertArrayHasKey('label', $dimension, "Dimension '$key' must have 'label'.");
      $this->assertIsFloat($dimension['weight'], "Dimension '$key' weight must be float.");
      $this->assertIsString($dimension['label'], "Dimension '$key' label must be string.");
    }
  }

  /**
   * Tests score categorization: healthy (76-100).
   *
   * @covers ::calculateUserHealth
   */
  public function testScoreCategoryHealthy(): void {
    $method = new \ReflectionMethod(JarabaLexHealthScoreService::class, 'categorize');
    $method->setAccessible(TRUE);

    $this->assertSame('healthy', $method->invoke($this->service, 76));
    $this->assertSame('healthy', $method->invoke($this->service, 100));
    $this->assertSame('healthy', $method->invoke($this->service, 85));
  }

  /**
   * Tests score categorization: neutral (51-75).
   *
   * @covers ::calculateUserHealth
   */
  public function testScoreCategoryNeutral(): void {
    $method = new \ReflectionMethod(JarabaLexHealthScoreService::class, 'categorize');
    $method->setAccessible(TRUE);

    $this->assertSame('neutral', $method->invoke($this->service, 51));
    $this->assertSame('neutral', $method->invoke($this->service, 75));
    $this->assertSame('neutral', $method->invoke($this->service, 60));
  }

  /**
   * Tests score categorization: at_risk (26-50).
   *
   * @covers ::calculateUserHealth
   */
  public function testScoreCategoryAtRisk(): void {
    $method = new \ReflectionMethod(JarabaLexHealthScoreService::class, 'categorize');
    $method->setAccessible(TRUE);

    $this->assertSame('at_risk', $method->invoke($this->service, 26));
    $this->assertSame('at_risk', $method->invoke($this->service, 50));
    $this->assertSame('at_risk', $method->invoke($this->service, 35));
  }

  /**
   * Tests score categorization: critical (0-25).
   *
   * @covers ::calculateUserHealth
   */
  public function testScoreCategoryCritical(): void {
    $method = new \ReflectionMethod(JarabaLexHealthScoreService::class, 'categorize');
    $method->setAccessible(TRUE);

    $this->assertSame('critical', $method->invoke($this->service, 0));
    $this->assertSame('critical', $method->invoke($this->service, 25));
    $this->assertSame('critical', $method->invoke($this->service, 10));
  }

  /**
   * Tests KPI targets are correctly defined with expected count.
   *
   * @covers ::calculateVerticalKpis
   */
  public function testKpiTargetsAreCorrectlyDefined(): void {
    $reflection = new \ReflectionClass(JarabaLexHealthScoreService::class);
    $kpiTargets = $reflection->getConstant('KPI_TARGETS');

    $this->assertCount(8, $kpiTargets);

    $expectedKpis = [
      'searches_per_user_month' => 20,
      'bookmarks_per_user' => 10,
      'alerts_per_user' => 3,
      'citations_per_user' => 5,
      'copilot_sessions_month' => 8,
      'conversion_free_paid' => 12,
      'user_retention_30d' => 65,
      'nps_score' => 45,
    ];

    foreach ($expectedKpis as $key => $expectedValue) {
      $this->assertArrayHasKey($key, $kpiTargets, "KPI '$key' must exist.");
      $this->assertSame(
        $expectedValue,
        $kpiTargets[$key],
        "KPI '$key' target must be $expectedValue.",
      );
    }
  }

  /**
   * Tests calculateVerticalKpis returns all 8 KPIs.
   *
   * @covers ::calculateVerticalKpis
   */
  public function testCalculateVerticalKpisReturnsAllKpis(): void {
    // The database select for searches_per_user_month may throw.
    // We mock it to return a count.
    $statement = $this->createMock(\Drupal\Core\Database\StatementInterface::class);
    $statement->method('fetchField')->willReturn(0);

    $countQuery = $this->createMock(\Drupal\Core\Database\Query\Select::class);
    $countQuery->method('execute')->willReturn($statement);

    $select = $this->createMock(\Drupal\Core\Database\Query\Select::class);
    $select->method('condition')->willReturnSelf();
    $select->method('countQuery')->willReturn($countQuery);

    $this->database->method('select')
      ->with('jarabalex_feature_usage', 'u')
      ->willReturn($select);

    $kpis = $this->service->calculateVerticalKpis();

    $expectedKpiKeys = [
      'searches_per_user_month',
      'bookmarks_per_user',
      'alerts_per_user',
      'citations_per_user',
      'copilot_sessions_month',
      'conversion_free_paid',
      'user_retention_30d',
      'nps_score',
    ];

    foreach ($expectedKpiKeys as $key) {
      $this->assertArrayHasKey($key, $kpis, "KPI '$key' must be returned.");
      $this->assertArrayHasKey('value', $kpis[$key]);
      $this->assertArrayHasKey('target', $kpis[$key]);
      $this->assertArrayHasKey('status', $kpis[$key]);
      $this->assertArrayHasKey('label', $kpis[$key]);
      $this->assertArrayHasKey('unit', $kpis[$key]);
    }
  }

  /**
   * Tests the KPI status values are within expected set.
   *
   * @covers ::calculateVerticalKpis
   */
  public function testKpiStatusValuesAreValid(): void {
    $statement = $this->createMock(\Drupal\Core\Database\StatementInterface::class);
    $statement->method('fetchField')->willReturn(0);

    $countQuery = $this->createMock(\Drupal\Core\Database\Query\Select::class);
    $countQuery->method('execute')->willReturn($statement);

    $select = $this->createMock(\Drupal\Core\Database\Query\Select::class);
    $select->method('condition')->willReturnSelf();
    $select->method('countQuery')->willReturn($countQuery);

    $this->database->method('select')
      ->with('jarabalex_feature_usage', 'u')
      ->willReturn($select);

    $validStatuses = ['on_track', 'behind', 'critical'];
    $kpis = $this->service->calculateVerticalKpis();

    foreach ($kpis as $key => $kpi) {
      $this->assertContains(
        $kpi['status'],
        $validStatuses,
        "KPI '$key' status '{$kpi['status']}' is not valid.",
      );
    }
  }

  /**
   * Tests calculateUserHealth returns expected structure.
   *
   * Without a Drupal container, all dimension calculations return 0,
   * so the overall score should be 0 and category 'critical'.
   *
   * @covers ::calculateUserHealth
   */
  public function testCalculateUserHealthReturnsExpectedStructure(): void {
    // Without Drupal container, all dimension calculators catch exceptions
    // and return 0. The entityTypeManager storages will fail gracefully.
    $storage = $this->createMock(\Drupal\Core\Entity\EntityStorageInterface::class);
    $storage->method('loadByProperties')->willReturn([]);

    $this->entityTypeManager->method('getStorage')
      ->willReturn($storage);

    $result = $this->service->calculateUserHealth(1);

    $this->assertArrayHasKey('user_id', $result);
    $this->assertArrayHasKey('overall_score', $result);
    $this->assertArrayHasKey('category', $result);
    $this->assertArrayHasKey('dimensions', $result);
    $this->assertSame(1, $result['user_id']);
    $this->assertIsInt($result['overall_score']);
    $this->assertIsString($result['category']);
    $this->assertCount(5, $result['dimensions']);
  }

  /**
   * Tests dimension output structure in calculateUserHealth.
   *
   * @covers ::calculateUserHealth
   */
  public function testDimensionOutputStructure(): void {
    $storage = $this->createMock(\Drupal\Core\Entity\EntityStorageInterface::class);
    $storage->method('loadByProperties')->willReturn([]);

    $this->entityTypeManager->method('getStorage')
      ->willReturn($storage);

    $result = $this->service->calculateUserHealth(1);

    foreach ($result['dimensions'] as $key => $dimension) {
      $this->assertArrayHasKey('score', $dimension, "Dimension '$key' must have 'score'.");
      $this->assertArrayHasKey('weight', $dimension, "Dimension '$key' must have 'weight'.");
      $this->assertArrayHasKey('weighted_score', $dimension, "Dimension '$key' must have 'weighted_score'.");
      $this->assertArrayHasKey('label', $dimension, "Dimension '$key' must have 'label'.");
    }
  }

}
