<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_messaging\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_messaging.
 *
 * @group jaraba_messaging
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_messaging'];
  protected $defaultTheme = 'stark';

  /**
   *
   */
  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_messaging'));
  }

}
