<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for the Fiscal Compliance Dashboard route.
 *
 * Verifies access control and response for /admin/jaraba/fiscal.
 *
 * @group ecosistema_jaraba_core
 */
class FiscalDashboardAccessTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'group',
    'ecosistema_jaraba_core',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests anonymous user cannot access fiscal dashboard.
   */
  public function testAnonymousAccess(): void {
    $this->drupalGet('/admin/jaraba/fiscal');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests user without view platform analytics cannot access.
   */
  public function testUnprivilegedUserDenied(): void {
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    $this->drupalGet('/admin/jaraba/fiscal');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests user with view platform analytics can access.
   */
  public function testPrivilegedUserAllowed(): void {
    $user = $this->drupalCreateUser(['view platform analytics']);
    $this->drupalLogin($user);

    $this->drupalGet('/admin/jaraba/fiscal');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests admin user can access fiscal dashboard.
   */
  public function testAdminCanAccess(): void {
    $admin = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($admin);

    $this->drupalGet('/admin/jaraba/fiscal');
    $this->assertSession()->statusCodeEquals(200);
  }

}
