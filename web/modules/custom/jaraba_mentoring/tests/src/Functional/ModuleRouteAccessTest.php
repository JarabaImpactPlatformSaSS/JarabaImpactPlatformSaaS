<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_mentoring\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_mentoring.
 *
 * @group jaraba_mentoring
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_mentoring'];
  protected $defaultTheme = 'stark';

  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_mentoring'));
  }

}
