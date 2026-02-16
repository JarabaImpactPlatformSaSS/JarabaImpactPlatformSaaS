<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_facturae\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for Facturae REST API endpoints.
 *
 * @group jaraba_facturae
 */
class FacturaeApiEndpointsTest extends BrowserTestBase {

  protected $defaultTheme = 'stark';

  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
  ];

  /**
   * Tests documents API requires authentication.
   */
  public function testDocumentsApiRequiresAuth(): void {
    $this->drupalGet('/api/v1/facturae/documents');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests config API requires admin permission.
   */
  public function testConfigApiRequiresAdmin(): void {
    $account = $this->drupalCreateUser(['view facturae documents']);
    $this->drupalLogin($account);
    $this->drupalGet('/api/v1/facturae/config');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests DIR3 search API requires permission.
   */
  public function testDir3SearchRequiresPermission(): void {
    $this->drupalGet('/api/v1/facturae/dir3/search?q=test');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests face logs API requires view facturae logs permission.
   */
  public function testFaceLogsRequiresPermission(): void {
    $this->drupalGet('/api/v1/facturae/face-logs');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests certificate status API requires admin permission.
   */
  public function testCertificateStatusRequiresAdmin(): void {
    $account = $this->drupalCreateUser(['view facturae documents']);
    $this->drupalLogin($account);
    $this->drupalGet('/api/v1/facturae/config/certificate/status');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests tenant config API requires manage config permission.
   */
  public function testTenantConfigRequiresManagePermission(): void {
    $account = $this->drupalCreateUser(['view facturae documents']);
    $this->drupalLogin($account);
    $this->drupalGet('/api/v1/facturae/config/tenant');
    $this->assertSession()->statusCodeEquals(403);
  }

}
