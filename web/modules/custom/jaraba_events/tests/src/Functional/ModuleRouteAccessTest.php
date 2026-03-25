<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_events\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_events.
 *
 * @group jaraba_events
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_events'];
  protected $defaultTheme = 'stark';

  /**
   *
   */
  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_events'));
  }

}
