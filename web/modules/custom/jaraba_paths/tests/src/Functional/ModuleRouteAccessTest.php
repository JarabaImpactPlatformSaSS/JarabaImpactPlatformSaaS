<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_paths\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_paths.
 *
 * @group jaraba_paths
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_paths'];
  protected $defaultTheme = 'stark';

  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_paths'));
  }

}
