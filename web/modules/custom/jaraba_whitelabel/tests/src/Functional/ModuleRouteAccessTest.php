<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_whitelabel\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_whitelabel.
 *
 * @group jaraba_whitelabel
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_whitelabel'];
  protected $defaultTheme = 'stark';

  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_whitelabel'));
  }

}
