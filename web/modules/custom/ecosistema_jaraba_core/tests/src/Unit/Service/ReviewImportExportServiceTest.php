<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\ReviewImportExportService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for ReviewImportExportService (B-14).
 *
 * @group ecosistema_jaraba_core
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\ReviewImportExportService
 */
class ReviewImportExportServiceTest extends UnitTestCase {

  protected ReviewImportExportService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $logger = $this->createMock(LoggerInterface::class);

    $this->service = new ReviewImportExportService($entityTypeManager, $logger);
  }

  /**
   * @covers ::parseCsv
   */
  public function testParseCsvValidData(): void {
    $csv = "entity_type,rating,body,status\ncomercio_review,5,Great product,approved\nreview_agro,4,Good service,pending";
    $rows = $this->service->parseCsv($csv);

    $this->assertCount(2, $rows);
    $this->assertEquals('comercio_review', $rows[0]['entity_type']);
    $this->assertEquals('5', $rows[0]['rating']);
    $this->assertEquals('Great product', $rows[0]['body']);
    $this->assertEquals('approved', $rows[0]['status']);
  }

  /**
   * @covers ::parseCsv
   */
  public function testParseCsvEmptyInput(): void {
    $rows = $this->service->parseCsv('');
    $this->assertEmpty($rows);
  }

  /**
   * @covers ::parseCsv
   */
  public function testParseCsvHeaderOnly(): void {
    $csv = "entity_type,rating,body,status\n";
    $rows = $this->service->parseCsv($csv);
    $this->assertEmpty($rows);
  }

  /**
   * @covers ::parseCsv
   */
  public function testParseCsvMismatchedColumns(): void {
    $csv = "entity_type,rating,body\ncomercio_review,5";
    $rows = $this->service->parseCsv($csv);
    // Row with wrong column count should be skipped.
    $this->assertEmpty($rows);
  }

  /**
   * @covers ::parseCsv
   */
  public function testParseCsvWithQuotedFields(): void {
    $csv = "entity_type,rating,body,status\ncomercio_review,5,\"A great, wonderful product\",approved";
    $rows = $this->service->parseCsv($csv);

    $this->assertCount(1, $rows);
    $this->assertEquals('A great, wonderful product', $rows[0]['body']);
  }

  /**
   * @covers ::exportJson
   */
  public function testExportJsonStructure(): void {
    $json = $this->service->exportJson('nonexistent_type');
    $data = json_decode($json, TRUE);

    $this->assertArrayHasKey('version', $data);
    $this->assertArrayHasKey('exported_at', $data);
    $this->assertArrayHasKey('total', $data);
    $this->assertArrayHasKey('reviews', $data);
    $this->assertEquals('1.0', $data['version']);
    $this->assertEquals(0, $data['total']);
  }

  /**
   * @covers ::exportJson
   */
  public function testExportJsonInvalidTypeReturnsEmpty(): void {
    $json = $this->service->exportJson('invalid_entity_type');
    $data = json_decode($json, TRUE);

    $this->assertEmpty($data['reviews']);
  }

}
