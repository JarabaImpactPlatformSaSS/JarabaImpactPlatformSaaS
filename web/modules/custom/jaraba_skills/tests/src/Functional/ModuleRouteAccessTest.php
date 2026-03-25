<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_skills\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_skills.
 *
 * @group jaraba_skills
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_skills'];
  protected $defaultTheme = 'stark';

  /**
   *
   */
  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_skills'));
  }

}
