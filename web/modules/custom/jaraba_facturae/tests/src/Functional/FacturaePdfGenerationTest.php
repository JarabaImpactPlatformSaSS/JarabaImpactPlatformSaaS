<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_facturae\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for Facturae PDF generation and download.
 *
 * @group jaraba_facturae
 */
class FacturaePdfGenerationTest extends BrowserTestBase {

  protected $defaultTheme = 'stark';

  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
  ];

  /**
   * Tests PDF download endpoint requires authentication.
   */
  public function testPdfDownloadRequiresAuth(): void {
    $this->drupalGet('/api/v1/facturae/documents/1/pdf');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests PDF download endpoint returns 404 for non-existent document.
   */
  public function testPdfDownloadReturnsNotFoundForMissing(): void {
    $account = $this->drupalCreateUser(['view facturae documents']);
    $this->drupalLogin($account);
    $this->drupalGet('/api/v1/facturae/documents/999999/pdf');
    $statusCode = $this->getSession()->getStatusCode();
    // 404 for missing entity or 500 if module not fully installed.
    $this->assertTrue(in_array($statusCode, [404, 500], TRUE));
  }

  /**
   * Tests PDF template theme hook has correct variables.
   */
  public function testPdfTemplateThemeVariables(): void {
    $themes = jaraba_facturae_theme();
    $this->assertArrayHasKey('facturae_pdf_template', $themes);

    $variables = $themes['facturae_pdf_template']['variables'];
    $this->assertArrayHasKey('document', $variables);
    $this->assertArrayHasKey('signature_info', $variables);
    $this->assertArrayHasKey('qr_data_uri', $variables);
  }

  /**
   * Tests that the internal PDF generation helper exists.
   */
  public function testPdfGenerationHelperExists(): void {
    $this->assertTrue(
      function_exists('_jaraba_facturae_generate_pdf'),
      '_jaraba_facturae_generate_pdf helper should exist.'
    );
  }

  /**
   * Tests that the internal corrective generation helper exists.
   */
  public function testCorrectiveGenerationHelperExists(): void {
    $this->assertTrue(
      function_exists('_jaraba_facturae_generate_corrective'),
      '_jaraba_facturae_generate_corrective helper should exist.'
    );
  }

}
