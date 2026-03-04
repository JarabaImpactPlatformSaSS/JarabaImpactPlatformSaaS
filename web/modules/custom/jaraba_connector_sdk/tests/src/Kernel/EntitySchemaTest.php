<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_connector_sdk\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_connector_sdk.
 *
 * @group jaraba_connector_sdk
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_connector_sdk'];

  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_connector_sdk'));
  }

}
