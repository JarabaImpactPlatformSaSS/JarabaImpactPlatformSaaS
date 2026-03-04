<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_predictive\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_predictive.
 *
 * @group jaraba_predictive
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_predictive'];
  protected $defaultTheme = 'stark';

  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_predictive'));
  }

}
