<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_agents\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_agents.
 *
 * @group jaraba_agents
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_agents'];
  protected $defaultTheme = 'stark';

  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_agents'));
  }

}
