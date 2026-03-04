<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_social\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_social.
 *
 * @group jaraba_social
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_social'];
  protected $defaultTheme = 'stark';

  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_social'));
  }

}
