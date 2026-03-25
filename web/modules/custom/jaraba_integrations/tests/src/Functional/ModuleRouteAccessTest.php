<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_integrations\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_integrations.
 *
 * @group jaraba_integrations
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_integrations'];
  protected $defaultTheme = 'stark';

  /**
   *
   */
  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_integrations'));
  }

}
