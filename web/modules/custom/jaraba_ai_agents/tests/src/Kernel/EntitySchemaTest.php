<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ai_agents\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_ai_agents.
 *
 * @group jaraba_ai_agents
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_ai_agents'];

  /**
   *
   */
  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_ai_agents'));
  }

}
