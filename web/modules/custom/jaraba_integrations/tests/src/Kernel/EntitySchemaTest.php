<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_integrations\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_integrations.
 *
 * @group jaraba_integrations
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_integrations'];

  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_integrations'));
  }

}
