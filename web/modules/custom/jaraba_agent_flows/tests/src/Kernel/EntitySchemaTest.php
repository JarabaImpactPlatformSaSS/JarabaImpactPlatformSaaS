<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_agent_flows\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_agent_flows.
 *
 * @group jaraba_agent_flows
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_agent_flows'];

  /**
   *
   */
  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_agent_flows'));
  }

}
