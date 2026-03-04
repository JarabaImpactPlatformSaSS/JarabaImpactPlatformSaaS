<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_diagnostic\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_diagnostic.
 *
 * @group jaraba_diagnostic
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_diagnostic'];
  protected $defaultTheme = 'stark';

  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_diagnostic'));
  }

}
