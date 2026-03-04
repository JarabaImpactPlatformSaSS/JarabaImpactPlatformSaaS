<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_email\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_email.
 *
 * @group jaraba_email
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_email'];
  protected $defaultTheme = 'stark';

  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_email'));
  }

}
