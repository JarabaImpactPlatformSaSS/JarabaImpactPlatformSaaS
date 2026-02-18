<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_security_compliance\Unit\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_security_compliance\Service\EnsComplianceService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for EnsComplianceService.
 *
 * @group jaraba_security_compliance
 * @coversDefaultClass \Drupal\jaraba_security_compliance\Service\EnsComplianceService
 */
class EnsComplianceServiceTest extends TestCase {

  /**
   * The service under test.
   */
  protected EnsComplianceService $service;

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
      ->with('ens_compliance')
      ->willReturn($this->storage);

    $this->service = new EnsComplianceService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * Tests getMediaMeasures returns all expected measures.
   *
   * @covers ::getMediaMeasures
   */
  public function testGetMediaMeasures(): void {
    $measures = $this->service->getMediaMeasures();

    // Should have at least 18 measures.
    $this->assertGreaterThanOrEqual(18, count($measures));

    // Verify organizational measures (org.*).
    $this->assertArrayHasKey('org.1', $measures);
    $this->assertArrayHasKey('org.2', $measures);
    $this->assertArrayHasKey('org.3', $measures);
    $this->assertArrayHasKey('org.4', $measures);
    $this->assertEquals('organizational', $measures['org.1']['category']);
    $this->assertEquals('Politica de Seguridad', $measures['org.1']['measure_name']);

    // Verify operational measures (op.*).
    $this->assertArrayHasKey('op.pl.1', $measures);
    $this->assertArrayHasKey('op.pl.2', $measures);
    $this->assertArrayHasKey('op.acc.1', $measures);
    $this->assertArrayHasKey('op.acc.2', $measures);
    $this->assertArrayHasKey('op.acc.5', $measures);
    $this->assertArrayHasKey('op.exp.1', $measures);
    $this->assertArrayHasKey('op.exp.2', $measures);
    $this->assertEquals('operational', $measures['op.acc.5']['category']);

    // Verify protection measures (mp.*).
    $this->assertArrayHasKey('mp.if.1', $measures);
    $this->assertArrayHasKey('mp.per.1', $measures);
    $this->assertArrayHasKey('mp.eq.1', $measures);
    $this->assertArrayHasKey('mp.com.1', $measures);
    $this->assertArrayHasKey('mp.si.1', $measures);
    $this->assertArrayHasKey('mp.s.1', $measures);
    $this->assertEquals('protection', $measures['mp.com.1']['category']);
  }

  /**
   * Tests getMediaMeasures includes required fields in each measure.
   *
   * @covers ::getMediaMeasures
   */
  public function testGetMediaMeasuresHasRequiredFields(): void {
    $measures = $this->service->getMediaMeasures();

    foreach ($measures as $measureId => $measure) {
      $this->assertArrayHasKey('measure_id', $measure, "Missing measure_id in $measureId");
      $this->assertArrayHasKey('category', $measure, "Missing category in $measureId");
      $this->assertArrayHasKey('measure_name', $measure, "Missing measure_name in $measureId");
      $this->assertArrayHasKey('required_level', $measure, "Missing required_level in $measureId");
      $this->assertArrayHasKey('description', $measure, "Missing description in $measureId");

      // Validate category values.
      $this->assertContains($measure['category'], ['organizational', 'operational', 'protection'],
        "Invalid category for $measureId");

      // Validate required_level values.
      $this->assertContains($measure['required_level'], ['basic', 'medium', 'high'],
        "Invalid required_level for $measureId");
    }
  }

  /**
   * Tests getComplianceSummary returns all three categories.
   *
   * @covers ::getComplianceSummary
   */
  public function testGetComplianceSummaryHasAllCategories(): void {
    $summary = $this->service->getComplianceSummary();

    $this->assertArrayHasKey('organizational', $summary);
    $this->assertArrayHasKey('operational', $summary);
    $this->assertArrayHasKey('protection', $summary);

    foreach ($summary as $catCode => $catData) {
      $this->assertArrayHasKey('category', $catData);
      $this->assertArrayHasKey('label', $catData);
      $this->assertArrayHasKey('total', $catData);
      $this->assertArrayHasKey('implemented', $catData);
      $this->assertArrayHasKey('partial', $catData);
      $this->assertArrayHasKey('not_implemented', $catData);
      $this->assertArrayHasKey('score', $catData);

      // Total should equal sum of statuses.
      $this->assertEquals(
        $catData['total'],
        $catData['implemented'] + $catData['partial'] + $catData['not_implemented'],
        "Status counts don't add up for $catCode"
      );
    }
  }

  /**
   * Tests getComplianceSummary organizational measures count.
   *
   * @covers ::getComplianceSummary
   */
  public function testGetComplianceSummaryOrganizationalCount(): void {
    $summary = $this->service->getComplianceSummary();

    // Should have 4 organizational measures (org.1-4).
    $this->assertEquals(4, $summary['organizational']['total']);
  }

  /**
   * Tests assessMeasure for a known measure.
   *
   * @covers ::assessMeasure
   */
  public function testAssessMeasureKnownMeasure(): void {
    $result = $this->service->assessMeasure('org.1');

    $this->assertTrue($result['found']);
    $this->assertEquals('org.1', $result['measure_id']);
    $this->assertEquals('Politica de Seguridad', $result['measure_name']);
    $this->assertEquals('organizational', $result['category']);
    $this->assertNotEmpty($result['platform_mapping']);
    $this->assertContains($result['status'], ['implemented', 'partial', 'not_implemented', 'not_applicable']);
  }

  /**
   * Tests assessMeasure for an unknown measure.
   *
   * @covers ::assessMeasure
   */
  public function testAssessMeasureUnknownMeasure(): void {
    $result = $this->service->assessMeasure('nonexistent.999');

    $this->assertFalse($result['found']);
    $this->assertEquals('nonexistent.999', $result['measure_id']);
    $this->assertEquals('not_applicable', $result['status']);
  }

  /**
   * Tests seedDefaultMeasures creates all measures for new tenant.
   *
   * @covers ::seedDefaultMeasures
   */
  public function testSeedDefaultMeasures(): void {
    $measures = $this->service->getMediaMeasures();
    $expectedCount = count($measures);

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    // No existing measures.
    $query->method('execute')->willReturn(0);

    $this->storage->method('getQuery')->willReturn($query);

    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('save')->willReturn(1);

    $this->storage->expects($this->exactly($expectedCount))
      ->method('create')
      ->willReturn($entity);

    $this->logger->expects($this->once())
      ->method('info');

    $created = $this->service->seedDefaultMeasures(42);
    $this->assertEquals($expectedCount, $created);
  }

  /**
   * Tests seedDefaultMeasures skips existing measures.
   *
   * @covers ::seedDefaultMeasures
   */
  public function testSeedDefaultMeasuresSkipsExisting(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    // All measures already exist.
    $query->method('execute')->willReturn(1);

    $this->storage->method('getQuery')->willReturn($query);

    $this->storage->expects($this->never())
      ->method('create');

    $created = $this->service->seedDefaultMeasures(42);
    $this->assertEquals(0, $created);
  }

  /**
   * Tests seedDefaultMeasures handles exceptions gracefully.
   *
   * @covers ::seedDefaultMeasures
   */
  public function testSeedDefaultMeasuresHandlesExceptions(): void {
    $this->storage->method('getQuery')
      ->willThrowException(new \RuntimeException('Database error'));

    $this->logger->expects($this->once())
      ->method('error');

    $created = $this->service->seedDefaultMeasures(42);
    $this->assertEquals(0, $created);
  }

}
