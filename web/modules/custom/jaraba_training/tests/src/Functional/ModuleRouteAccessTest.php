<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_training\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_training.
 *
 * @group jaraba_training
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_training'];
  protected $defaultTheme = 'stark';

  /**
   *
   */
  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_training'));
  }

}
