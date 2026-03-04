<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_training\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_training.
 *
 * @group jaraba_training
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_training'];

  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_training'));
  }

}
