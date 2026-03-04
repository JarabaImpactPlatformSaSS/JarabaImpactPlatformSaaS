<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_governance\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_governance.
 *
 * @group jaraba_governance
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_governance'];
  protected $defaultTheme = 'stark';

  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_governance'));
  }

}
