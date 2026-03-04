<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_mobile\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_mobile.
 *
 * @group jaraba_mobile
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_mobile'];
  protected $defaultTheme = 'stark';

  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_mobile'));
  }

}
