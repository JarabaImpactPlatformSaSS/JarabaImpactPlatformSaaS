<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\ecosistema_jaraba_core\Service\BrandedPdfService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for BrandedPdfService.
 *
 * @group ecosistema_jaraba_core
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\BrandedPdfService
 */
class BrandedPdfServiceTest extends TestCase {

  /**
   * The service under test.
   */
  protected BrandedPdfService $service;

  /**
   * Mocked file system.
   */
  protected FileSystemInterface&MockObject $fileSystem;

  /**
   * Mocked entity type manager.
   */
  protected EntityTypeManagerInterface&MockObject $entityTypeManager;

  /**
   * Mocked logger.
   */
  protected LoggerInterface&MockObject $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->fileSystem = $this->createMock(FileSystemInterface::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new BrandedPdfService(
      $this->fileSystem,
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * Tests getBrandConfig returns platform defaults when no tenant config.
   *
   * The protected getBrandConfig() method uses \Drupal::hasService() which
   * will not be available in a pure unit test context, so it falls back to
   * the default platform colors. We use reflection to test this.
   *
   * @covers ::getBrandConfig
   */
  public function testGetBrandConfigReturnsPlatformDefaults(): void {
    $method = new \ReflectionMethod(BrandedPdfService::class, 'getBrandConfig');
    $method->setAccessible(TRUE);

    // When called without the Drupal container available (unit test context),
    // the method catches the error and returns defaults.
    $result = $method->invoke($this->service, NULL);

    $this->assertIsArray($result);
    $this->assertEquals('#FF8C42', $result['color_primary']);
    $this->assertEquals('#00A9A5', $result['color_secondary']);
    $this->assertEquals('#233D63', $result['color_accent']);
    $this->assertNull($result['logo_path']);
    $this->assertEquals('Outfit', $result['font_heading']);
    $this->assertEquals('Inter', $result['font_body']);
  }

  /**
   * Tests getBrandConfig returns defaults for a specific tenant ID.
   *
   * Even with a valid tenant ID, when the jaraba_theming service
   * is not available, defaults should be returned.
   *
   * @covers ::getBrandConfig
   */
  public function testGetBrandConfigReturnsPlatformDefaultsForTenantId(): void {
    $method = new \ReflectionMethod(BrandedPdfService::class, 'getBrandConfig');
    $method->setAccessible(TRUE);

    // With a tenant ID but no Drupal container, falls back to defaults.
    $result = $method->invoke($this->service, 42);

    $this->assertIsArray($result);
    $this->assertEquals('#FF8C42', $result['color_primary']);
    $this->assertEquals('#00A9A5', $result['color_secondary']);
    $this->assertEquals('#233D63', $result['color_accent']);
  }

  /**
   * Tests generateInvoice returns null when TCPDF is not available or fails.
   *
   * Since TCPDF class may not be loaded in the test environment,
   * generateInvoice() should catch the error and return null.
   *
   * @covers ::generateInvoice
   */
  public function testGenerateInvoiceReturnsNullOnError(): void {
    // Create a partial mock that makes createPdfInstance throw.
    $service = $this->getMockBuilder(BrandedPdfService::class)
      ->setConstructorArgs([
        $this->fileSystem,
        $this->entityTypeManager,
        $this->logger,
      ])
      ->onlyMethods(['createPdfInstance', 'getBrandConfig'])
      ->getMock();

    $service->method('getBrandConfig')
      ->willReturn([
        'color_primary' => '#FF8C42',
        'color_secondary' => '#00A9A5',
        'color_accent' => '#233D63',
        'logo_path' => NULL,
        'font_heading' => 'Outfit',
        'font_body' => 'Inter',
      ]);

    $service->method('createPdfInstance')
      ->willThrowException(new \Error('Class "TCPDF" not found'));

    // Logger should record the error.
    $this->logger->expects($this->once())
      ->method('error')
      ->with(
        'Error al generar factura PDF: @message',
        $this->callback(function (array $context): bool {
          return str_contains($context['@message'], 'TCPDF');
        })
      );

    $result = $service->generateInvoice([
      'invoice_number' => 'INV-001',
      'date' => '01/01/2026',
      'client_name' => 'Test Client',
      'items' => [],
      'subtotal' => 100.0,
      'tax_rate' => 21,
      'tax_amount' => 21.0,
      'total' => 121.0,
    ]);

    $this->assertNull($result);
  }

  /**
   * Tests generateCertificate returns null when TCPDF fails.
   *
   * @covers ::generateCertificate
   */
  public function testGenerateCertificateReturnsNullOnError(): void {
    $service = $this->getMockBuilder(BrandedPdfService::class)
      ->setConstructorArgs([
        $this->fileSystem,
        $this->entityTypeManager,
        $this->logger,
      ])
      ->onlyMethods(['createPdfInstance', 'getBrandConfig'])
      ->getMock();

    $service->method('getBrandConfig')
      ->willReturn([
        'color_primary' => '#FF8C42',
        'color_secondary' => '#00A9A5',
        'color_accent' => '#233D63',
        'logo_path' => NULL,
        'font_heading' => 'Outfit',
        'font_body' => 'Inter',
      ]);

    $service->method('createPdfInstance')
      ->willThrowException(new \Error('Class "TCPDF" not found'));

    // Logger should record the error.
    $this->logger->expects($this->once())
      ->method('error')
      ->with(
        'Error al generar certificado PDF: @message',
        $this->callback(function (array $context): bool {
          return str_contains($context['@message'], 'TCPDF');
        })
      );

    $result = $service->generateCertificate([
      'title' => 'Test Certificate',
      'recipient_name' => 'John Doe',
      'description' => 'For completing the test.',
      'date' => '01/01/2026',
      'certificate_id' => 'CERT-001',
      'issuer_name' => 'Test Issuer',
    ]);

    $this->assertNull($result);
  }

  /**
   * Tests generateReport returns null when TCPDF fails.
   *
   * @covers ::generateReport
   */
  public function testGenerateReportReturnsNullOnError(): void {
    $service = $this->getMockBuilder(BrandedPdfService::class)
      ->setConstructorArgs([
        $this->fileSystem,
        $this->entityTypeManager,
        $this->logger,
      ])
      ->onlyMethods(['createPdfInstance', 'getBrandConfig'])
      ->getMock();

    $service->method('getBrandConfig')
      ->willReturn([
        'color_primary' => '#FF8C42',
        'color_secondary' => '#00A9A5',
        'color_accent' => '#233D63',
        'logo_path' => NULL,
        'font_heading' => 'Outfit',
        'font_body' => 'Inter',
      ]);

    $service->method('createPdfInstance')
      ->willThrowException(new \Error('Class "TCPDF" not found'));

    $this->logger->expects($this->once())
      ->method('error');

    $result = $service->generateReport([
      'title' => 'Monthly Report',
      'sections' => [
        ['title' => 'Summary', 'content' => 'Overview of the month.'],
      ],
      'date' => '01/01/2026',
      'author' => 'Test Author',
    ]);

    $this->assertNull($result);
  }

  /**
   * Tests hexToRgb converts valid hex colors correctly.
   *
   * @covers ::hexToRgb
   */
  public function testHexToRgbConvertsCorrectly(): void {
    $method = new \ReflectionMethod(BrandedPdfService::class, 'hexToRgb');
    $method->setAccessible(TRUE);

    // Test with hash prefix.
    $result = $method->invoke($this->service, '#FF8C42');
    $this->assertEquals([255, 140, 66], $result);

    // Test without hash prefix.
    $result = $method->invoke($this->service, '00A9A5');
    $this->assertEquals([0, 169, 165], $result);

    // Test black.
    $result = $method->invoke($this->service, '#000000');
    $this->assertEquals([0, 0, 0], $result);

    // Test white.
    $result = $method->invoke($this->service, '#FFFFFF');
    $this->assertEquals([255, 255, 255], $result);
  }

  /**
   * Tests hexToRgb returns black for invalid hex input.
   *
   * @covers ::hexToRgb
   */
  public function testHexToRgbReturnsBlackForInvalidInput(): void {
    $method = new \ReflectionMethod(BrandedPdfService::class, 'hexToRgb');
    $method->setAccessible(TRUE);

    // Too short.
    $result = $method->invoke($this->service, '#FFF');
    $this->assertEquals([0, 0, 0], $result);

    // Empty.
    $result = $method->invoke($this->service, '');
    $this->assertEquals([0, 0, 0], $result);

    // Too long.
    $result = $method->invoke($this->service, '#FF8C42AA');
    $this->assertEquals([0, 0, 0], $result);
  }

  /**
   * Tests that default brand config has all required keys.
   *
   * @covers ::getBrandConfig
   */
  public function testBrandConfigHasAllRequiredKeys(): void {
    $method = new \ReflectionMethod(BrandedPdfService::class, 'getBrandConfig');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->service, NULL);

    $requiredKeys = [
      'color_primary',
      'color_secondary',
      'color_accent',
      'logo_path',
      'font_heading',
      'font_body',
    ];

    foreach ($requiredKeys as $key) {
      $this->assertArrayHasKey($key, $result, "Missing required brand config key: {$key}");
    }
  }

}
