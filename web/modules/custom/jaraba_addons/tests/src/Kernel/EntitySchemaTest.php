<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_addons\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_addons.
 *
 * @group jaraba_addons
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_addons'];

  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_addons'));
  }

}
