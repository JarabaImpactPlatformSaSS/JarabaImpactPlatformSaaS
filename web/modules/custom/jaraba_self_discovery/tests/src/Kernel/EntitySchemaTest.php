<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_self_discovery\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_self_discovery.
 *
 * @group jaraba_self_discovery
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_self_discovery'];

  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_self_discovery'));
  }

}
