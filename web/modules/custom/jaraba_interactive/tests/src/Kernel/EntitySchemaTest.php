<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_interactive\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_interactive.
 *
 * @group jaraba_interactive
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_interactive'];

  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_interactive'));
  }

}
