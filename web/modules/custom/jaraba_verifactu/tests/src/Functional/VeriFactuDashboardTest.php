<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_verifactu\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests VeriFactu dashboard pages are accessible with correct permissions.
 *
 * @group jaraba_verifactu
 */
class VeriFactuDashboardTest extends BrowserTestBase {

  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'jaraba_verifactu',
  ];

  protected $defaultTheme = 'stark';

  /**
   * Tests dashboard page requires authentication.
   */
  public function testDashboardRequiresAuthentication(): void {
    $this->drupalGet('/admin/jaraba/fiscal/verifactu');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests dashboard page accessible with permission.
   */
  public function testDashboardAccessibleWithPermission(): void {
    $user = $this->drupalCreateUser(['view verifactu records']);
    $this->drupalLogin($user);

    $this->drupalGet('/admin/jaraba/fiscal/verifactu');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('VeriFactu Dashboard');
  }

  /**
   * Tests records page accessible with permission.
   */
  public function testRecordsPageAccessible(): void {
    $user = $this->drupalCreateUser(['view verifactu records']);
    $this->drupalLogin($user);

    $this->drupalGet('/admin/jaraba/fiscal/verifactu/records');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('VeriFactu Invoice Records');
  }

  /**
   * Tests remision page requires correct permission.
   */
  public function testRemisionPageRequiresPermission(): void {
    $user = $this->drupalCreateUser(['view verifactu records']);
    $this->drupalLogin($user);

    $this->drupalGet('/admin/jaraba/fiscal/verifactu/remision');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests remision page accessible with manage permission.
   */
  public function testRemisionPageAccessibleWithPermission(): void {
    $user = $this->drupalCreateUser(['manage verifactu remision']);
    $this->drupalLogin($user);

    $this->drupalGet('/admin/jaraba/fiscal/verifactu/remision');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('AEAT Remision Batches');
  }

  /**
   * Tests audit page accessible with event log permission.
   */
  public function testAuditPageAccessible(): void {
    $user = $this->drupalCreateUser(['view verifactu event log']);
    $this->drupalLogin($user);

    $this->drupalGet('/admin/jaraba/fiscal/verifactu/audit');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests settings page requires admin permission.
   */
  public function testSettingsRequiresAdminPermission(): void {
    $user = $this->drupalCreateUser(['view verifactu records']);
    $this->drupalLogin($user);

    $this->drupalGet('/admin/config/jaraba/verifactu');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests settings page accessible for admin.
   */
  public function testSettingsAccessibleForAdmin(): void {
    $user = $this->drupalCreateUser(['administer verifactu']);
    $this->drupalLogin($user);

    $this->drupalGet('/admin/config/jaraba/verifactu');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('VeriFactu Settings');
  }

}
