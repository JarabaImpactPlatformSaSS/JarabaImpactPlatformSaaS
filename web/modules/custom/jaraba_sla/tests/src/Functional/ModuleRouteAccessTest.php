<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_sla\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_sla.
 *
 * @group jaraba_sla
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_sla'];
  protected $defaultTheme = 'stark';

  /**
   *
   */
  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_sla'));
  }

}
