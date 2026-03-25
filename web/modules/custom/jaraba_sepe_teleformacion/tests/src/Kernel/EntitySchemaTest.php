<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_sepe_teleformacion\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_sepe_teleformacion.
 *
 * @group jaraba_sepe_teleformacion
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_sepe_teleformacion'];

  /**
   *
   */
  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_sepe_teleformacion'));
  }

}
