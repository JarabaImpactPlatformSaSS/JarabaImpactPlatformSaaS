<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_einvoice_b2b\Unit;

use Drupal\jaraba_einvoice_b2b\Model\EN16931Model;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for the EN 16931 neutral semantic model.
 *
 * @group jaraba_einvoice_b2b
 * @coversDefaultClass \Drupal\jaraba_einvoice_b2b\Model\EN16931Model
 */
class EN16931ModelTest extends UnitTestCase {

  /**
   * Returns a complete data array for model creation.
   */
  protected function fullData(array $overrides = []): array {
    return array_replace_recursive([
      'invoice_number' => 'M-2026-001',
      'issue_date' => '2026-03-01',
      'invoice_type_code' => 380,
      'currency_code' => 'EUR',
      'tax_point_date' => '2026-03-01',
      'due_date' => '2026-03-31',
      'buyer_reference' => 'PO-12345',
      'project_reference' => 'PROJ-001',
      'contract_reference' => 'CONTR-001',
      'preceding_invoice_reference' => NULL,
      'seller' => [
        'name' => 'Cooperativa Olivar',
        'tax_id' => 'B12345678',
      ],
      'buyer' => [
        'name' => 'Distribuidora Norte',
        'tax_id' => 'A87654321',
      ],
      'payment_means' => ['code' => '30', 'iban' => 'ES9121000418450200051332'],
      'payment_terms' => ['note' => 'Net 30'],
      'lines' => [
        [
          'description' => 'Aceite virgen extra',
          'quantity' => '10',
          'net_amount' => '100.00',
          'price' => '10.00',
          'tax_percent' => '21.00',
        ],
      ],
      'tax_totals' => [
        ['taxable_amount' => '100.00', 'tax_amount' => '21.00', 'category_id' => 'S', 'percent' => '21.00'],
      ],
      'total_without_tax' => '100.00',
      'total_tax' => '21.00',
      'total_with_tax' => '121.00',
      'amount_due' => '121.00',
      'note' => 'Test invoice note.',
    ], $overrides);
  }

  /**
   * Tests fromArray creates a model with all properties.
   *
   * @covers ::fromArray
   */
  public function testFromArray(): void {
    $model = EN16931Model::fromArray($this->fullData());

    $this->assertSame('M-2026-001', $model->invoiceNumber);
    $this->assertSame('2026-03-01', $model->issueDate);
    $this->assertSame(380, $model->invoiceTypeCode);
    $this->assertSame('EUR', $model->currencyCode);
    $this->assertSame('2026-03-01', $model->taxPointDate);
    $this->assertSame('2026-03-31', $model->dueDate);
    $this->assertSame('PO-12345', $model->buyerReference);
    $this->assertSame('PROJ-001', $model->projectReference);
    $this->assertSame('CONTR-001', $model->contractReference);
    $this->assertNull($model->precedingInvoiceReference);
    $this->assertSame('Cooperativa Olivar', $model->seller['name']);
    $this->assertSame('Distribuidora Norte', $model->buyer['name']);
    $this->assertCount(1, $model->lines);
    $this->assertCount(1, $model->taxTotals);
    $this->assertSame('100.00', $model->totalWithoutTax);
    $this->assertSame('21.00', $model->totalTax);
    $this->assertSame('121.00', $model->totalWithTax);
    $this->assertSame('121.00', $model->amountDue);
    $this->assertSame('Test invoice note.', $model->note);
  }

  /**
   * Tests fromArray with minimal data uses defaults.
   *
   * @covers ::fromArray
   */
  public function testFromArrayMinimalDefaults(): void {
    $model = EN16931Model::fromArray([
      'invoice_number' => 'MIN-001',
      'issue_date' => '2026-01-01',
    ]);

    $this->assertSame(380, $model->invoiceTypeCode, 'Default type should be 380.');
    $this->assertSame('EUR', $model->currencyCode, 'Default currency should be EUR.');
    $this->assertNull($model->dueDate);
    $this->assertSame([], $model->seller);
    $this->assertSame([], $model->lines);
    $this->assertSame('0.00', $model->totalWithoutTax);
    $this->assertNull($model->note);
  }

  /**
   * Tests toArray roundtrip.
   *
   * @covers ::toArray
   */
  public function testToArrayRoundtrip(): void {
    $data = $this->fullData();
    $model = EN16931Model::fromArray($data);
    $exported = $model->toArray();

    $this->assertSame($data['invoice_number'], $exported['invoice_number']);
    $this->assertSame($data['issue_date'], $exported['issue_date']);
    $this->assertSame($data['invoice_type_code'], $exported['invoice_type_code']);
    $this->assertSame($data['currency_code'], $exported['currency_code']);
    $this->assertSame($data['total_without_tax'], $exported['total_without_tax']);
    $this->assertSame($data['total_tax'], $exported['total_tax']);
    $this->assertSame($data['total_with_tax'], $exported['total_with_tax']);
    $this->assertSame($data['amount_due'], $exported['amount_due']);
    $this->assertSame($data['note'], $exported['note']);
  }

  /**
   * Tests isInvoice for type 380.
   *
   * @covers ::isInvoice
   */
  public function testIsInvoice(): void {
    $model = EN16931Model::fromArray($this->fullData());
    $this->assertTrue($model->isInvoice());
    $this->assertFalse($model->isCreditNote());
  }

  /**
   * Tests isCreditNote for type 381.
   *
   * @covers ::isCreditNote
   */
  public function testIsCreditNote(): void {
    $model = EN16931Model::fromArray($this->fullData(['invoice_type_code' => 381]));
    $this->assertTrue($model->isCreditNote());
    $this->assertFalse($model->isInvoice());
  }

  /**
   * Tests validate with all required fields present.
   *
   * @covers ::validate
   */
  public function testValidateAllFieldsPresent(): void {
    $model = EN16931Model::fromArray($this->fullData());
    $errors = $model->validate();
    $this->assertEmpty($errors, 'Complete model should have no validation errors.');
  }

  /**
   * Tests validate missing invoice number.
   *
   * @covers ::validate
   */
  public function testValidateMissingInvoiceNumber(): void {
    $model = EN16931Model::fromArray($this->fullData(['invoice_number' => '']));
    $errors = $model->validate();
    $this->assertNotEmpty($errors);
    $this->assertNotEmpty(array_filter($errors, fn($e) => str_contains($e, 'BT-1')));
  }

  /**
   * Tests validate missing issue date.
   *
   * @covers ::validate
   */
  public function testValidateMissingIssueDate(): void {
    $model = EN16931Model::fromArray($this->fullData(['issue_date' => '']));
    $errors = $model->validate();
    $this->assertNotEmpty(array_filter($errors, fn($e) => str_contains($e, 'BT-2')));
  }

  /**
   * Tests validate invalid invoice type code.
   *
   * @covers ::validate
   */
  public function testValidateInvalidTypeCode(): void {
    $model = EN16931Model::fromArray($this->fullData(['invoice_type_code' => 999]));
    $errors = $model->validate();
    $this->assertNotEmpty(array_filter($errors, fn($e) => str_contains($e, 'BT-3')));
  }

  /**
   * Tests validate missing seller name.
   *
   * @covers ::validate
   */
  public function testValidateMissingSellerName(): void {
    $model = EN16931Model::fromArray($this->fullData(['seller' => ['name' => '', 'tax_id' => 'B12345678']]));
    $errors = $model->validate();
    $this->assertNotEmpty(array_filter($errors, fn($e) => str_contains($e, 'BT-27')));
  }

  /**
   * Tests validate missing lines.
   *
   * @covers ::validate
   */
  public function testValidateMissingLines(): void {
    $data = $this->fullData();
    $data['lines'] = [];
    $model = EN16931Model::fromArray($data);
    $errors = $model->validate();
    $this->assertNotEmpty(array_filter($errors, fn($e) => str_contains($e, 'BG-25')));
  }

  /**
   * Tests validate invalid currency code.
   *
   * @covers ::validate
   */
  public function testValidateInvalidCurrency(): void {
    $model = EN16931Model::fromArray($this->fullData(['currency_code' => 'X']));
    $errors = $model->validate();
    $this->assertNotEmpty(array_filter($errors, fn($e) => str_contains($e, 'BT-5')));
  }

  /**
   * Tests valid invoice type codes (380, 381, 383, 386).
   *
   * @covers ::validate
   * @dataProvider validTypeCodeProvider
   */
  public function testValidTypeCodesPass(int $code): void {
    $model = EN16931Model::fromArray($this->fullData(['invoice_type_code' => $code]));
    $errors = $model->validate();
    $typeErrors = array_filter($errors, fn($e) => str_contains($e, 'BT-3'));
    $this->assertEmpty($typeErrors, "Type code {$code} should be valid.");
  }

  /**
   * Data provider for valid invoice type codes.
   */
  public static function validTypeCodeProvider(): array {
    return [
      'invoice' => [380],
      'credit note' => [381],
      'debit note' => [383],
      'prepayment' => [386],
    ];
  }

}
