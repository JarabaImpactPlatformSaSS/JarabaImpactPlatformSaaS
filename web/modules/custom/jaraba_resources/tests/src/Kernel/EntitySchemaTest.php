<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_resources\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_resources.
 *
 * @group jaraba_resources
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_resources'];

  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_resources'));
  }

}
