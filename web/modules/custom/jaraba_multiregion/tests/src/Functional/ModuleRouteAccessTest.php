<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_multiregion\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_multiregion.
 *
 * @group jaraba_multiregion
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_multiregion'];
  protected $defaultTheme = 'stark';

  /**
   *
   */
  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_multiregion'));
  }

}
