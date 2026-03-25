<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_copilot_v2\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_copilot_v2.
 *
 * @group jaraba_copilot_v2
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_copilot_v2'];

  /**
   *
   */
  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_copilot_v2'));
  }

}
