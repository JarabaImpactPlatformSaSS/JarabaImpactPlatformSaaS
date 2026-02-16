<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_verifactu\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\jaraba_verifactu\Entity\VeriFactuInvoiceRecord;
use Drupal\jaraba_verifactu\Service\VeriFactuQrService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para VeriFactuQrService.
 *
 * Verifica la generacion de URLs de verificacion AEAT y codigos QR.
 *
 * @group jaraba_verifactu
 * @coversDefaultClass \Drupal\jaraba_verifactu\Service\VeriFactuQrService
 */
class VeriFactuQrServiceTest extends UnitTestCase {

  protected VeriFactuQrService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $config = $this->createMock(ImmutableConfig::class);
    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->method('get')->willReturn($config);

    $logger = $this->createMock(LoggerInterface::class);

    $this->service = new VeriFactuQrService($configFactory, $logger);
  }

  /**
   * Tests that the verification URL contains the correct AEAT base URL.
   */
  public function testBuildVerificationUrlContainsAeatBase(): void {
    $record = $this->createMockRecord();
    $url = $this->service->buildVerificationUrl($record);

    $this->assertStringStartsWith(
      'https://www2.agenciatributaria.gob.es/wlpl/TIKE-CONT/ValidarQR',
      $url,
    );
  }

  /**
   * Tests that the verification URL contains the NIF parameter.
   */
  public function testBuildVerificationUrlContainsNif(): void {
    $record = $this->createMockRecord();
    $url = $this->service->buildVerificationUrl($record);

    $this->assertStringContainsString('nif=B12345678', $url);
  }

  /**
   * Tests that the verification URL contains the invoice number.
   */
  public function testBuildVerificationUrlContainsNumSerie(): void {
    $record = $this->createMockRecord();
    $url = $this->service->buildVerificationUrl($record);

    $this->assertStringContainsString('numserie=VF-2026-001', $url);
  }

  /**
   * Tests that the date is formatted correctly (DD-MM-YYYY).
   */
  public function testBuildVerificationUrlFormatsDate(): void {
    $record = $this->createMockRecord();
    $url = $this->service->buildVerificationUrl($record);

    $this->assertStringContainsString('fecha=16-02-2026', $url);
  }

  /**
   * Tests that the total amount has 2 decimal places.
   */
  public function testBuildVerificationUrlFormatsAmount(): void {
    $record = $this->createMockRecord();
    $url = $this->service->buildVerificationUrl($record);

    $this->assertStringContainsString('importe=1210.00', $url);
  }

  /**
   * Tests QR image generation returns non-empty base64 string.
   */
  public function testGenerateQrImageReturnsBase64(): void {
    $url = 'https://example.com/test';
    $qrImage = $this->service->generateQrImage($url);

    $this->assertNotEmpty($qrImage);
    // Verify it's valid base64.
    $decoded = base64_decode($qrImage, TRUE);
    $this->assertNotFalse($decoded);
  }

  /**
   * Tests QR fallback generates SVG content.
   */
  public function testGenerateQrImageFallbackContainsSvg(): void {
    $url = 'https://example.com/test';
    $qrImage = $this->service->generateQrImage($url);

    $decoded = base64_decode($qrImage, TRUE);
    // The fallback SVG should contain VERI*FACTU text.
    $this->assertStringContainsString('VERI*FACTU', $decoded);
  }

  /**
   * Tests QR with custom size.
   */
  public function testGenerateQrImageCustomSize(): void {
    $url = 'https://example.com/test';
    $qrImage = $this->service->generateQrImage($url, 500);

    $this->assertNotEmpty($qrImage);
    $decoded = base64_decode($qrImage, TRUE);
    $this->assertStringContainsString('500', $decoded);
  }

  /**
   * Creates a mock VeriFactuInvoiceRecord for testing.
   */
  protected function createMockRecord(): VeriFactuInvoiceRecord {
    $record = $this->createMock(VeriFactuInvoiceRecord::class);

    $fieldMap = [
      'nif_emisor' => 'B12345678',
      'numero_factura' => 'VF-2026-001',
      'fecha_expedicion' => '2026-02-16',
      'importe_total' => '1210.00',
    ];

    $record->method('get')->willReturnCallback(function (string $field) use ($fieldMap) {
      $item = $this->createMock(FieldItemListInterface::class);
      $item->value = $fieldMap[$field] ?? NULL;
      $item->__get = fn($prop) => $fieldMap[$field] ?? NULL;
      return $item;
    });

    return $record;
  }

}
