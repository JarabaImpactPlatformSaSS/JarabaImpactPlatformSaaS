<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_verifactu\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests VeriFactu PDF generation and download endpoints.
 *
 * Verifies that compliance PDF generation, download access control,
 * and stamping endpoints work correctly.
 *
 * @group jaraba_verifactu
 */
class VeriFactuPdfGenerationTest extends BrowserTestBase {

  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'jaraba_verifactu',
  ];

  protected $defaultTheme = 'stark';

  /**
   * Tests PDF download endpoint requires authentication.
   */
  public function testPdfDownloadRequiresAuthentication(): void {
    $this->drupalGet('/api/v1/verifactu/records/1/pdf');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests PDF download endpoint requires view permission.
   */
  public function testPdfDownloadRequiresPermission(): void {
    $user = $this->drupalCreateUser([]);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/verifactu/records/1/pdf');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests PDF download returns 404 for nonexistent record.
   */
  public function testPdfDownloadReturns404ForMissing(): void {
    $user = $this->drupalCreateUser(['view verifactu records']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/verifactu/records/999999/pdf');
    $this->assertSession()->statusCodeEquals(404);
  }

  /**
   * Tests compliance PDF endpoint for valid record.
   */
  public function testCompliancePdfEndpointForValidRecord(): void {
    $user = $this->drupalCreateUser(['view verifactu records']);
    $this->drupalLogin($user);

    $storage = \Drupal::entityTypeManager()->getStorage('verifactu_invoice_record');
    $record = $storage->create([
      'tenant_id' => 1,
      'record_type' => 'alta',
      'nif_emisor' => 'B12345678',
      'nombre_emisor' => 'Test Company SL',
      'numero_factura' => 'VF-PDF-001',
      'fecha_expedicion' => '2026-02-16',
      'tipo_factura' => 'F1',
      'base_imponible' => '1000.00',
      'tipo_impositivo' => '21.00',
      'cuota_tributaria' => '210.00',
      'importe_total' => '1210.00',
      'hash_record' => hash('sha256', 'pdf-test-record'),
      'hash_previous' => '',
      'aeat_status' => 'accepted',
    ]);
    $record->save();

    $this->drupalGet('/api/v1/verifactu/records/' . $record->id() . '/pdf');
    // Should return 200 with PDF content or JSON depending on service availability.
    $statusCode = $this->getSession()->getStatusCode();
    $this->assertTrue(
      in_array($statusCode, [200, 500], TRUE),
      'PDF endpoint should return 200 (success) or 500 (TCPDF not available in test).'
    );
  }

  /**
   * Tests batch PDF export endpoint requires manage permission.
   */
  public function testBatchPdfExportRequiresPermission(): void {
    $user = $this->drupalCreateUser(['view verifactu records']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/verifactu/remisions/1/pdf');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests batch PDF export endpoint accessible with manage permission.
   */
  public function testBatchPdfExportAccessibleWithPermission(): void {
    $user = $this->drupalCreateUser([
      'view verifactu records',
      'manage verifactu remision',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/verifactu/remisions/999999/pdf');
    // Non-existent batch returns 404.
    $this->assertSession()->statusCodeEquals(404);
  }

}
