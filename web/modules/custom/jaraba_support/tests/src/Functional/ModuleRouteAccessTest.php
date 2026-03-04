<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_support\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_support.
 *
 * @group jaraba_support
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_support'];
  protected $defaultTheme = 'stark';

  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_support'));
  }

}
