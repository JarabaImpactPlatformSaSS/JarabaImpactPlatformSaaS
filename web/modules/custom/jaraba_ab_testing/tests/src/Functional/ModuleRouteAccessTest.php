<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ab_testing\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_ab_testing.
 *
 * @group jaraba_ab_testing
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_ab_testing'];
  protected $defaultTheme = 'stark';

  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_ab_testing'));
  }

}
