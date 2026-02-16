<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_einvoice_b2b\Kernel;

use Drupal\jaraba_einvoice_b2b\Model\EN16931Model;
use Drupal\jaraba_einvoice_b2b\Service\EInvoiceUblService;
use Drupal\jaraba_einvoice_b2b\Service\EInvoiceValidationService;
use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel tests for EN 16931 validation with real services.
 *
 * Tests the complete validation pipeline using container-instantiated services.
 *
 * @group jaraba_einvoice_b2b
 */
class EN16931ValidationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'jaraba_einvoice_b2b',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installConfig(['jaraba_einvoice_b2b']);
  }

  /**
   * Tests full validation of UBL generated from model.
   */
  public function testValidateGeneratedUbl(): void {
    /** @var \Drupal\jaraba_einvoice_b2b\Service\EInvoiceUblService $ublService */
    $ublService = $this->container->get('jaraba_einvoice_b2b.ubl_service');

    /** @var \Drupal\jaraba_einvoice_b2b\Service\EInvoiceValidationService $validationService */
    $validationService = $this->container->get('jaraba_einvoice_b2b.validation_service');

    $model = EN16931Model::fromArray([
      'invoice_number' => 'KV-001',
      'issue_date' => '2026-01-15',
      'invoice_type_code' => 380,
      'currency_code' => 'EUR',
      'seller' => ['name' => 'Seller KV', 'tax_id' => 'B12345678'],
      'buyer' => ['name' => 'Buyer KV', 'tax_id' => 'A87654321'],
      'lines' => [
        ['description' => 'Item KV', 'quantity' => '1', 'net_amount' => '100.00', 'price' => '100.00', 'tax_percent' => '21.00'],
      ],
      'tax_totals' => [
        ['taxable_amount' => '100.00', 'tax_amount' => '21.00', 'category_id' => 'S', 'percent' => '21.00'],
      ],
      'total_without_tax' => '100.00',
      'total_tax' => '21.00',
      'total_with_tax' => '121.00',
      'amount_due' => '121.00',
    ]);

    $xml = $ublService->generateFromModel($model);
    $result = $validationService->validate($xml, 'ubl_2.1');

    $this->assertTrue($result->valid, 'Generated UBL must pass all validation layers. Errors: ' . implode(', ', $result->errors));
    $this->assertSame('complete', $result->layer);
  }

  /**
   * Tests model validation catches missing required fields.
   */
  public function testModelValidationErrors(): void {
    $model = EN16931Model::fromArray([
      'invoice_number' => '',
      'issue_date' => '',
      'invoice_type_code' => 999,
      'currency_code' => 'X',
      'seller' => [],
      'buyer' => [],
      'lines' => [],
    ]);

    $errors = $model->validate();
    $this->assertNotEmpty($errors);

    // Verify all expected BT errors.
    $errorString = implode(' | ', $errors);
    $this->assertStringContainsString('BT-1', $errorString);
    $this->assertStringContainsString('BT-2', $errorString);
    $this->assertStringContainsString('BT-3', $errorString);
    $this->assertStringContainsString('BT-5', $errorString);
    $this->assertStringContainsString('BT-27', $errorString);
    $this->assertStringContainsString('BT-44', $errorString);
    $this->assertStringContainsString('BG-25', $errorString);
  }

  /**
   * Tests Schematron rules with container service.
   */
  public function testSchematronValidationViaContainer(): void {
    $validationService = $this->container->get('jaraba_einvoice_b2b.validation_service');

    // Minimal invalid UBL.
    $xml = '<?xml version="1.0" encoding="UTF-8"?>'
      . '<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"'
      . ' xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2"'
      . ' xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2">'
      . '</Invoice>';

    $result = $validationService->validateSchematron($xml);
    $this->assertFalse($result->valid);
    // Should flag missing ID, IssueDate, etc.
    $this->assertGreaterThan(0, count($result->errors));
  }

}
