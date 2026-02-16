<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_facturae\Unit;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\jaraba_facturae\Service\FacturaeValidationService;
use Drupal\jaraba_facturae\ValueObject\ValidationResult;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests the FacturaeValidationService multi-layer validation.
 *
 * @group jaraba_facturae
 * @coversDefaultClass \Drupal\jaraba_facturae\Service\FacturaeValidationService
 */
class FacturaeValidationServiceTest extends UnitTestCase {

  protected FacturaeValidationService $service;
  protected ModuleHandlerInterface $moduleHandler;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new FacturaeValidationService(
      $this->moduleHandler,
      $this->logger,
    );
  }

  // =========================================================================
  // NIF Validation.
  // =========================================================================

  /**
   * @covers ::validateNif
   * @dataProvider validNifProvider
   */
  public function testValidNifs(string $nif): void {
    $this->assertTrue($this->service->validateNif($nif), "Expected NIF '$nif' to be valid.");
  }

  /**
   * Provides valid NIF/CIF/NIE values.
   */
  public static function validNifProvider(): array {
    return [
      // NIF personal (DNI).
      'DNI 12345678Z' => ['12345678Z'],
      'DNI 00000000T' => ['00000000T'],
      'DNI 99999999R' => ['99999999R'],
      // NIE.
      'NIE X0000000T' => ['X0000000T'],
      'NIE Y0000000Z' => ['Y0000000Z'],
      'NIE Z0000000M' => ['Z0000000M'],
      // CIF.
      'CIF A28015865' => ['A28015865'],
      'CIF B86596045' => ['B86596045'],
      'CIF Q2826000H' => ['Q2826000H'],
      'CIF P1234567A' => ['P1234567A'],
    ];
  }

  /**
   * @covers ::validateNif
   * @dataProvider invalidNifProvider
   */
  public function testInvalidNifs(string $nif): void {
    $this->assertFalse($this->service->validateNif($nif), "Expected NIF '$nif' to be invalid.");
  }

  /**
   * Provides invalid NIF/CIF/NIE values.
   */
  public static function invalidNifProvider(): array {
    return [
      'empty' => [''],
      'too short' => ['1234'],
      'too long' => ['12345678901'],
      'wrong letter DNI' => ['12345678A'],
      'wrong letter NIE' => ['X0000000A'],
      'invalid prefix' => ['I12345678'],
      'all letters' => ['ABCDEFGHI'],
    ];
  }

  /**
   * @covers ::validateNif
   */
  public function testNifIsCaseInsensitive(): void {
    // 12345678Z is valid; test lowercase.
    $this->assertTrue($this->service->validateNif('12345678z'));
    $this->assertTrue($this->service->validateNif('x0000000t'));
  }

  /**
   * @covers ::validateNif
   */
  public function testNifTrimsWhitespace(): void {
    $this->assertTrue($this->service->validateNif(' 12345678Z '));
  }

  // =========================================================================
  // IBAN Validation.
  // =========================================================================

  /**
   * @covers ::validateIban
   * @dataProvider validIbanProvider
   */
  public function testValidIbans(string $iban): void {
    $this->assertTrue($this->service->validateIban($iban), "Expected IBAN '$iban' to be valid.");
  }

  /**
   * Provides valid IBAN values.
   */
  public static function validIbanProvider(): array {
    return [
      'Spain' => ['ES9121000418450200051332'],
      'Spain with spaces' => ['ES91 2100 0418 4502 0005 1332'],
      'Germany' => ['DE89370400440532013000'],
      'UK' => ['GB29NWBK60161331926819'],
      'France' => ['FR7630006000011234567890189'],
    ];
  }

  /**
   * @covers ::validateIban
   * @dataProvider invalidIbanProvider
   */
  public function testInvalidIbans(string $iban): void {
    $this->assertFalse($this->service->validateIban($iban), "Expected IBAN '$iban' to be invalid.");
  }

  /**
   * Provides invalid IBAN values.
   */
  public static function invalidIbanProvider(): array {
    return [
      'empty' => [''],
      'too short' => ['ES12'],
      'wrong checksum' => ['ES0021000418450200051332'],
      'no country code' => ['12345678901234567890'],
      'numeric country' => ['12345678901234567890ABCD'],
    ];
  }

  // =========================================================================
  // DIR3 Validation.
  // =========================================================================

  /**
   * @covers ::validateDir3
   * @dataProvider validDir3Provider
   */
  public function testValidDir3Codes(string $code): void {
    $this->assertTrue($this->service->validateDir3($code), "Expected DIR3 code '$code' to be valid.");
  }

  /**
   * Provides valid DIR3 codes.
   */
  public static function validDir3Provider(): array {
    return [
      'standard' => ['L01234567'],
      'EA prefix' => ['EA0012345'],
      'longer code' => ['LA0000050'],
      'all caps alphanumeric' => ['P00200010'],
    ];
  }

  /**
   * @covers ::validateDir3
   * @dataProvider invalidDir3Provider
   */
  public function testInvalidDir3Codes(string $code): void {
    $this->assertFalse($this->service->validateDir3($code), "Expected DIR3 code '$code' to be invalid.");
  }

  /**
   * Provides invalid DIR3 codes.
   */
  public static function invalidDir3Provider(): array {
    return [
      'empty' => [''],
      'too short' => ['L1'],
      'starts with digit' => ['12345'],
      'has special chars' => ['L123-456'],
      'has spaces' => ['L12 345'],
    ];
  }

  // =========================================================================
  // XSD Validation.
  // =========================================================================

  /**
   * @covers ::validateXsd
   */
  public function testValidateXsdReturnsErrorOnEmptyXml(): void {
    $result = $this->service->validateXsd('');

    $this->assertInstanceOf(ValidationResult::class, $result);
    $this->assertFalse($result->valid);
    $this->assertNotEmpty($result->errors);
    $this->assertStringContainsString('empty', $result->errors[0]);
  }

  /**
   * @covers ::validateXsd
   */
  public function testValidateXsdReturnsErrorOnInvalidXml(): void {
    $result = $this->service->validateXsd('not-valid-xml<<<');

    $this->assertFalse($result->valid);
    $this->assertNotEmpty($result->errors);
    $this->assertStringContainsString('XML parse error', $result->errors[0]);
  }

  /**
   * @covers ::validateXsd
   */
  public function testValidateXsdPassesWithValidXmlWithoutXsdFile(): void {
    $module = $this->createMock(\Drupal\Core\Extension\Extension::class);
    $module->method('getPath')->willReturn('/nonexistent/path');
    $this->moduleHandler->method('getModule')
      ->with('jaraba_facturae')
      ->willReturn($module);

    $xml = '<?xml version="1.0" encoding="UTF-8"?>'
      . '<Facturae xmlns="http://www.facturae.gob.es/formato/Versiones/Facturaev3_2_2.xml">'
      . '<FileHeader><SchemaVersion>3.2.2</SchemaVersion></FileHeader>'
      . '</Facturae>';

    $result = $this->service->validateXsd($xml);

    // Without the XSD file present, it should pass (only checks XML well-formedness).
    $this->assertTrue($result->valid);
    $this->assertEmpty($result->errors);
  }

  // =========================================================================
  // Amount Validation.
  // =========================================================================

  /**
   * @covers ::validateAmounts
   */
  public function testValidateAmountsPassesWithConsistentData(): void {
    $data = [
      'total_gross_amount_before_taxes' => 1000.00,
      'total_tax_outputs' => 210.00,
      'total_tax_withheld' => 150.00,
      'total_invoice_amount' => 1060.00,
      'total_outstanding' => 1060.00,
      'total_executable' => 1060.00,
    ];

    $result = $this->service->validateAmounts($data);

    $this->assertInstanceOf(ValidationResult::class, $result);
    $this->assertTrue($result->valid);
    $this->assertEmpty($result->errors);
  }

  /**
   * @covers ::validateAmounts
   */
  public function testValidateAmountsDetectsInconsistentTotal(): void {
    $data = [
      'total_gross_amount_before_taxes' => 1000.00,
      'total_tax_outputs' => 210.00,
      'total_tax_withheld' => 150.00,
      'total_invoice_amount' => 999.00, // Should be 1060.
      'total_outstanding' => 999.00,
      'total_executable' => 999.00,
    ];

    $result = $this->service->validateAmounts($data);

    $this->assertFalse($result->valid);
    $this->assertNotEmpty($result->errors);
    $this->assertStringContainsString('does not match expected', $result->errors[0]);
  }

  /**
   * @covers ::validateAmounts
   */
  public function testValidateAmountsDetectsOutstandingExceedsTotal(): void {
    $data = [
      'total_gross_amount_before_taxes' => 1000.00,
      'total_tax_outputs' => 210.00,
      'total_tax_withheld' => 0.00,
      'total_invoice_amount' => 1210.00,
      'total_outstanding' => 1500.00, // Exceeds total.
      'total_executable' => 1500.00,
    ];

    $result = $this->service->validateAmounts($data);

    $this->assertFalse($result->valid);
    $this->assertNotEmpty($result->errors);
    $this->assertStringContainsString('Outstanding', $result->errors[0]);
  }

  /**
   * @covers ::validateAmounts
   */
  public function testValidateAmountsDetectsExecutableExceedsOutstanding(): void {
    $data = [
      'total_gross_amount_before_taxes' => 1000.00,
      'total_tax_outputs' => 210.00,
      'total_tax_withheld' => 0.00,
      'total_invoice_amount' => 1210.00,
      'total_outstanding' => 1000.00,
      'total_executable' => 1100.00, // Exceeds outstanding.
    ];

    $result = $this->service->validateAmounts($data);

    $this->assertFalse($result->valid);
    $this->assertNotEmpty($result->errors);
    $this->assertStringContainsString('Executable', $result->errors[0]);
  }

  /**
   * @covers ::validateAmounts
   */
  public function testValidateAmountsToleratesSmallDifferences(): void {
    $data = [
      'total_gross_amount_before_taxes' => 100.00,
      'total_tax_outputs' => 21.00,
      'total_tax_withheld' => 0.00,
      'total_invoice_amount' => 121.005, // Within 0.01 tolerance.
      'total_outstanding' => 121.005,
      'total_executable' => 121.005,
    ];

    $result = $this->service->validateAmounts($data);

    $this->assertTrue($result->valid);
  }

  // =========================================================================
  // ValidationResult Value Object.
  // =========================================================================

  /**
   * Tests ValidationResult::success factory.
   *
   * @covers \Drupal\jaraba_facturae\ValueObject\ValidationResult
   */
  public function testValidationResultSuccess(): void {
    $result = ValidationResult::success();

    $this->assertTrue($result->valid);
    $this->assertEmpty($result->errors);
  }

  /**
   * Tests ValidationResult::failure factory.
   *
   * @covers \Drupal\jaraba_facturae\ValueObject\ValidationResult
   */
  public function testValidationResultFailure(): void {
    $errors = ['Error 1', 'Error 2'];
    $result = ValidationResult::failure($errors);

    $this->assertFalse($result->valid);
    $this->assertCount(2, $result->errors);
    $this->assertEquals('Error 1', $result->errors[0]);
  }

  /**
   * Tests ValidationResult::toArray structure.
   *
   * @covers \Drupal\jaraba_facturae\ValueObject\ValidationResult
   */
  public function testValidationResultToArray(): void {
    $result = new ValidationResult(FALSE, ['Some error']);
    $array = $result->toArray();

    $this->assertFalse($array['valid']);
    $this->assertCount(1, $array['errors']);
    $this->assertEquals('Some error', $array['errors'][0]);
  }

}
