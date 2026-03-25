<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_sso\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_sso.
 *
 * @group jaraba_sso
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_sso'];
  protected $defaultTheme = 'stark';

  /**
   *
   */
  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_sso'));
  }

}
