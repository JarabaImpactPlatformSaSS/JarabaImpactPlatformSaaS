<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_institutional\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_institutional\Service\PiilMetricsService;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for PiilMetricsService.
 *
 * @group jaraba_institutional
 * @coversDefaultClass \Drupal\jaraba_institutional\Service\PiilMetricsService
 */
class PiilMetricsServiceTest extends UnitTestCase {

  protected PiilMetricsService $service;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected Connection $database;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->database = $this->createMock(Connection::class);
    $this->service = new PiilMetricsService($this->entityTypeManager, $this->database);
  }

  /**
   * @covers ::generatePiilReport
   */
  public function testGeneratePiilReportStructure(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    $query->method('execute')->willReturn([]);
    $storage->method('getQuery')->willReturn($query);
    $storage->method('loadMultiple')->willReturn([]);
    $this->entityTypeManager->method('getStorage')->willReturn($storage);

    $report = $this->service->generatePiilReport(1);

    $this->assertArrayHasKey('program_id', $report);
    $this->assertArrayHasKey('generated_at', $report);
    $this->assertArrayHasKey('employment_outcomes', $report);
    $this->assertArrayHasKey('digital_skills', $report);
    $this->assertArrayHasKey('certifications', $report);
    $this->assertEquals(1, $report['program_id']);
  }

  /**
   * @covers ::getEmploymentOutcomes
   */
  public function testGetEmploymentOutcomesEmpty(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);
    $storage->method('getQuery')->willReturn($query);
    $this->entityTypeManager->method('getStorage')->willReturn($storage);

    $outcomes = $this->service->getEmploymentOutcomes(999);

    $this->assertEquals(0, $outcomes['total']);
    $this->assertEquals(0, $outcomes['employed']);
    $this->assertEquals(0.0, $outcomes['rate']);
  }

}
