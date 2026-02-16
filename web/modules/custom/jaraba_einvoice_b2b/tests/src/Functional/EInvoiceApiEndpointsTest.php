<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_einvoice_b2b\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for E-Invoice API endpoints.
 *
 * Tests access control and response structure for all 14 API routes.
 *
 * @group jaraba_einvoice_b2b
 */
class EInvoiceApiEndpointsTest extends BrowserTestBase {

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
  protected $defaultTheme = 'stark';

  /**
   * Tests anonymous users cannot access document list API.
   */
  public function testAnonymousCannotAccessDocuments(): void {
    $this->drupalGet('/api/v1/einvoice/documents');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests anonymous users cannot access dashboard API.
   */
  public function testAnonymousCannotAccessDashboard(): void {
    $this->drupalGet('/api/v1/einvoice/dashboard');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests authenticated user with view permission can access documents.
   */
  public function testViewDocumentsPermission(): void {
    $user = $this->drupalCreateUser(['view einvoice documents']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/einvoice/documents');
    $this->assertSession()->statusCodeEquals(200);

    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertArrayHasKey('success', $response);
    $this->assertTrue($response['success']);
    $this->assertArrayHasKey('data', $response);
    $this->assertArrayHasKey('meta', $response);
  }

  /**
   * Tests document detail API with non-existent document.
   */
  public function testDocumentDetailNotFound(): void {
    $user = $this->drupalCreateUser(['view einvoice documents']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/einvoice/documents/99999');
    $this->assertSession()->statusCodeEquals(404);

    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertFalse($response['success']);
  }

  /**
   * Tests dashboard API returns proper structure.
   */
  public function testDashboardApiResponse(): void {
    $user = $this->drupalCreateUser(['view einvoice documents']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/einvoice/dashboard');
    $this->assertSession()->statusCodeEquals(200);

    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertTrue($response['success']);
    $this->assertArrayHasKey('total_documents', $response['data']);
    $this->assertArrayHasKey('outbound', $response['data']);
    $this->assertArrayHasKey('inbound', $response['data']);
    $this->assertArrayHasKey('overdue', $response['data']);
  }

  /**
   * Tests validate API requires view permission.
   */
  public function testValidateApiRequiresPermission(): void {
    $this->drupalGet('/api/v1/einvoice/validate');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests morosity report API requires view einvoice reports.
   */
  public function testMorosityReportRequiresPermission(): void {
    // User with only view documents but NOT view reports.
    $user = $this->drupalCreateUser(['view einvoice documents']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/einvoice/payment/morosity-report');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests morosity report accessible with correct permission.
   */
  public function testMorosityReportAccessible(): void {
    $user = $this->drupalCreateUser(['view einvoice reports']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/einvoice/payment/morosity-report?tenant_id=1');
    $this->assertSession()->statusCodeEquals(200);

    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertTrue($response['success']);
    $this->assertArrayHasKey('data', $response);
  }

  /**
   * Tests overdue API requires tenant_id parameter.
   */
  public function testOverdueApiRequiresTenantId(): void {
    $user = $this->drupalCreateUser(['view einvoice documents']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/einvoice/payment/overdue');
    $this->assertSession()->statusCodeEquals(400);

    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertFalse($response['success']);
    $this->assertStringContainsString('tenant_id', $response['meta']['error']);
  }

  /**
   * Tests payment history API.
   */
  public function testPaymentHistoryApi(): void {
    $user = $this->drupalCreateUser(['view einvoice documents']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/einvoice/payment/999/history');
    $this->assertSession()->statusCodeEquals(200);

    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertTrue($response['success']);
    $this->assertSame(0, $response['meta']['count']);
  }

}
