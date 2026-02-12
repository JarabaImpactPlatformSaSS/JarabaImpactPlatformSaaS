<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_security_compliance\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\jaraba_security_compliance\Entity\ComplianceAssessment;
use Drupal\jaraba_security_compliance\Service\ComplianceTrackerService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for ComplianceTrackerService.
 *
 * @group jaraba_security_compliance
 * @coversDefaultClass \Drupal\jaraba_security_compliance\Service\ComplianceTrackerService
 */
class ComplianceTrackerServiceTest extends TestCase {

  /**
   * The service under test.
   */
  protected ComplianceTrackerService $service;

  /**
   * Mocked entity type manager.
   */
  protected EntityTypeManagerInterface&MockObject $entityTypeManager;

  /**
   * Mocked state service.
   */
  protected StateInterface&MockObject $state;

  /**
   * Mocked logger.
   */
  protected LoggerInterface&MockObject $logger;

  /**
   * Mocked entity storage.
   */
  protected EntityStorageInterface&MockObject $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->state = $this->createMock(StateInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->storage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('compliance_assessment_v2')
      ->willReturn($this->storage);

    $this->service = new ComplianceTrackerService(
      $this->entityTypeManager,
      $this->state,
      $this->logger,
    );
  }

  /**
   * Tests getComplianceStatus returns data for all frameworks.
   *
   * @covers ::getComplianceStatus
   */
  public function testGetComplianceStatusReturnsAllFrameworks(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->storage->method('getQuery')->willReturn($query);

    $result = $this->service->getComplianceStatus();

    $this->assertArrayHasKey('soc2', $result);
    $this->assertArrayHasKey('iso27001', $result);
    $this->assertArrayHasKey('ens', $result);
    $this->assertArrayHasKey('gdpr', $result);
  }

  /**
   * Tests getComplianceStatus calculates correct scores.
   *
   * @covers ::getComplianceStatus
   */
  public function testGetComplianceStatusCalculatesScores(): void {
    // Create mock assessments: 3 pass, 1 fail, 1 warning.
    $passingAssessment = $this->createMock(ComplianceAssessment::class);
    $passingAssessment->method('getAssessmentStatus')->willReturn('pass');

    $failingAssessment = $this->createMock(ComplianceAssessment::class);
    $failingAssessment->method('getAssessmentStatus')->willReturn('fail');

    $warningAssessment = $this->createMock(ComplianceAssessment::class);
    $warningAssessment->method('getAssessmentStatus')->willReturn('warning');

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();

    $callCount = 0;
    $query->method('execute')->willReturnCallback(function () use (&$callCount) {
      $callCount++;
      // First framework (soc2) has assessments, rest empty.
      if ($callCount === 1) {
        return [1, 2, 3, 4, 5];
      }
      return [];
    });

    $this->storage->method('getQuery')->willReturn($query);

    $loadCount = 0;
    $this->storage->method('loadMultiple')->willReturnCallback(
      function ($ids) use (&$loadCount, $passingAssessment, $failingAssessment, $warningAssessment) {
        $loadCount++;
        if ($loadCount === 1) {
          return [
            1 => $passingAssessment,
            2 => $passingAssessment,
            3 => $passingAssessment,
            4 => $failingAssessment,
            5 => $warningAssessment,
          ];
        }
        return [];
      }
    );

    $result = $this->service->getComplianceStatus();

    // SOC2 should have 5 controls, 3 passing = 60% score.
    $this->assertEquals(5, $result['soc2']['total_controls']);
    $this->assertEquals(3, $result['soc2']['passing']);
    $this->assertEquals(1, $result['soc2']['failing']);
    $this->assertEquals(1, $result['soc2']['warnings']);
    $this->assertEquals(60, $result['soc2']['score']);
  }

  /**
   * Tests getComplianceScore returns zero when no assessments exist.
   *
   * @covers ::getComplianceScore
   */
  public function testGetComplianceScoreReturnsZeroWhenEmpty(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->storage->method('getQuery')->willReturn($query);

    $score = $this->service->getComplianceScore();
    $this->assertEquals(0, $score);
  }

  /**
   * Tests runAssessment returns correct structure.
   *
   * @covers ::runAssessment
   */
  public function testRunAssessmentReturnsCorrectStructure(): void {
    $assessment = $this->createMock(ComplianceAssessment::class);
    $assessment->method('getAssessmentStatus')->willReturn('pass');
    $assessment->method('getControlId')->willReturn('SOC2-CC6.1');
    $assessment->method('getControlName')->willReturn('Access Controls');

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([1]);

    $this->storage->method('getQuery')->willReturn($query);
    $this->storage->method('loadMultiple')
      ->with([1])
      ->willReturn([1 => $assessment]);

    $this->state->expects($this->atLeastOnce())->method('set');

    $result = $this->service->runAssessment('soc2');

    $this->assertEquals('soc2', $result['framework']);
    $this->assertEquals(1, $result['controls_evaluated']);
    $this->assertEquals(100, $result['score']);
    $this->assertNotEmpty($result['details']);
    $this->assertEquals('SOC2-CC6.1', $result['details'][0]['control_id']);
  }

  /**
   * Tests getComplianceStatus handles exceptions gracefully.
   *
   * @covers ::getComplianceStatus
   */
  public function testGetComplianceStatusHandlesExceptions(): void {
    $this->storage->method('getQuery')
      ->willThrowException(new \RuntimeException('Database error'));

    $this->logger->expects($this->once())
      ->method('error');

    $result = $this->service->getComplianceStatus();
    $this->assertEmpty($result);
  }

  /**
   * Tests runAssessment handles empty assessments.
   *
   * @covers ::runAssessment
   */
  public function testRunAssessmentWithNoAssessments(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->storage->method('getQuery')->willReturn($query);

    $this->state->expects($this->atLeastOnce())->method('set');

    $result = $this->service->runAssessment('soc2');

    $this->assertEquals(0, $result['controls_evaluated']);
    $this->assertEquals(0, $result['score']);
    $this->assertEmpty($result['details']);
  }

}
