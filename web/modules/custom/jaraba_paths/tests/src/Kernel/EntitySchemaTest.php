<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_paths\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_paths.
 *
 * @group jaraba_paths
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_paths'];

  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_paths'));
  }

}
