<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_self_discovery\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_self_discovery.
 *
 * @group jaraba_self_discovery
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_self_discovery'];
  protected $defaultTheme = 'stark';

  /**
   *
   */
  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_self_discovery'));
  }

}
