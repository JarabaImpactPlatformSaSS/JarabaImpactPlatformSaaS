<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_sla\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_sla.
 *
 * @group jaraba_sla
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_sla'];

  /**
   *
   */
  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_sla'));
  }

}
