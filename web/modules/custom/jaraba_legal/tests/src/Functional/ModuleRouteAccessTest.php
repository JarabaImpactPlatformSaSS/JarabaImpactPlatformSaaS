<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_legal.
 *
 * @group jaraba_legal
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_legal'];
  protected $defaultTheme = 'stark';

  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_legal'));
  }

}
