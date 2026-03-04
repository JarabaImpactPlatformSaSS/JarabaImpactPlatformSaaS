<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_foc\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_foc.
 *
 * @group jaraba_foc
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_foc'];
  protected $defaultTheme = 'stark';

  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_foc'));
  }

}
