<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_interactive\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_interactive.
 *
 * @group jaraba_interactive
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_interactive'];
  protected $defaultTheme = 'stark';

  /**
   *
   */
  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_interactive'));
  }

}
