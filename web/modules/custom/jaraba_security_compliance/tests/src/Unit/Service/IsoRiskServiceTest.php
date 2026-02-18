<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_security_compliance\Unit\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_security_compliance\Entity\RiskAssessment;
use Drupal\jaraba_security_compliance\Service\IsoRiskService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for IsoRiskService.
 *
 * @group jaraba_security_compliance
 * @coversDefaultClass \Drupal\jaraba_security_compliance\Service\IsoRiskService
 */
class IsoRiskServiceTest extends TestCase {

  /**
   * The service under test.
   */
  protected IsoRiskService $service;

  /**
   * Mocked entity type manager.
   */
  protected EntityTypeManagerInterface&MockObject $entityTypeManager;

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
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->storage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('risk_assessment')
      ->willReturn($this->storage);

    $this->service = new IsoRiskService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * Tests calculateRiskScore returns correct score and level.
   *
   * @covers ::calculateRiskScore
   */
  public function testCalculateRiskScore(): void {
    // Low risk: 1 * 2 = 2.
    $result = $this->service->calculateRiskScore(1, 2);
    $this->assertEquals(2, $result['score']);
    $this->assertEquals('low', $result['level']);

    // Medium risk: 3 * 2 = 6.
    $result = $this->service->calculateRiskScore(3, 2);
    $this->assertEquals(6, $result['score']);
    $this->assertEquals('medium', $result['level']);

    // High risk: 3 * 4 = 12.
    $result = $this->service->calculateRiskScore(3, 4);
    $this->assertEquals(12, $result['score']);
    $this->assertEquals('high', $result['level']);

    // Critical risk: 3 * 5 = 15.
    $result = $this->service->calculateRiskScore(3, 5);
    $this->assertEquals(15, $result['score']);
    $this->assertEquals('critical', $result['level']);

    // Critical risk: 5 * 5 = 25.
    $result = $this->service->calculateRiskScore(5, 5);
    $this->assertEquals(25, $result['score']);
    $this->assertEquals('critical', $result['level']);
  }

  /**
   * Tests calculateRiskScore clamps values to 1-5 range.
   *
   * @covers ::calculateRiskScore
   */
  public function testCalculateRiskScoreClampsValues(): void {
    // Below minimum.
    $result = $this->service->calculateRiskScore(0, 0);
    $this->assertEquals(1, $result['score']);
    $this->assertEquals(1, $result['likelihood']);
    $this->assertEquals(1, $result['impact']);

    // Above maximum.
    $result = $this->service->calculateRiskScore(10, 10);
    $this->assertEquals(25, $result['score']);
    $this->assertEquals(5, $result['likelihood']);
    $this->assertEquals(5, $result['impact']);
  }

  /**
   * Tests calculateRiskScore risk level boundaries.
   *
   * @covers ::calculateRiskScore
   */
  public function testCalculateRiskScoreBoundaries(): void {
    // Boundary: score 4 (max low).
    $result = $this->service->calculateRiskScore(2, 2);
    $this->assertEquals(4, $result['score']);
    $this->assertEquals('low', $result['level']);

    // Boundary: score 5 (min medium).
    $result = $this->service->calculateRiskScore(1, 5);
    $this->assertEquals(5, $result['score']);
    $this->assertEquals('medium', $result['level']);

    // Boundary: score 9 (max medium).
    $result = $this->service->calculateRiskScore(3, 3);
    $this->assertEquals(9, $result['score']);
    $this->assertEquals('medium', $result['level']);

    // Boundary: score 10 (min high).
    $result = $this->service->calculateRiskScore(2, 5);
    $this->assertEquals(10, $result['score']);
    $this->assertEquals('high', $result['level']);

    // Boundary: score 14 (max high).
    $result = $this->service->calculateRiskScore(2, 4);
    // 2*4 = 8, which is medium. Use 14: not achievable with integers.
    // Let's test score 15 (min critical).
    $result = $this->service->calculateRiskScore(5, 3);
    $this->assertEquals(15, $result['score']);
    $this->assertEquals('critical', $result['level']);
  }

  /**
   * Tests seedDefaultRisks creates all 7 default risks.
   *
   * @covers ::seedDefaultRisks
   */
  public function testSeedDefaultRisks(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    // No existing risks.
    $query->method('execute')->willReturn(0);

    $this->storage->method('getQuery')->willReturn($query);

    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('save')->willReturn(1);

    $this->storage->expects($this->exactly(7))
      ->method('create')
      ->willReturn($entity);

    $this->logger->expects($this->once())
      ->method('info');

    $created = $this->service->seedDefaultRisks(42);
    $this->assertEquals(7, $created);
  }

  /**
   * Tests seedDefaultRisks skips existing risks.
   *
   * @covers ::seedDefaultRisks
   */
  public function testSeedDefaultRisksSkipsExisting(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    // All risks already exist.
    $query->method('execute')->willReturn(1);

    $this->storage->method('getQuery')->willReturn($query);

    $this->storage->expects($this->never())
      ->method('create');

    $created = $this->service->seedDefaultRisks(42);
    $this->assertEquals(0, $created);
  }

  /**
   * Tests seedDefaultRisks handles exceptions gracefully.
   *
   * @covers ::seedDefaultRisks
   */
  public function testSeedDefaultRisksHandlesExceptions(): void {
    $this->storage->method('getQuery')
      ->willThrowException(new \RuntimeException('Database error'));

    $this->logger->expects($this->once())
      ->method('error');

    $created = $this->service->seedDefaultRisks(42);
    $this->assertEquals(0, $created);
  }

  /**
   * Tests getRiskRegister returns empty array for tenant with no risks.
   *
   * @covers ::getRiskRegister
   */
  public function testGetRiskRegisterReturnsEmptyForNoRisks(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->storage->method('getQuery')->willReturn($query);

    $risks = $this->service->getRiskRegister(42);
    $this->assertEmpty($risks);
  }

}
