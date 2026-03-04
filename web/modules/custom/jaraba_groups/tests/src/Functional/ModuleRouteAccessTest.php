<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_groups\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_groups.
 *
 * @group jaraba_groups
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_groups'];
  protected $defaultTheme = 'stark';

  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_groups'));
  }

}
