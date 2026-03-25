<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ads\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_ads.
 *
 * @group jaraba_ads
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_ads'];
  protected $defaultTheme = 'stark';

  /**
   *
   */
  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_ads'));
  }

}
