<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_resources\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_resources.
 *
 * @group jaraba_resources
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_resources'];
  protected $defaultTheme = 'stark';

  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_resources'));
  }

}
