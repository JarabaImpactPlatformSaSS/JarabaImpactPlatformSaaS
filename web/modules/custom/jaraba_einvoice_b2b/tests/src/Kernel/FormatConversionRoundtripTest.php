<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_einvoice_b2b\Kernel;

use Drupal\jaraba_einvoice_b2b\Service\EInvoiceFormatConverterService;
use Drupal\jaraba_einvoice_b2b\Service\EInvoiceUblService;
use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel tests for format conversion roundtrip via container services.
 *
 * @group jaraba_einvoice_b2b
 */
class FormatConversionRoundtripTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'options',
    'datetime',
    'flexible_permissions',
    'group',
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
   * Tests UBL -> Facturae -> UBL roundtrip preserves invoice number.
   */
  public function testUblToFacturaeToUblRoundtrip(): void {
    /** @var \Drupal\jaraba_einvoice_b2b\Service\EInvoiceUblService $ublService */
    $ublService = $this->container->get('jaraba_einvoice_b2b.ubl_service');

    /** @var \Drupal\jaraba_einvoice_b2b\Service\EInvoiceFormatConverterService $converter */
    $converter = $this->container->get('jaraba_einvoice_b2b.format_converter');

    $model = \Drupal\jaraba_einvoice_b2b\Model\EN16931Model::fromArray([
      'invoice_number' => 'RT-2026-001',
      'issue_date' => '2026-01-20',
      'invoice_type_code' => 380,
      'currency_code' => 'EUR',
      'seller' => ['name' => 'Seller RT', 'tax_id' => 'B11111111'],
      'buyer' => ['name' => 'Buyer RT', 'tax_id' => 'A22222222'],
      'lines' => [
        ['description' => 'Product RT', 'quantity' => '5', 'net_amount' => '50.00', 'price' => '10.00', 'tax_percent' => '21.00'],
      ],
      'tax_totals' => [
        ['taxable_amount' => '50.00', 'tax_amount' => '10.50', 'category_id' => 'S', 'percent' => '21.00'],
      ],
      'total_without_tax' => '50.00',
      'total_tax' => '10.50',
      'total_with_tax' => '60.50',
      'amount_due' => '60.50',
    ]);

    // Step 1: Generate UBL.
    $ublXml = $ublService->generateFromModel($model);
    $this->assertSame('ubl_2.1', $converter->detectFormat($ublXml));

    // Step 2: Convert to Facturae.
    $facturaeXml = $converter->convertTo($ublXml, 'facturae_3.2.2');
    $this->assertSame('facturae_3.2.2', $converter->detectFormat($facturaeXml));

    // Step 3: Convert back to UBL.
    $roundtripUbl = $converter->convertTo($facturaeXml, 'ubl_2.1');
    $this->assertSame('ubl_2.1', $converter->detectFormat($roundtripUbl));

    // Step 4: Parse roundtrip and verify key data.
    $roundtripModel = $converter->toNeutralModel($roundtripUbl);
    $this->assertSame('RT-2026-001', $roundtripModel->invoiceNumber);
    $this->assertSame('2026-01-20', $roundtripModel->issueDate);
    $this->assertSame('EUR', $roundtripModel->currencyCode);
    $this->assertCount(1, $roundtripModel->lines);
  }

  /**
   * Tests format detection with container-instantiated service.
   */
  public function testFormatDetectionViaContainer(): void {
    $converter = $this->container->get('jaraba_einvoice_b2b.format_converter');

    $this->assertSame('unknown', $converter->detectFormat('<root/>'));
    $this->assertSame('unknown', $converter->detectFormat('invalid'));
  }

}
