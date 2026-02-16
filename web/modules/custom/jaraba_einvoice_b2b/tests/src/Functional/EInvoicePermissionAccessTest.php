<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_einvoice_b2b\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for permission-based access control across all routes.
 *
 * Systematically verifies that each permission gates the correct routes.
 *
 * @group jaraba_einvoice_b2b
 */
class EInvoicePermissionAccessTest extends BrowserTestBase {

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
   * Tests view einvoice documents permission grants GET access.
   */
  public function testViewDocumentsPermission(): void {
    $user = $this->drupalCreateUser(['view einvoice documents']);
    $this->drupalLogin($user);

    // These should all be 200.
    $this->drupalGet('/api/v1/einvoice/documents');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('/api/v1/einvoice/dashboard');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('/api/v1/einvoice/payment/1/history');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('/api/v1/einvoice/payment/overdue?tenant_id=1');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests view einvoice documents does NOT grant morosity report.
   */
  public function testViewDocumentsDoesNotGrantReports(): void {
    $user = $this->drupalCreateUser(['view einvoice documents']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/einvoice/payment/morosity-report?tenant_id=1');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests view einvoice reports grants morosity report access.
   */
  public function testViewReportsPermission(): void {
    $user = $this->drupalCreateUser(['view einvoice reports']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/einvoice/payment/morosity-report?tenant_id=1');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests administer einvoice b2b grants settings access.
   */
  public function testAdminPermissionGrantsSettings(): void {
    $user = $this->drupalCreateUser(['administer einvoice b2b']);
    $this->drupalLogin($user);

    $this->drupalGet('/admin/structure/einvoice-document/settings');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests non-admin cannot access settings.
   */
  public function testNonAdminCannotAccessSettings(): void {
    $user = $this->drupalCreateUser(['view einvoice documents']);
    $this->drupalLogin($user);

    $this->drupalGet('/admin/structure/einvoice-document/settings');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests combined permissions for a typical tenant administrator.
   */
  public function testTenantAdminCombinedPermissions(): void {
    $user = $this->drupalCreateUser([
      'view einvoice documents',
      'create einvoice documents',
      'send einvoice',
      'manage einvoice payment events',
      'view einvoice reports',
    ]);
    $this->drupalLogin($user);

    // Document list.
    $this->drupalGet('/api/v1/einvoice/documents');
    $this->assertSession()->statusCodeEquals(200);

    // Dashboard.
    $this->drupalGet('/api/v1/einvoice/dashboard');
    $this->assertSession()->statusCodeEquals(200);

    // Morosity report.
    $this->drupalGet('/api/v1/einvoice/payment/morosity-report?tenant_id=1');
    $this->assertSession()->statusCodeEquals(200);
  }

}
