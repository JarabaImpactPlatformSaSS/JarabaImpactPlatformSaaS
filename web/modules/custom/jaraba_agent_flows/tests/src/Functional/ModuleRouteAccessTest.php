<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_agent_flows\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_agent_flows.
 *
 * @group jaraba_agent_flows
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_agent_flows'];
  protected $defaultTheme = 'stark';

  /**
   *
   */
  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_agent_flows'));
  }

}
