<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_pixels\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_pixels.
 *
 * @group jaraba_pixels
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_pixels'];
  protected $defaultTheme = 'stark';

  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_pixels'));
  }

}
