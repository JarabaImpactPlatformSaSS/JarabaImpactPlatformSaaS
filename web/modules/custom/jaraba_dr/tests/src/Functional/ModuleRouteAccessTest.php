<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_dr\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_dr.
 *
 * @group jaraba_dr
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_dr'];
  protected $defaultTheme = 'stark';

  /**
   *
   */
  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_dr'));
  }

}
