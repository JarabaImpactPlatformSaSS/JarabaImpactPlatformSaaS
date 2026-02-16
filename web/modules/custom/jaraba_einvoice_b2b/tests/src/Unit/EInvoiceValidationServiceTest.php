<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_einvoice_b2b\Unit;

use Drupal\jaraba_einvoice_b2b\Model\EN16931Model;
use Drupal\jaraba_einvoice_b2b\Service\EInvoiceUblService;
use Drupal\jaraba_einvoice_b2b\Service\EInvoiceValidationService;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for the 4-layer validation service.
 *
 * @group jaraba_einvoice_b2b
 * @coversDefaultClass \Drupal\jaraba_einvoice_b2b\Service\EInvoiceValidationService
 */
class EInvoiceValidationServiceTest extends UnitTestCase {

  protected EInvoiceValidationService $validator;
  protected EInvoiceUblService $ublService;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->validator = new EInvoiceValidationService();
    $this->ublService = new EInvoiceUblService();
  }

  /**
   * Returns a valid UBL XML for testing.
   */
  protected function createValidUblXml(): string {
    $model = EN16931Model::fromArray([
      'invoice_number' => 'VAL-001',
      'issue_date' => '2026-01-15',
      'invoice_type_code' => 380,
      'currency_code' => 'EUR',
      'seller' => ['name' => 'Seller SL', 'tax_id' => 'B12345678'],
      'buyer' => ['name' => 'Buyer SA', 'tax_id' => 'A87654321'],
      'lines' => [
        ['description' => 'Item', 'quantity' => '1', 'net_amount' => '100.00', 'price' => '100.00', 'tax_percent' => '21.00'],
      ],
      'tax_totals' => [
        ['taxable_amount' => '100.00', 'tax_amount' => '21.00', 'category_id' => 'S', 'percent' => '21.00'],
      ],
      'total_without_tax' => '100.00',
      'total_tax' => '21.00',
      'total_with_tax' => '121.00',
      'amount_due' => '121.00',
    ]);
    return $this->ublService->generateFromModel($model);
  }

  /**
   * Tests Schematron validation passes for valid XML.
   *
   * @covers ::validateSchematron
   */
  public function testSchematronValidXml(): void {
    $xml = $this->createValidUblXml();
    $result = $this->validator->validateSchematron($xml);

    $this->assertTrue($result->valid, 'Valid UBL XML should pass Schematron validation.');
    $this->assertEmpty($result->errors);
  }

  /**
   * Tests BR-01: Invoice number is required.
   *
   * @covers ::validateSchematron
   */
  public function testSchematronBr01MissingInvoiceNumber(): void {
    $xml = '<?xml version="1.0" encoding="UTF-8"?>'
      . '<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"'
      . ' xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2"'
      . ' xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2">'
      . '<cbc:IssueDate>2026-01-01</cbc:IssueDate>'
      . '<cbc:InvoiceTypeCode>380</cbc:InvoiceTypeCode>'
      . '<cbc:DocumentCurrencyCode>EUR</cbc:DocumentCurrencyCode>'
      . '</Invoice>';

    $result = $this->validator->validateSchematron($xml);
    $this->assertFalse($result->valid);
    $this->assertNotEmpty(array_filter($result->errors, fn($e) => str_contains($e, 'BR-01')));
  }

  /**
   * Tests BR-16: At least one invoice line required.
   *
   * @covers ::validateSchematron
   */
  public function testSchematronBr16MissingLines(): void {
    $xml = '<?xml version="1.0" encoding="UTF-8"?>'
      . '<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"'
      . ' xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2"'
      . ' xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2">'
      . '<cbc:ID>TEST-001</cbc:ID>'
      . '<cbc:IssueDate>2026-01-01</cbc:IssueDate>'
      . '<cbc:InvoiceTypeCode>380</cbc:InvoiceTypeCode>'
      . '<cbc:DocumentCurrencyCode>EUR</cbc:DocumentCurrencyCode>'
      . '<cac:AccountingSupplierParty><cac:Party><cac:PartyLegalEntity>'
      . '<cbc:RegistrationName>Seller</cbc:RegistrationName>'
      . '</cac:PartyLegalEntity></cac:Party></cac:AccountingSupplierParty>'
      . '<cac:AccountingCustomerParty><cac:Party><cac:PartyLegalEntity>'
      . '<cbc:RegistrationName>Buyer</cbc:RegistrationName>'
      . '</cac:PartyLegalEntity></cac:Party></cac:AccountingCustomerParty>'
      . '</Invoice>';

    $result = $this->validator->validateSchematron($xml);
    $this->assertFalse($result->valid);
    $this->assertNotEmpty(array_filter($result->errors, fn($e) => str_contains($e, 'BR-16')));
  }

  /**
   * Tests invalid XML returns errors for schematron.
   *
   * @covers ::validateSchematron
   */
  public function testSchematronInvalidXml(): void {
    $result = $this->validator->validateSchematron('not-xml');
    $this->assertFalse($result->valid);
    $this->assertSame('schematron', $result->layer);
  }

  /**
   * Tests Spanish rules ES-01: Seller VAT must be ES prefix.
   *
   * @covers ::validateSpanishRules
   */
  public function testSpanishRulesValidSellerVat(): void {
    $xml = $this->createValidUblXml();
    $result = $this->validator->validateSpanishRules($xml);
    // B12345678 is a CIF that matches format.
    $this->assertTrue($result->valid, 'Valid Spanish VAT should pass CIUS validation.');
  }

  /**
   * Tests Spanish rules with invalid seller NIF format.
   *
   * @covers ::validateSpanishRules
   */
  public function testSpanishRulesInvalidSellerVat(): void {
    $xml = str_replace('ESB12345678', 'ESINVALID!', $this->createValidUblXml());
    $result = $this->validator->validateSpanishRules($xml);
    $this->assertFalse($result->valid);
    $this->assertNotEmpty(array_filter($result->errors, fn($e) => str_contains($e, 'ES-01')));
  }

  /**
   * Tests Spanish rules ES-03: Non-EUR currency warning.
   *
   * @covers ::validateSpanishRules
   */
  public function testSpanishRulesNonEurCurrencyWarning(): void {
    $xml = str_replace('EUR', 'USD', $this->createValidUblXml());
    $result = $this->validator->validateSpanishRules($xml);
    // Warning, not error — should still be valid.
    $this->assertNotEmpty($result->warnings);
    $this->assertNotEmpty(array_filter($result->warnings, fn($w) => str_contains($w, 'ES-03')));
  }

  /**
   * Tests business rules BIZ-01: Total balance.
   *
   * @covers ::validateBusinessRules
   */
  public function testBusinessRulesValidTotals(): void {
    $xml = $this->createValidUblXml();
    $result = $this->validator->validateBusinessRules($xml);
    $this->assertTrue($result->valid, 'Valid totals should pass business rules.');
  }

  /**
   * Tests business rules BIZ-03: Negative payable.
   *
   * @covers ::validateBusinessRules
   */
  public function testBusinessRulesNegativePayable(): void {
    $xml = str_replace(
      '<cbc:PayableAmount currencyID="EUR">1210.00</cbc:PayableAmount>',
      '<cbc:PayableAmount currencyID="EUR">-50.00</cbc:PayableAmount>',
      $this->createValidUblXml(),
    );
    $result = $this->validator->validateBusinessRules($xml);
    $this->assertFalse($result->valid);
    $this->assertNotEmpty(array_filter($result->errors, fn($e) => str_contains($e, 'BIZ-03')));
  }

  /**
   * Tests NIF validation: valid DNI.
   *
   * @covers ::isValidNif
   * @dataProvider validNifProvider
   */
  public function testIsValidNif(string $nif, bool $expected): void {
    $this->assertSame($expected, $this->validator->isValidNif($nif));
  }

  /**
   * Data provider for NIF validation.
   */
  public static function validNifProvider(): array {
    return [
      'valid DNI 12345678Z' => ['12345678Z', TRUE],
      'valid DNI 00000000T' => ['00000000T', TRUE],
      'invalid DNI wrong letter' => ['12345678A', FALSE],
      'valid CIF B12345678' => ['B12345678', TRUE],
      'valid CIF A28000000' => ['A28000000', TRUE],
      'valid NIE X0000000T' => ['X0000000T', TRUE],
      'too short' => ['12345', FALSE],
      'too long' => ['1234567890A', FALSE],
      'empty string' => ['', FALSE],
    ];
  }

  /**
   * Tests XSD validation with invalid XML.
   *
   * @covers ::validateXsd
   */
  public function testXsdValidationInvalidXml(): void {
    $result = $this->validator->validateXsd('not-xml');
    $this->assertFalse($result->valid);
    $this->assertSame('xsd', $result->layer);
  }

  /**
   * Tests XSD validation passes for well-formed XML.
   *
   * @covers ::validateXsd
   */
  public function testXsdValidationWellFormedXml(): void {
    $xml = $this->createValidUblXml();
    // Without XSD files physically present, schema validation step is skipped.
    $result = $this->validator->validateXsd($xml);
    $this->assertTrue($result->valid);
    $this->assertSame('xsd', $result->layer);
  }

  /**
   * Tests full 4-layer validation.
   *
   * @covers ::validate
   */
  public function testFullValidation(): void {
    $xml = $this->createValidUblXml();
    $result = $this->validator->validate($xml, 'ubl_2.1');

    // Should pass all layers (without physical XSD files).
    $this->assertTrue($result->valid);
    $this->assertSame('complete', $result->layer);
    $this->assertEmpty($result->errors);
  }

}
