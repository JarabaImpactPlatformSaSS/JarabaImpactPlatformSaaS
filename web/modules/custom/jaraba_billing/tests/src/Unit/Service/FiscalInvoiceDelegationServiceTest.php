<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_billing\Unit\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_billing\Service\FiscalInvoiceDelegationService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for FiscalInvoiceDelegationService.
 *
 * @group jaraba_billing
 * @coversDefaultClass \Drupal\jaraba_billing\Service\FiscalInvoiceDelegationService
 */
class FiscalInvoiceDelegationServiceTest extends UnitTestCase {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
  }

  /**
   * Creates a service instance with optional fiscal module services.
   */
  protected function createService(
    ?object $verifactuRecordService = NULL,
    ?object $facturaeXmlService = NULL,
    ?object $einvoiceDeliveryService = NULL,
  ): FiscalInvoiceDelegationService {
    return new FiscalInvoiceDelegationService(
      $this->entityTypeManager,
      $this->logger,
      $verifactuRecordService,
      $facturaeXmlService,
      $einvoiceDeliveryService,
    );
  }

  /**
   * Creates a mock BillingInvoice with given buyer NIF.
   */
  protected function createMockInvoice(string $buyerNif): object {
    $nifField = new \stdClass();
    $nifField->value = $buyerNif;

    $invoice = $this->createMock(BillingInvoiceInterface::class);
    $invoice->method('id')->willReturn(1);
    $invoice->method('get')->willReturnCallback(function (string $field) use ($nifField) {
      if ($field === 'buyer_nif') {
        return $nifField;
      }
      return new \stdClass();
    });

    return $invoice;
  }

  /**
   * Tests B2G detection for public administration NIF (Q prefix).
   *
   * @covers ::detectInvoiceType
   */
  public function testDetectB2gForQPrefix(): void {
    $service = $this->createService();
    $invoice = $this->createMockInvoice('Q2826000H');
    $this->assertSame('b2g', $service->detectInvoiceType($invoice));
  }

  /**
   * Tests B2G detection for S prefix (autonomous organs).
   *
   * @covers ::detectInvoiceType
   */
  public function testDetectB2gForSPrefix(): void {
    $service = $this->createService();
    $invoice = $this->createMockInvoice('S4100001F');
    $this->assertSame('b2g', $service->detectInvoiceType($invoice));
  }

  /**
   * Tests B2G detection for P prefix (local corporations).
   *
   * @covers ::detectInvoiceType
   */
  public function testDetectB2gForPPrefix(): void {
    $service = $this->createService();
    $invoice = $this->createMockInvoice('P4100000A');
    $this->assertSame('b2g', $service->detectInvoiceType($invoice));
  }

  /**
   * Tests B2B detection for standard CIF.
   *
   * @covers ::detectInvoiceType
   * @dataProvider b2bNifProvider
   */
  public function testDetectB2bForBusinessNif(string $nif): void {
    $service = $this->createService();
    $invoice = $this->createMockInvoice($nif);
    $this->assertSame('b2b', $service->detectInvoiceType($invoice));
  }

  /**
   * Provides B2B NIF examples.
   */
  public static function b2bNifProvider(): array {
    return [
      'CIF A' => ['A12345678'],
      'CIF B' => ['B12345678'],
      'CIF C' => ['C12345678'],
      'CIF H' => ['H12345678'],
      'CIF J' => ['J12345678'],
      'CIF U' => ['U12345678'],
    ];
  }

  /**
   * Tests nacional detection for DNI (consumer).
   *
   * @covers ::detectInvoiceType
   */
  public function testDetectNacionalForDni(): void {
    $service = $this->createService();
    $invoice = $this->createMockInvoice('12345678Z');
    $this->assertSame('nacional', $service->detectInvoiceType($invoice));
  }

  /**
   * Tests nacional detection for empty NIF.
   *
   * @covers ::detectInvoiceType
   */
  public function testDetectNacionalForEmptyNif(): void {
    $service = $this->createService();
    $invoice = $this->createMockInvoice('');
    $this->assertSame('nacional', $service->detectInvoiceType($invoice));
  }

  /**
   * Tests processing with all modules installed — B2G invoice.
   *
   * @covers ::processFinalizedInvoice
   */
  public function testProcessB2gInvoiceWithAllModules(): void {
    $verifactu = $this->createMock(VerifactuRecordServiceInterface::class);
    $resultEntity = $this->createMock(FiscalResultEntityInterface::class);
    $resultEntity->method('id')->willReturn(42);
    $verifactu->method('createFromBillingInvoice')->willReturn($resultEntity);

    $facturae = $this->createMock(FacturaeXmlServiceInterface::class);
    $facturaeEntity = $this->createMock(FiscalResultEntityInterface::class);
    $facturaeEntity->method('id')->willReturn(15);
    $facturae->method('generateFromBillingInvoice')->willReturn($facturaeEntity);

    $service = $this->createService($verifactu, $facturae);
    $invoice = $this->createMockInvoice('Q2826000H');

    $results = $service->processFinalizedInvoice($invoice);

    $this->assertSame('success', $results['verifactu']['status']);
    $this->assertSame(42, $results['verifactu']['record_id']);
    $this->assertSame('success', $results['facturae']['status']);
    $this->assertSame(15, $results['facturae']['invoice_id']);
  }

  /**
   * Tests skipped when modules not installed.
   *
   * @covers ::processFinalizedInvoice
   */
  public function testSkippedWhenModulesNotInstalled(): void {
    $service = $this->createService();
    $invoice = $this->createMockInvoice('Q2826000H');

    $results = $service->processFinalizedInvoice($invoice);

    $this->assertSame('skipped', $results['verifactu']['status']);
    // B2G but facturae not installed.
    $this->assertSame('skipped', $results['facturae']['status']);
  }

  /**
   * Tests B2B invoice delegates to einvoice but not facturae.
   *
   * @covers ::processFinalizedInvoice
   */
  public function testB2bInvoiceDelegatesToEinvoice(): void {
    $einvoice = $this->createMock(EinvoiceDeliveryServiceInterface::class);
    $resultEntity = $this->createMock(FiscalResultEntityInterface::class);
    $resultEntity->method('id')->willReturn(99);
    $einvoice->method('createFromBillingInvoice')->willReturn($resultEntity);

    $service = $this->createService(einvoiceDeliveryService: $einvoice);
    $invoice = $this->createMockInvoice('B12345678');

    $results = $service->processFinalizedInvoice($invoice);

    $this->assertSame('success', $results['einvoice_b2b']['status']);
    $this->assertArrayNotHasKey('facturae', $results);
  }

  /**
   * Tests nacional invoice only triggers VeriFactu.
   *
   * @covers ::processFinalizedInvoice
   */
  public function testNacionalInvoiceOnlyTriggersVerifactu(): void {
    $verifactu = $this->createMock(VerifactuRecordServiceInterface::class);
    $resultEntity = $this->createMock(FiscalResultEntityInterface::class);
    $resultEntity->method('id')->willReturn(1);
    $verifactu->method('createFromBillingInvoice')->willReturn($resultEntity);

    $service = $this->createService($verifactu);
    $invoice = $this->createMockInvoice('12345678Z');

    $results = $service->processFinalizedInvoice($invoice);

    $this->assertSame('success', $results['verifactu']['status']);
    $this->assertArrayNotHasKey('facturae', $results);
    $this->assertArrayNotHasKey('einvoice_b2b', $results);
  }

  /**
   * Tests error handling when delegation throws exception.
   *
   * @covers ::processFinalizedInvoice
   */
  public function testDelegationExceptionReturnsError(): void {
    $verifactu = $this->createMock(VerifactuRecordServiceInterface::class);
    $verifactu->method('createFromBillingInvoice')
      ->willThrowException(new \RuntimeException('Connection failed'));

    $service = $this->createService($verifactu);
    $invoice = $this->createMockInvoice('12345678Z');

    $results = $service->processFinalizedInvoice($invoice);

    $this->assertSame('error', $results['verifactu']['status']);
    $this->assertSame('Connection failed', $results['verifactu']['message']);
  }

}

/**
 * Temporary interface for mocking billing invoice.
 */
interface BillingInvoiceInterface {
  public function id();
  public function get(string $field);
}

/**
 * Temporary interface for mocking verifactu service.
 */
interface VerifactuRecordServiceInterface {
  public function createFromBillingInvoice(object $invoice);
}

/**
 * Temporary interface for mocking facturae service.
 */
interface FacturaeXmlServiceInterface {
  public function generateFromBillingInvoice(object $invoice);
}

/**
 * Temporary interface for mocking einvoice service.
 */
interface EinvoiceDeliveryServiceInterface {
  public function createFromBillingInvoice(object $invoice);
}

/**
 * Temporary interface for mocking fiscal result entities.
 */
interface FiscalResultEntityInterface {
  public function id();
}
