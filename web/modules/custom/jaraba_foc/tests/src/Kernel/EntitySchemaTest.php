<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_foc\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_foc.
 *
 * @group jaraba_foc
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_foc'];

  /**
   *
   */
  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_foc'));
  }

}
