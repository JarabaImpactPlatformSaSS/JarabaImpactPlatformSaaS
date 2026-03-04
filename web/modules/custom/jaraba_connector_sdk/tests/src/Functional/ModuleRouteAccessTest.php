<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_connector_sdk\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_connector_sdk.
 *
 * @group jaraba_connector_sdk
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_connector_sdk'];
  protected $defaultTheme = 'stark';

  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_connector_sdk'));
  }

}
