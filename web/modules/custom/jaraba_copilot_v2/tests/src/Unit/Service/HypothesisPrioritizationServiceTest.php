<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_copilot_v2\Unit\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_copilot_v2\Service\HypothesisPrioritizationService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for the HypothesisPrioritizationService.
 *
 * @covers \Drupal\jaraba_copilot_v2\Service\HypothesisPrioritizationService
 * @group jaraba_copilot_v2
 */
class HypothesisPrioritizationServiceTest extends UnitTestCase {

  /**
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The mocked logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected LoggerInterface $logger;

  /**
   * The service under test.
   *
   * @var \Drupal\jaraba_copilot_v2\Service\HypothesisPrioritizationService
   */
  protected HypothesisPrioritizationService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new HypothesisPrioritizationService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * Tests that the service can be instantiated.
   */
  public function testServiceExists(): void {
    $this->assertInstanceOf(HypothesisPrioritizationService::class, $this->service);
  }

  /**
   * Tests ICE score calculation with standard values.
   *
   * The service uses the formula: importance * (6 - confidence) * evidence.
   * With importance=5, confidence=1 (urgency=5), evidence=3: 5 * 5 * 3 = 75.
   */
  public function testCalculateIceScore(): void {
    // importance=5, confidence=1 -> urgency = 6-1 = 5, evidence=3
    // ICE = 5 * 5 * 3 = 75
    $result = $this->service->calculateIceScore(5, 1, 3);
    $this->assertSame(75.0, $result);
  }

  /**
   * Tests ICE score when values are at their minimum clamped boundary.
   *
   * Values below 1 are clamped to 1, so passing 0 results in
   * importance=1, confidence=1 (urgency=5), evidence=1: 1 * 5 * 1 = 5.
   */
  public function testCalculateIceScoreWithZeros(): void {
    // Values are clamped: max(1, min(5, value))
    // importance=1 (clamped from 0), confidence=1 (urgency=5), evidence=1 (clamped from 0)
    // ICE = 1 * 5 * 1 = 5
    $result = $this->service->calculateIceScore(0, 0, 0);
    $this->assertSame(5.0, $result);
  }

  /**
   * Tests ICE score with maximum allowed values.
   *
   * With importance=5, confidence=5 (urgency=1), evidence=5: 5 * 1 * 5 = 25.
   * Maximum urgency scenario: importance=5, confidence=1 (urgency=5), evidence=5: 5 * 5 * 5 = 125.
   */
  public function testCalculateIceScoreWithMaxValues(): void {
    // All at max: importance=5, confidence=5 (urgency=6-5=1), evidence=5
    // ICE = 5 * 1 * 5 = 25
    $result = $this->service->calculateIceScore(5, 5, 5);
    $this->assertSame(25.0, $result);

    // Maximum urgency scenario: confidence=1 (urgency=5)
    // ICE = 5 * 5 * 5 = 125
    $maxUrgency = $this->service->calculateIceScore(5, 1, 5);
    $this->assertSame(125.0, $maxUrgency);
  }

  /**
   * Tests that values exceeding boundaries are clamped to 1-5.
   *
   * Importance, confidence, and evidence are each clamped to [1,5].
   */
  public function testCalculateIceScoreClampsBoundaries(): void {
    // Values above 5 are clamped to 5
    // importance=5 (clamped from 10), confidence=5 (urgency=1), evidence=5 (clamped from 10)
    // ICE = 5 * 1 * 5 = 25
    $result = $this->service->calculateIceScore(10, 10, 10);
    $this->assertSame(25.0, $result);

    // Negative values are clamped to 1
    // importance=1, confidence=1 (urgency=5), evidence=1
    // ICE = 1 * 5 * 1 = 5
    $negativeResult = $this->service->calculateIceScore(-1, -5, -3);
    $this->assertSame(5.0, $negativeResult);
  }

  /**
   * Tests that the return type of calculateIceScore is float.
   */
  public function testCalculateIceScoreReturnsFloat(): void {
    $result = $this->service->calculateIceScore(3, 2, 4);
    $this->assertIsFloat($result);
  }

  /**
   * Tests that higher importance increases the ICE score.
   */
  public function testHigherImportanceIncreasesScore(): void {
    $lowImportance = $this->service->calculateIceScore(1, 3, 3);
    $highImportance = $this->service->calculateIceScore(5, 3, 3);

    $this->assertGreaterThan($lowImportance, $highImportance);
  }

  /**
   * Tests that lower confidence increases the ICE score (higher urgency).
   */
  public function testLowerConfidenceIncreasesScore(): void {
    $highConfidence = $this->service->calculateIceScore(3, 5, 3);
    $lowConfidence = $this->service->calculateIceScore(3, 1, 3);

    $this->assertGreaterThan($highConfidence, $lowConfidence);
  }

}
