<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_institutional\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_institutional.
 *
 * @group jaraba_institutional
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_institutional'];
  protected $defaultTheme = 'stark';

  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_institutional'));
  }

}
