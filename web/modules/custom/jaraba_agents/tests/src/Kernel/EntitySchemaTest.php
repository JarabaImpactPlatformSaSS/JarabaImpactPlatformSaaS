<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_agents\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_agents.
 *
 * @group jaraba_agents
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_agents'];

  /**
   *
   */
  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_agents'));
  }

}
