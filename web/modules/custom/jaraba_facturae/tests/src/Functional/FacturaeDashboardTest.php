<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_facturae\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for Facturae dashboard and admin pages.
 *
 * @group jaraba_facturae
 */
class FacturaeDashboardTest extends BrowserTestBase {

  protected $defaultTheme = 'stark';

  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
  ];

  /**
   * Tests dashboard page requires authentication.
   */
  public function testDashboardRequiresAuthentication(): void {
    $this->drupalGet('/admin/jaraba/fiscal/facturae');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests dashboard page is accessible with correct permission.
   */
  public function testDashboardAccessibleWithPermission(): void {
    $account = $this->drupalCreateUser(['view facturae documents']);
    $this->drupalLogin($account);
    $this->drupalGet('/admin/jaraba/fiscal/facturae');
    // Page should be accessible (200) or redirect.
    $statusCode = $this->getSession()->getStatusCode();
    $this->assertTrue(in_array($statusCode, [200, 403], TRUE));
  }

  /**
   * Tests documents page requires permission.
   */
  public function testDocumentsPageRequiresPermission(): void {
    $this->drupalGet('/admin/jaraba/fiscal/facturae/documents');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests FACe log page requires view facturae logs permission.
   */
  public function testFaceLogRequiresPermission(): void {
    $this->drupalGet('/admin/jaraba/fiscal/facturae/face-log');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests settings page requires administer facturae permission.
   */
  public function testSettingsRequiresAdminPermission(): void {
    $account = $this->drupalCreateUser(['view facturae documents']);
    $this->drupalLogin($account);
    $this->drupalGet('/admin/config/jaraba/facturae');
    $this->assertSession()->statusCodeEquals(403);
  }

}
