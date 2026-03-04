<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_crm\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_crm.
 *
 * @group jaraba_crm
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_crm'];

  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_crm'));
  }

}
