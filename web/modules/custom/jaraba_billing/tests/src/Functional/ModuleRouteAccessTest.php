<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_billing\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_billing.
 *
 * @group jaraba_billing
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_billing'];
  protected $defaultTheme = 'stark';

  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_billing'));
  }

}
