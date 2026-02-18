<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_einvoice_b2b\Unit;

use Drupal\jaraba_einvoice_b2b\Model\EN16931Model;
use Drupal\jaraba_einvoice_b2b\Service\EInvoiceUblService;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for the UBL 2.1 generation service.
 *
 * @group jaraba_einvoice_b2b
 * @coversDefaultClass \Drupal\jaraba_einvoice_b2b\Service\EInvoiceUblService
 */
class EInvoiceUblServiceTest extends UnitTestCase {

  protected EInvoiceUblService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->service = new EInvoiceUblService();
  }

  /**
   * Returns a minimal valid EN16931Model for testing.
   */
  protected function createMinimalModel(array $overrides = []): EN16931Model {
    $defaults = [
      'invoice_number' => 'F-2026-001',
      'issue_date' => '2026-01-15',
      'invoice_type_code' => 380,
      'currency_code' => 'EUR',
      'seller' => [
        'name' => 'Cooperativa Olivar del Sur',
        'tax_id' => 'B12345678',
        'endpoint_id' => 'B12345678',
        'endpoint_scheme' => '9920',
        'address' => [
          'street' => 'Calle Olivo 1',
          'city' => 'Jaen',
          'postal_code' => '23001',
          'country' => 'ES',
        ],
        'contact' => [
          'name' => 'Admin',
          'phone' => '+34 953 123 456',
          'email' => 'admin@cooperativa.es',
        ],
      ],
      'buyer' => [
        'name' => 'Distribuidora Norte SL',
        'tax_id' => 'A87654321',
        'endpoint_id' => 'A87654321',
        'endpoint_scheme' => '9920',
        'address' => [
          'street' => 'Avenida Comercial 10',
          'city' => 'Madrid',
          'postal_code' => '28001',
          'country' => 'ES',
        ],
      ],
      'lines' => [
        [
          'description' => 'Aceite de oliva virgen extra 5L',
          'quantity' => '100',
          'unit' => 'LTR',
          'net_amount' => '1000.00',
          'price' => '10.00',
          'tax_percent' => '21.00',
          'tax_category' => 'S',
        ],
      ],
      'tax_totals' => [
        [
          'taxable_amount' => '1000.00',
          'tax_amount' => '210.00',
          'category_id' => 'S',
          'percent' => '21.00',
        ],
      ],
      'total_without_tax' => '1000.00',
      'total_tax' => '210.00',
      'total_with_tax' => '1210.00',
      'amount_due' => '1210.00',
      'payment_means' => [
        'code' => '30',
        'iban' => 'ES9121000418450200051332',
        'bic' => 'CAIXESBBXXX',
        'payment_id' => 'F-2026-001',
      ],
      'payment_terms' => ['note' => 'Pago a 30 dias'],
    ];

    return EN16931Model::fromArray(array_replace_recursive($defaults, $overrides));
  }

  /**
   * Tests generating a standard UBL Invoice (type 380).
   *
   * @covers ::generateFromModel
   */
  public function testGenerateStandardInvoice(): void {
    $model = $this->createMinimalModel();
    $xml = $this->service->generateFromModel($model);

    $this->assertNotEmpty($xml);
    $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);

    $dom = new \DOMDocument();
    $this->assertTrue($dom->loadXML($xml), 'Generated XML must be well-formed.');

    // Root element is Invoice.
    $this->assertSame('Invoice', $dom->documentElement->localName);
    $this->assertSame(
      'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2',
      $dom->documentElement->namespaceURI,
    );
  }

  /**
   * Tests EN 16931 Business Terms are present in generated UBL.
   *
   * @covers ::generateFromModel
   */
  public function testBusinessTermsPresent(): void {
    $model = $this->createMinimalModel();
    $xml = $this->service->generateFromModel($model);

    $dom = new \DOMDocument();
    $dom->loadXML($xml);
    $xpath = new \DOMXPath($dom);
    $xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');

    // BT-1: Invoice number.
    $this->assertSame('F-2026-001', $xpath->query('//cbc:ID')->item(0)->nodeValue);
    // BT-2: Issue date.
    $this->assertSame('2026-01-15', $xpath->query('//cbc:IssueDate')->item(0)->nodeValue);
    // BT-3: Invoice type code.
    $this->assertSame('380', $xpath->query('//cbc:InvoiceTypeCode')->item(0)->nodeValue);
    // BT-5: Currency.
    $this->assertSame('EUR', $xpath->query('//cbc:DocumentCurrencyCode')->item(0)->nodeValue);
  }

  /**
   * Tests seller party structure (BG-4).
   *
   * @covers ::generateFromModel
   */
  public function testSellerPartyStructure(): void {
    $model = $this->createMinimalModel();
    $xml = $this->service->generateFromModel($model);

    // BT-27: Seller name.
    $this->assertStringContainsString('Cooperativa Olivar del Sur', $xml);
    // BT-31: Seller VAT with ES prefix.
    $this->assertStringContainsString('ESB12345678', $xml);
    // Postal address.
    $this->assertStringContainsString('Calle Olivo 1', $xml);
    $this->assertStringContainsString('23001', $xml);
    // Contact.
    $this->assertStringContainsString('+34 953 123 456', $xml);
  }

  /**
   * Tests buyer party structure (BG-7).
   *
   * @covers ::generateFromModel
   */
  public function testBuyerPartyStructure(): void {
    $model = $this->createMinimalModel();
    $xml = $this->service->generateFromModel($model);

    // BT-44: Buyer name.
    $this->assertStringContainsString('Distribuidora Norte SL', $xml);
    // BT-48: Buyer VAT.
    $this->assertStringContainsString('ESA87654321', $xml);
    // Buyer address.
    $this->assertStringContainsString('Avenida Comercial 10', $xml);
  }

  /**
   * Tests payment means with IBAN (BG-16).
   *
   * @covers ::generateFromModel
   */
  public function testPaymentMeansIban(): void {
    $model = $this->createMinimalModel();
    $xml = $this->service->generateFromModel($model);

    $dom = new \DOMDocument();
    $dom->loadXML($xml);
    $xpath = new \DOMXPath($dom);
    $xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
    $xpath->registerNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');

    $this->assertSame('30', $xpath->query('//cac:PaymentMeans/cbc:PaymentMeansCode')->item(0)->nodeValue);
    $this->assertSame('ES9121000418450200051332', $xpath->query('//cac:PaymentMeans/cac:PayeeFinancialAccount/cbc:ID')->item(0)->nodeValue);
    $this->assertSame('CAIXESBBXXX', $xpath->query('//cac:PaymentMeans/cac:PayeeFinancialAccount/cac:FinancialInstitutionBranch/cbc:ID')->item(0)->nodeValue);
  }

  /**
   * Tests tax total and legal monetary total.
   *
   * @covers ::generateFromModel
   */
  public function testTotalsAndTax(): void {
    $model = $this->createMinimalModel();
    $xml = $this->service->generateFromModel($model);

    $dom = new \DOMDocument();
    $dom->loadXML($xml);
    $xpath = new \DOMXPath($dom);
    $xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
    $xpath->registerNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');

    // Tax total.
    $taxAmount = $xpath->query('//cac:TaxTotal/cbc:TaxAmount');
    $this->assertSame(1, $taxAmount->length);
    $this->assertSame('210.00', $taxAmount->item(0)->textContent);

    // Legal monetary total.
    $payable = $xpath->query('//cac:LegalMonetaryTotal/cbc:PayableAmount');
    $this->assertSame(1, $payable->length);
    $this->assertSame('1210.00', $payable->item(0)->textContent);

    // Line extension.
    $lineExt = $xpath->query('//cac:LegalMonetaryTotal/cbc:LineExtensionAmount');
    $this->assertSame('1000.00', $lineExt->item(0)->textContent);
  }

  /**
   * Tests generating a Credit Note (type 381).
   *
   * @covers ::generateFromModel
   */
  public function testGenerateCreditNote(): void {
    $model = $this->createMinimalModel([
      'invoice_type_code' => 381,
      'preceding_invoice_reference' => 'F-2025-099',
    ]);
    $xml = $this->service->generateFromModel($model);

    $dom = new \DOMDocument();
    $dom->loadXML($xml);

    // Root element must be CreditNote.
    $this->assertSame('CreditNote', $dom->documentElement->localName);
    $this->assertSame(
      'urn:oasis:names:specification:ubl:schema:xsd:CreditNote-2',
      $dom->documentElement->namespaceURI,
    );

    // CreditNoteLine instead of InvoiceLine.
    $this->assertStringContainsString('CreditNoteLine', $xml);
    $this->assertStringNotContainsString('InvoiceLine', $xml);

    // BT-25: Preceding invoice reference.
    $this->assertStringContainsString('F-2025-099', $xml);
  }

  /**
   * Tests invoice line generation (BG-25).
   *
   * @covers ::buildInvoiceLine
   */
  public function testInvoiceLineStructure(): void {
    $model = $this->createMinimalModel();
    $xml = $this->service->generateFromModel($model);

    $dom = new \DOMDocument();
    $dom->loadXML($xml);
    $xpath = new \DOMXPath($dom);
    $xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
    $xpath->registerNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');

    $lines = $xpath->query('//cac:InvoiceLine');
    $this->assertSame(1, $lines->length);

    // BT-153: Item name.
    $itemName = $xpath->query('//cac:InvoiceLine/cac:Item/cbc:Name');
    $this->assertSame('Aceite de oliva virgen extra 5L', $itemName->item(0)->textContent);

    // BT-129: Invoiced quantity.
    $qty = $xpath->query('//cac:InvoiceLine/cbc:InvoicedQuantity');
    $this->assertSame('100', $qty->item(0)->textContent);
    $this->assertSame('LTR', $qty->item(0)->getAttribute('unitCode'));
  }

  /**
   * Tests parseUblToModel roundtrip.
   *
   * @covers ::parseUblToModel
   */
  public function testParseUblToModelRoundtrip(): void {
    $originalModel = $this->createMinimalModel();
    $xml = $this->service->generateFromModel($originalModel);

    $parsedModel = $this->service->parseUblToModel($xml);

    $this->assertSame($originalModel->invoiceNumber, $parsedModel->invoiceNumber);
    $this->assertSame($originalModel->issueDate, $parsedModel->issueDate);
    $this->assertSame($originalModel->currencyCode, $parsedModel->currencyCode);
    $this->assertSame($originalModel->totalWithoutTax, $parsedModel->totalWithoutTax);
    $this->assertSame($originalModel->totalTax, $parsedModel->totalTax);
    $this->assertSame($originalModel->totalWithTax, $parsedModel->totalWithTax);
    $this->assertSame($originalModel->amountDue, $parsedModel->amountDue);
    $this->assertCount(1, $parsedModel->lines);
    $this->assertCount(1, $parsedModel->taxTotals);
    $this->assertSame($originalModel->paymentMeans['iban'], $parsedModel->paymentMeans['iban']);
  }

  /**
   * Tests parseUblToModel with invalid XML throws exception.
   *
   * @covers ::parseUblToModel
   */
  public function testParseInvalidXmlThrowsException(): void {
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Invalid UBL XML');
    $this->service->parseUblToModel('not-xml');
  }

  /**
   * Tests optional fields are omitted when NULL.
   *
   * @covers ::generateFromModel
   */
  public function testOptionalFieldsOmitted(): void {
    // We explicitly empty the fields to avoid defaults.
    $model = EN16931Model::fromArray(array_merge($this->createMinimalModel()->toArray(), [
      'payment_means' => [],
      'payment_terms' => [],
    ]));
    $xml = $this->service->generateFromModel($model);

    $this->assertStringNotContainsString('PaymentMeans', $xml);
    $this->assertStringNotContainsString('PaymentTerms', $xml);
  }

  /**
   * Tests multiple invoice lines.
   *
   * @covers ::generateFromModel
   */
  public function testMultipleInvoiceLines(): void {
    $model = $this->createMinimalModel([
      'lines' => [
        ['description' => 'Aceite 5L', 'quantity' => '50', 'net_amount' => '500.00', 'price' => '10.00'],
        ['description' => 'Aceite 2L', 'quantity' => '100', 'net_amount' => '500.00', 'price' => '5.00'],
      ],
    ]);
    $xml = $this->service->generateFromModel($model);

    $dom = new \DOMDocument();
    $dom->loadXML($xml);
    $xpath = new \DOMXPath($dom);
    $xpath->registerNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');

    $lines = $xpath->query('//cac:InvoiceLine');
    $this->assertSame(2, $lines->length);
  }

}
