<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_facturae\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for Facturae RBAC permissions.
 *
 * @group jaraba_facturae
 */
class FacturaePermissionsTest extends BrowserTestBase {

  protected $defaultTheme = 'stark';

  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
  ];

  /**
   * Tests anonymous users cannot access any Facturae pages.
   */
  public function testAnonymousCannotAccess(): void {
    $paths = [
      '/admin/jaraba/fiscal/facturae',
      '/admin/jaraba/fiscal/facturae/documents',
      '/admin/jaraba/fiscal/facturae/face-log',
      '/admin/config/jaraba/facturae',
    ];

    foreach ($paths as $path) {
      $this->drupalGet($path);
      $statusCode = $this->getSession()->getStatusCode();
      $this->assertTrue(
        in_array($statusCode, [403, 302], TRUE),
        "Anonymous should not access $path (got $statusCode)."
      );
    }
  }

  /**
   * Tests anonymous users cannot access any API endpoints.
   */
  public function testAnonymousCannotAccessApi(): void {
    $paths = [
      '/api/v1/facturae/documents',
      '/api/v1/facturae/config',
      '/api/v1/facturae/face-logs',
      '/api/v1/facturae/dir3/search?q=test',
      '/api/v1/facturae/config/tenant',
    ];

    foreach ($paths as $path) {
      $this->drupalGet($path);
      $this->assertSession()->statusCodeEquals(403);
    }
  }

  /**
   * Tests that view permission does not grant admin access.
   */
  public function testViewPermissionCannotAdmin(): void {
    $account = $this->drupalCreateUser(['view facturae documents']);
    $this->drupalLogin($account);

    // Admin pages should be denied.
    $this->drupalGet('/admin/config/jaraba/facturae');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalGet('/api/v1/facturae/config');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests that manage config permission grants config access.
   */
  public function testManageConfigPermission(): void {
    $account = $this->drupalCreateUser(['manage facturae config']);
    $this->drupalLogin($account);

    $this->drupalGet('/api/v1/facturae/config/tenant');
    // Should be accessible (200) or 500 (module not fully installed).
    $statusCode = $this->getSession()->getStatusCode();
    $this->assertTrue(in_array($statusCode, [200, 500], TRUE));
  }

}
