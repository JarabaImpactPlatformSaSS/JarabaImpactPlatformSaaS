<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_site_builder\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_site_builder.
 *
 * @group jaraba_site_builder
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_site_builder'];
  protected $defaultTheme = 'stark';

  /**
   *
   */
  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_site_builder'));
  }

}
