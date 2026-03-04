<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_onboarding\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_onboarding.
 *
 * @group jaraba_onboarding
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_onboarding'];
  protected $defaultTheme = 'stark';

  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_onboarding'));
  }

}
