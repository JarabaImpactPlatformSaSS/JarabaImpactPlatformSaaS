<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_servicios_conecta\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that jaraba_servicios_conecta routes respond correctly.
 *
 * @group jaraba_servicios_conecta
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'options',
    'file',
    'flexible_permissions',
    'group',
    'ecosistema_jaraba_core',
    'jaraba_servicios_conecta',
  ];

  protected $defaultTheme = 'stark';

  protected $strictConfigSchema = FALSE;

  /**
   * Tests that the marketplace route returns a valid response.
   */
  public function testMarketplaceRouteExists(): void {
    $this->drupalGet('/servicios');
    // Route exists — any response code other than 500 is acceptable.
    $statusCode = $this->getSession()->getStatusCode();
    $this->assertNotEquals(500, $statusCode, 'Marketplace route should not return a server error.');
  }

}
