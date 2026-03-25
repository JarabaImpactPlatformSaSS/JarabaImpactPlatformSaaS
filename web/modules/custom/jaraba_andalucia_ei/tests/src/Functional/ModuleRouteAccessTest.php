<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_andalucia_ei.
 *
 * @group jaraba_andalucia_ei
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_andalucia_ei'];
  protected $defaultTheme = 'stark';

  /**
   *
   */
  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_andalucia_ei'));
  }

}
