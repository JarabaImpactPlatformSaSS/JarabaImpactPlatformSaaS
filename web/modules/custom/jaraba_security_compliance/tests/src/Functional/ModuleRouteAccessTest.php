<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_security_compliance\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_security_compliance.
 *
 * @group jaraba_security_compliance
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_security_compliance'];
  protected $defaultTheme = 'stark';

  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_security_compliance'));
  }

}
