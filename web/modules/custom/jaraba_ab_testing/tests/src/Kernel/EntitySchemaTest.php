<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ab_testing\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_ab_testing.
 *
 * @group jaraba_ab_testing
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_ab_testing'];

  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_ab_testing'));
  }

}
