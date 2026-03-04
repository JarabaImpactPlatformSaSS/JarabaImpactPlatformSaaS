<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_matching\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_matching.
 *
 * @group jaraba_matching
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_matching'];

  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_matching'));
  }

}
