<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_tenant_export\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for the tenant export page.
 *
 * @group jaraba_tenant_export
 */
class TenantExportPageTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'group',
    'views',
    'ecosistema_jaraba_core',
    'jaraba_tenant_export',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that the export page requires authentication.
   */
  public function testExportPageRequiresPermission(): void {
    $this->drupalGet('/tenant/export');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests export page access with permission.
   */
  public function testExportPageWithPermission(): void {
    $user = $this->drupalCreateUser(['request tenant export']);
    $this->drupalLogin($user);

    $this->drupalGet('/tenant/export');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Exportar Datos del Tenant');
  }

  /**
   * Tests admin collection page.
   */
  public function testAdminCollectionPage(): void {
    $admin = $this->drupalCreateUser(['administer tenant exports']);
    $this->drupalLogin($admin);

    $this->drupalGet('/admin/content/tenant-export-records');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests settings form page.
   */
  public function testSettingsFormPage(): void {
    $admin = $this->drupalCreateUser(['administer tenant exports']);
    $this->drupalLogin($admin);

    $this->drupalGet('/admin/structure/tenant-export-record/settings');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('export_expiration_hours');
    $this->assertSession()->fieldExists('rate_limit_per_day');
    $this->assertSession()->fieldExists('max_export_size_mb');
  }

  /**
   * Tests body classes on export page.
   */
  public function testExportPageBodyClasses(): void {
    $user = $this->drupalCreateUser(['request tenant export']);
    $this->drupalLogin($user);

    $this->drupalGet('/tenant/export');
    $this->assertSession()->elementExists('css', 'body.page--tenant-export');
    $this->assertSession()->elementExists('css', 'body.full-width-layout');
  }

}
