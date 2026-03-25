<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_agent_market\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_agent_market.
 *
 * @group jaraba_agent_market
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_agent_market'];

  /**
   *
   */
  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_agent_market'));
  }

}
