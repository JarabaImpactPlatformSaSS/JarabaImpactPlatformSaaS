<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_einvoice_b2b\Unit;

use Drupal\jaraba_einvoice_b2b\Model\EN16931Model;
use Drupal\jaraba_einvoice_b2b\Service\EInvoiceFormatConverterService;
use Drupal\jaraba_einvoice_b2b\Service\EInvoiceUblService;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for the format conversion service.
 *
 * @group jaraba_einvoice_b2b
 * @coversDefaultClass \Drupal\jaraba_einvoice_b2b\Service\EInvoiceFormatConverterService
 */
class EInvoiceFormatConverterServiceTest extends UnitTestCase {

  protected EInvoiceFormatConverterService $converter;
  protected EInvoiceUblService $ublService;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->ublService = new EInvoiceUblService();
    $this->converter = new EInvoiceFormatConverterService($this->ublService);
  }

  /**
   * Returns a minimal EN16931Model for testing.
   */
  protected function createModel(array $overrides = []): EN16931Model {
    return EN16931Model::fromArray(array_replace_recursive([
      'invoice_number' => 'FE-2026-001',
      'issue_date' => '2026-02-01',
      'invoice_type_code' => 380,
      'currency_code' => 'EUR',
      'seller' => ['name' => 'Cooperativa Test', 'tax_id' => 'B12345678'],
      'buyer' => ['name' => 'Buyer Corp SL', 'tax_id' => 'A87654321'],
      'lines' => [
        ['description' => 'Producto A', 'quantity' => '10', 'net_amount' => '100.00', 'price' => '10.00', 'tax_percent' => '21.00'],
      ],
      'tax_totals' => [
        ['taxable_amount' => '100.00', 'tax_amount' => '21.00', 'category_id' => 'S', 'percent' => '21.00'],
      ],
      'total_without_tax' => '100.00',
      'total_tax' => '21.00',
      'total_with_tax' => '121.00',
      'amount_due' => '121.00',
    ], $overrides));
  }

  /**
   * Tests UBL format detection.
   *
   * @covers ::detectFormat
   */
  public function testDetectUblFormat(): void {
    $model = $this->createModel();
    $xml = $this->ublService->generateFromModel($model);
    $this->assertSame('ubl_2.1', $this->converter->detectFormat($xml));
  }

  /**
   * Tests Facturae format detection from a generated Facturae XML.
   *
   * @covers ::detectFormat
   */
  public function testDetectFacturaeFormat(): void {
    $facturaeXml = '<?xml version="1.0" encoding="UTF-8"?>'
      . '<Facturae xmlns="http://www.facturae.gob.es/formato/Versiones/Facturaev3_2_2.xml">'
      . '<FileHeader><SchemaVersion>3.2.2</SchemaVersion></FileHeader>'
      . '</Facturae>';
    $this->assertSame('facturae_3.2.2', $this->converter->detectFormat($facturaeXml));
  }

  /**
   * Tests unknown format detection.
   *
   * @covers ::detectFormat
   */
  public function testDetectUnknownFormat(): void {
    $this->assertSame('unknown', $this->converter->detectFormat('<root/>'));
  }

  /**
   * Tests invalid XML returns unknown.
   *
   * @covers ::detectFormat
   */
  public function testDetectInvalidXml(): void {
    $this->assertSame('unknown', $this->converter->detectFormat('not-xml'));
  }

  /**
   * Tests UBL to Facturae conversion.
   *
   * @covers ::convertToFacturae
   */
  public function testConvertUblToFacturae(): void {
    $model = $this->createModel();
    $ublXml = $this->ublService->generateFromModel($model);

    $facturaeXml = $this->converter->convertToFacturae($ublXml);

    $this->assertNotEmpty($facturaeXml);
    $dom = new \DOMDocument();
    $this->assertTrue($dom->loadXML($facturaeXml), 'Facturae XML must be well-formed.');
    $this->assertSame('Facturae', $dom->documentElement->localName);

    // Verify key data preserved.
    $this->assertStringContainsString('FE-2026-001', $facturaeXml);
    $this->assertStringContainsString('B12345678', $facturaeXml);
    $this->assertStringContainsString('A87654321', $facturaeXml);
    $this->assertStringContainsString('3.2.2', $facturaeXml);
  }

  /**
   * Tests Facturae to UBL conversion via convertToUbl.
   *
   * @covers ::convertToUbl
   */
  public function testConvertFacturaeToUbl(): void {
    // First generate Facturae from model.
    $model = $this->createModel();
    $ublXml = $this->ublService->generateFromModel($model);
    $facturaeXml = $this->converter->convertToFacturae($ublXml);

    // Now convert back to UBL.
    $reconvertedUbl = $this->converter->convertToUbl($facturaeXml);

    $this->assertNotEmpty($reconvertedUbl);
    $dom = new \DOMDocument();
    $this->assertTrue($dom->loadXML($reconvertedUbl));
    $this->assertSame('Invoice', $dom->documentElement->localName);
  }

  /**
   * Tests roundtrip conversion preserves key data.
   *
   * @covers ::toNeutralModel
   * @covers ::convertTo
   */
  public function testRoundtripConversionPreservesData(): void {
    $model = $this->createModel();
    $ublXml = $this->ublService->generateFromModel($model);

    // UBL -> Facturae -> UBL.
    $facturaeXml = $this->converter->convertTo($ublXml, 'facturae_3.2.2');
    $roundtripUbl = $this->converter->convertTo($facturaeXml, 'ubl_2.1');

    // Parse the roundtrip UBL back to model.
    $roundtripModel = $this->converter->toNeutralModel($roundtripUbl);

    $this->assertSame('FE-2026-001', $roundtripModel->invoiceNumber);
    $this->assertSame('2026-02-01', $roundtripModel->issueDate);
    $this->assertSame('EUR', $roundtripModel->currencyCode);
    $this->assertSame('100.00', $roundtripModel->totalWithoutTax);
    $this->assertSame('21.00', $roundtripModel->totalTax);
  }

  /**
   * Tests convertTo with unsupported target format throws exception.
   *
   * @covers ::convertTo
   */
  public function testConvertToUnsupportedTargetThrows(): void {
    $model = $this->createModel();
    $xml = $this->ublService->generateFromModel($model);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Unsupported target format');
    $this->converter->convertTo($xml, 'cii');
  }

  /**
   * Tests toNeutralModel with unsupported source format throws exception.
   *
   * @covers ::toNeutralModel
   */
  public function testToNeutralModelUnsupportedSourceThrows(): void {
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Unsupported format');
    $this->converter->toNeutralModel('<root/>');
  }

  /**
   * Tests Facturae generation includes proper structure.
   *
   * @covers ::convertToFacturae
   */
  public function testFacturaeStructure(): void {
    $model = $this->createModel([
      'payment_means' => ['code' => '30', 'iban' => 'ES9121000418450200051332'],
    ]);
    $ublXml = $this->ublService->generateFromModel($model);
    $facturaeXml = $this->converter->convertToFacturae($ublXml);

    // Required Facturae elements.
    $this->assertStringContainsString('FileHeader', $facturaeXml);
    $this->assertStringContainsString('Parties', $facturaeXml);
    $this->assertStringContainsString('SellerParty', $facturaeXml);
    $this->assertStringContainsString('BuyerParty', $facturaeXml);
    $this->assertStringContainsString('InvoiceTotals', $facturaeXml);
    $this->assertStringContainsString('TaxesOutputs', $facturaeXml);
    $this->assertStringContainsString('Items', $facturaeXml);
    // IBAN in payment details.
    $this->assertStringContainsString('ES9121000418450200051332', $facturaeXml);
  }

  /**
   * Tests Credit Note Facturae conversion sets correct document type.
   *
   * @covers ::convertToFacturae
   */
  public function testCreditNoteFacturaeDocumentType(): void {
    $model = $this->createModel(['invoice_type_code' => 381]);
    $ublXml = $this->ublService->generateFromModel($model);
    $facturaeXml = $this->converter->convertToFacturae($ublXml);

    // RA = Rectificativa (credit note) in Facturae.
    $this->assertStringContainsString('<InvoiceDocumentType>RA</InvoiceDocumentType>', $facturaeXml);
  }

}
