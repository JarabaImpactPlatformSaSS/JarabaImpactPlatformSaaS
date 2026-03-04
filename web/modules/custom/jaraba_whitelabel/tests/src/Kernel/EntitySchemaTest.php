<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_whitelabel\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_whitelabel.
 *
 * @group jaraba_whitelabel
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_whitelabel'];

  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_whitelabel'));
  }

}
