<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_funding\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_funding.
 *
 * @group jaraba_funding
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_funding'];
  protected $defaultTheme = 'stark';

  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_funding'));
  }

}
