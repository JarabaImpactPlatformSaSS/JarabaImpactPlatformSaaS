<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_dr\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_dr.
 *
 * @group jaraba_dr
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_dr'];

  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_dr'));
  }

}
