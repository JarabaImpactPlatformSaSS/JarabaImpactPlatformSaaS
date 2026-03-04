<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_usage_billing\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_usage_billing.
 *
 * @group jaraba_usage_billing
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_usage_billing'];

  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_usage_billing'));
  }

}
