<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal_intelligence\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\jaraba_legal_intelligence\Service\LegalMergeRankService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for LegalMergeRankService.
 *
 * @coversDefaultClass \Drupal\jaraba_legal_intelligence\Service\LegalMergeRankService
 * @group jaraba_legal_intelligence
 */
class LegalMergeRankServiceTest extends UnitTestCase {

  /**
   * The service being tested.
   *
   * @var \Drupal\jaraba_legal_intelligence\Service\LegalMergeRankService
   */
  protected LegalMergeRankService $service;

  /**
   * Mock config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $configFactory;

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

    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturnMap([
      ['eu_primacy_boost', 0.05],
      ['freshness_boost', 0.02],
      ['freshness_days', 365],
    ]);
    $this->configFactory->method('get')
      ->with('jaraba_legal_intelligence.settings')
      ->willReturn($config);

    $this->service = new LegalMergeRankService($this->configFactory, $this->logger);
  }

  /**
   * Builds a standard result array for testing.
   *
   * @param int $id
   *   Result ID.
   * @param string $sourceId
   *   Source identifier (cendoj, tjue, eurlex, etc.).
   * @param float $score
   *   Base score for the result.
   * @param string $dateIssued
   *   Date issued in YYYY-MM-DD format.
   * @param int $importanceLevel
   *   Importance level (1=key, 2=medium, 3=low).
   *
   * @return array
   *   A standard result array.
   */
  private function buildResult(int $id, string $sourceId, float $score, string $dateIssued = '2024-01-15', int $importanceLevel = 3): array {
    return [
      'id' => $id,
      'title' => 'Test Resolution ' . $id,
      'source_id' => $sourceId,
      'external_ref' => 'REF-' . $id,
      'resolution_type' => 'sentencia',
      'date_issued' => $dateIssued,
      'importance_level' => $importanceLevel,
      'is_eu' => in_array($sourceId, ['tjue', 'eurlex', 'tedh', 'edpb', 'eba', 'esma', 'ag_tjue']),
      'score' => $score,
    ];
  }

  /**
   * @covers ::mergeAndRank
   */
  public function testMergeAndRankEmptyArrays(): void {
    $result = $this->service->mergeAndRank([], [], 'all');
    $this->assertSame([], $result);
  }

  /**
   * @covers ::mergeAndRank
   */
  public function testMergeAndRankNationalScope(): void {
    $national = [
      $this->buildResult(1, 'cendoj', 0.85),
      $this->buildResult(2, 'boe', 0.80),
    ];
    $eu = [
      $this->buildResult(3, 'tjue', 0.90),
    ];

    $result = $this->service->mergeAndRank($national, $eu, 'national');

    // Only national results should appear.
    $resultIds = array_column($result, 'id');
    $this->assertContains(1, $resultIds);
    $this->assertContains(2, $resultIds);
    $this->assertNotContains(3, $resultIds);
    $this->assertCount(2, $result);
  }

  /**
   * @covers ::mergeAndRank
   */
  public function testMergeAndRankEuScope(): void {
    $national = [
      $this->buildResult(1, 'cendoj', 0.85),
    ];
    $eu = [
      $this->buildResult(2, 'tjue', 0.90),
      $this->buildResult(3, 'eurlex', 0.75),
    ];

    $result = $this->service->mergeAndRank($national, $eu, 'eu');

    // Only EU results should appear.
    $resultIds = array_column($result, 'id');
    $this->assertNotContains(1, $resultIds);
    $this->assertContains(2, $resultIds);
    $this->assertContains(3, $resultIds);
    $this->assertCount(2, $result);
  }

  /**
   * @covers ::mergeAndRank
   */
  public function testMergeAndRankAllScope(): void {
    $national = [
      $this->buildResult(1, 'cendoj', 0.85),
    ];
    $eu = [
      $this->buildResult(2, 'tjue', 0.90),
    ];

    $result = $this->service->mergeAndRank($national, $eu, 'all');

    // Both sets should be merged.
    $resultIds = array_column($result, 'id');
    $this->assertContains(1, $resultIds);
    $this->assertContains(2, $resultIds);
    $this->assertCount(2, $result);
  }

  /**
   * @covers ::mergeAndRank
   */
  public function testEuPrimacyBoostApplied(): void {
    $eu = [
      $this->buildResult(1, 'tjue', 0.80),
    ];

    $result = $this->service->mergeAndRank([], $eu, 'all');

    // TJUE result should get +0.05 EU primacy boost.
    // Base 0.80 + 0.05 (EU) + 0.00 (no freshness, old date) + 0.00 (importance 3) = 0.85.
    $this->assertCount(1, $result);
    $this->assertGreaterThan(0.80, $result[0]['score']);
    $this->assertEquals(0.85, $result[0]['score']);
  }

  /**
   * @covers ::mergeAndRank
   */
  public function testEuPrimacyBoostNotAppliedToNational(): void {
    $national = [
      $this->buildResult(1, 'cendoj', 0.80),
    ];

    $result = $this->service->mergeAndRank($national, [], 'all');

    // Cendoj result should NOT get EU primacy boost.
    // Base 0.80 + 0.00 (no EU) + 0.00 (no freshness, old date) + 0.00 (importance 3) = 0.80.
    $this->assertCount(1, $result);
    $this->assertEquals(0.80, $result[0]['score']);
  }

  /**
   * @covers ::mergeAndRank
   */
  public function testFreshnessBoostApplied(): void {
    $today = date('Y-m-d');
    $national = [
      $this->buildResult(1, 'cendoj', 0.80, $today),
    ];

    $result = $this->service->mergeAndRank($national, [], 'all');

    // Result with today's date should get +0.02 freshness boost.
    // Base 0.80 + 0.00 (no EU) + 0.02 (fresh) + 0.00 (importance 3) = 0.82.
    $this->assertCount(1, $result);
    $this->assertEquals(0.82, $result[0]['score']);
  }

  /**
   * @covers ::mergeAndRank
   */
  public function testFreshnessBoostNotAppliedToOldResult(): void {
    // Date 2 years ago (well beyond 365 days).
    $oldDate = date('Y-m-d', strtotime('-730 days'));
    $national = [
      $this->buildResult(1, 'cendoj', 0.80, $oldDate),
    ];

    $result = $this->service->mergeAndRank($national, [], 'all');

    // Old result should NOT get freshness boost.
    // Base 0.80 + 0.00 (no EU) + 0.00 (too old) + 0.00 (importance 3) = 0.80.
    $this->assertCount(1, $result);
    $this->assertEquals(0.80, $result[0]['score']);
  }

  /**
   * @covers ::mergeAndRank
   */
  public function testImportanceBoostLevel1(): void {
    $national = [
      $this->buildResult(1, 'cendoj', 0.80, '2024-01-15', 1),
    ];

    $result = $this->service->mergeAndRank($national, [], 'all');

    // Importance level 1 should get +0.03.
    // Base 0.80 + 0.00 (no EU) + 0.00 (no freshness) + 0.03 (importance 1) = 0.83.
    $this->assertCount(1, $result);
    $this->assertEquals(0.83, $result[0]['score']);
  }

  /**
   * @covers ::mergeAndRank
   */
  public function testImportanceBoostLevel2(): void {
    $national = [
      $this->buildResult(1, 'cendoj', 0.80, '2024-01-15', 2),
    ];

    $result = $this->service->mergeAndRank($national, [], 'all');

    // Importance level 2 should get +0.01.
    // Base 0.80 + 0.00 (no EU) + 0.00 (no freshness) + 0.01 (importance 2) = 0.81.
    $this->assertCount(1, $result);
    $this->assertEquals(0.81, $result[0]['score']);
  }

  /**
   * @covers ::mergeAndRank
   */
  public function testImportanceBoostLevel3(): void {
    $national = [
      $this->buildResult(1, 'cendoj', 0.80, '2024-01-15', 3),
    ];

    $result = $this->service->mergeAndRank($national, [], 'all');

    // Importance level 3 should get +0.00.
    // Base 0.80 + 0.00 (no EU) + 0.00 (no freshness) + 0.00 (importance 3) = 0.80.
    $this->assertCount(1, $result);
    $this->assertEquals(0.80, $result[0]['score']);
  }

  /**
   * @covers ::mergeAndRank
   */
  public function testScoreCappedAt1(): void {
    $today = date('Y-m-d');
    // High base score + all boosts should still cap at 1.0.
    $eu = [
      $this->buildResult(1, 'tjue', 0.98, $today, 1),
    ];

    $result = $this->service->mergeAndRank([], $eu, 'all');

    // Base 0.98 + 0.05 (EU) + 0.02 (fresh) + 0.03 (importance 1) = 1.08 -> capped to 1.0.
    $this->assertCount(1, $result);
    $this->assertLessThanOrEqual(1.0, $result[0]['score']);
    $this->assertEquals(1.0, $result[0]['score']);
  }

  /**
   * @covers ::mergeAndRank
   */
  public function testDeduplicationKeepsHigherScore(): void {
    // Same ID in both sets with different scores.
    $national = [
      $this->buildResult(1, 'cendoj', 0.70),
    ];
    $eu = [
      $this->buildResult(1, 'tjue', 0.90),
    ];

    $result = $this->service->mergeAndRank($national, $eu, 'all');

    // Deduplication should keep the higher score (tjue version: 0.90 + 0.05 EU = 0.95).
    $this->assertCount(1, $result);
    $this->assertEquals(1, $result[0]['id']);
    // The tjue version (0.90 + 0.05 EU) is higher than the cendoj version (0.70).
    $this->assertGreaterThan(0.70, $result[0]['score']);
  }

  /**
   * @covers ::mergeAndRank
   */
  public function testResultsSortedByScoreDescending(): void {
    $national = [
      $this->buildResult(1, 'cendoj', 0.60),
      $this->buildResult(2, 'boe', 0.90),
      $this->buildResult(3, 'dgt', 0.75),
    ];

    $result = $this->service->mergeAndRank($national, [], 'all');

    $this->assertCount(3, $result);
    // Results should be sorted by score descending.
    $scores = array_column($result, 'score');
    for ($i = 0; $i < count($scores) - 1; $i++) {
      $this->assertGreaterThanOrEqual($scores[$i + 1], $scores[$i],
        'Results should be sorted by score descending.');
    }
  }

  /**
   * @covers ::applyBoosts
   */
  public function testApplyBoostsEmptyArray(): void {
    $result = $this->service->applyBoosts([]);
    $this->assertSame([], $result);
  }

}
