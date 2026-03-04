<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_credentials\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_credentials.
 *
 * @group jaraba_credentials
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_credentials'];
  protected $defaultTheme = 'stark';

  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_credentials'));
  }

}
