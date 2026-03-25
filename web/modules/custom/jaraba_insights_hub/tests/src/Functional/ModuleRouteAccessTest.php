<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_insights_hub\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_insights_hub.
 *
 * @group jaraba_insights_hub
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_insights_hub'];
  protected $defaultTheme = 'stark';

  /**
   *
   */
  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_insights_hub'));
  }

}
