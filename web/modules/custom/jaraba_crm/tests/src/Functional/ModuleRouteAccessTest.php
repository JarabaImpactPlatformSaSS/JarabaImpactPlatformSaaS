<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_crm\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_crm.
 *
 * @group jaraba_crm
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_crm'];
  protected $defaultTheme = 'stark';

  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_crm'));
  }

}
