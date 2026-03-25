<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_business_tools\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_business_tools.
 *
 * @group jaraba_business_tools
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_business_tools'];
  protected $defaultTheme = 'stark';

  /**
   *
   */
  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_business_tools'));
  }

}
