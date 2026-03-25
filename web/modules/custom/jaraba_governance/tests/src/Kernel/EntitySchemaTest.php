<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_governance\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_governance.
 *
 * @group jaraba_governance
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_governance'];

  /**
   *
   */
  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_governance'));
  }

}
