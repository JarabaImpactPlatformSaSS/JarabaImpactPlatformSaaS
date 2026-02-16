<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_verifactu\Unit;

use Drupal\Core\File\FileSystemInterface;
use Drupal\jaraba_verifactu\Service\VeriFactuPdfService;
use Drupal\jaraba_verifactu\Service\VeriFactuQrService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para VeriFactuPdfService.
 *
 * Verifica la generacion de PDFs de compliance VeriFactu.
 *
 * @group jaraba_verifactu
 * @coversDefaultClass \Drupal\jaraba_verifactu\Service\VeriFactuPdfService
 */
class VeriFactuPdfServiceTest extends UnitTestCase {

  protected VeriFactuPdfService $service;
  protected VeriFactuQrService $qrService;
  protected FileSystemInterface $fileSystem;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->qrService = $this->createMock(VeriFactuQrService::class);
    $this->fileSystem = $this->createMock(FileSystemInterface::class);
    $logger = $this->createMock(LoggerInterface::class);

    $this->service = new VeriFactuPdfService(
      $this->qrService,
      $this->fileSystem,
      $logger,
    );
  }

  /**
   * Tests stampInvoicePdf returns null when source file not found.
   */
  public function testStampInvoicePdfFileNotFound(): void {
    $this->fileSystem->method('realpath')->willReturn(FALSE);

    $result = $this->service->stampInvoicePdf('private://nonexistent.pdf', []);
    $this->assertNull($result);
  }

  /**
   * Tests generateCompliancePage returns null when save fails.
   */
  public function testGenerateCompliancePageSaveFailure(): void {
    $this->fileSystem->method('prepareDirectory')->willReturn(TRUE);
    $this->fileSystem->method('realpath')->willReturn(FALSE);

    $result = $this->service->generateCompliancePage([
      'nif_emisor' => 'B12345678',
      'numero_factura' => 'VF-2026-001',
      'fecha_expedicion' => '2026-02-16',
      'importe_total' => '1210.00',
      'hash_record' => str_repeat('a', 64),
    ]);

    $this->assertNull($result);
  }

  /**
   * Tests service accepts valid record data without exceptions.
   */
  public function testServiceAcceptsValidRecordData(): void {
    // This test verifies the service doesn't throw on valid input,
    // even if it can't save (no real filesystem).
    $this->fileSystem->method('prepareDirectory')->willReturn(TRUE);
    $this->fileSystem->method('realpath')->willReturn(FALSE);

    $this->qrService->method('generateQrImage')->willReturn(NULL);

    // Should not throw, just return NULL because filesystem is mocked.
    $result = $this->service->generateCompliancePage([
      'nif_emisor' => 'B12345678',
      'numero_factura' => 'VF-2026-001',
      'fecha_expedicion' => '2026-02-16',
      'importe_total' => '1210.00',
      'hash_record' => str_repeat('b', 64),
    ]);

    $this->assertNull($result);
  }

  /**
   * Tests service handles empty record data gracefully.
   */
  public function testServiceHandlesEmptyRecordData(): void {
    $this->fileSystem->method('prepareDirectory')->willReturn(TRUE);
    $this->fileSystem->method('realpath')->willReturn(FALSE);

    // Should not throw with empty data.
    $result = $this->service->generateCompliancePage([]);
    $this->assertNull($result);
  }

}
