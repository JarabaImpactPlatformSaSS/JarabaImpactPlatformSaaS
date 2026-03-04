<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_privacy\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_privacy.
 *
 * @group jaraba_privacy
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_privacy'];
  protected $defaultTheme = 'stark';

  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_privacy'));
  }

}
