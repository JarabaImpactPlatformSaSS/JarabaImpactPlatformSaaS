<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_addons\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests addon routes are accessible and return expected responses.
 *
 * @group jaraba_addons
 */
class AddonRoutesTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'jaraba_addons',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that the addon catalog page returns 200 for admin.
   */
  public function testAddonCatalogRouteAccessible(): void {
    $admin = $this->drupalCreateUser([
      'administer jaraba addons',
    ]);
    $this->drupalLogin($admin);
    $this->drupalGet('/admin/structure/jaraba-addons/addon');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests anonymous users cannot access addon admin routes.
   */
  public function testAddonCatalogRouteAnonymousDenied(): void {
    $this->drupalGet('/admin/structure/jaraba-addons/addon');
    $this->assertSession()->statusCodeEquals(403);
  }

}
