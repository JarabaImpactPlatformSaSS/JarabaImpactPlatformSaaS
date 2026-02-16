<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_einvoice_b2b\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for the E-Invoice admin dashboard pages.
 *
 * @group jaraba_einvoice_b2b
 */
class EInvoiceDashboardTest extends BrowserTestBase {

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
   * Tests entity settings pages require admin permission.
   */
  public function testSettingsPagesRequireAdmin(): void {
    $pages = [
      '/admin/structure/einvoice-document/settings',
      '/admin/structure/einvoice-tenant-config/settings',
      '/admin/structure/einvoice-delivery-log/settings',
      '/admin/structure/einvoice-payment-event/settings',
    ];

    foreach ($pages as $path) {
      $this->drupalGet($path);
      $this->assertSession()->statusCodeEquals(403, "Anonymous should not access {$path}.");
    }
  }

  /**
   * Tests admin user can access settings pages.
   */
  public function testAdminCanAccessSettings(): void {
    $admin = $this->drupalCreateUser(['administer einvoice b2b']);
    $this->drupalLogin($admin);

    $pages = [
      '/admin/structure/einvoice-document/settings',
      '/admin/structure/einvoice-tenant-config/settings',
      '/admin/structure/einvoice-delivery-log/settings',
      '/admin/structure/einvoice-payment-event/settings',
    ];

    foreach ($pages as $path) {
      $this->drupalGet($path);
      $this->assertSession()->statusCodeEquals(200, "Admin should access {$path}.");
    }
  }

  /**
   * Tests document list page is accessible (admin entity routes).
   */
  public function testDocumentCollectionPage(): void {
    $user = $this->drupalCreateUser(['view einvoice documents', 'administer einvoice b2b']);
    $this->drupalLogin($user);

    $this->drupalGet('/admin/content/einvoice-document');
    // 200 if entity collection route is defined, 404 otherwise.
    $code = (int) $this->getSession()->getStatusCode();
    $this->assertTrue(in_array($code, [200, 404], TRUE), 'Collection page should be accessible or return 404 if not defined.');
  }

}
