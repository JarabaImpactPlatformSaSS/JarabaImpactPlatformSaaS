<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_analytics\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_analytics.
 *
 * @group jaraba_analytics
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_analytics'];
  protected $defaultTheme = 'stark';

  /**
   *
   */
  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_analytics'));
  }

}
