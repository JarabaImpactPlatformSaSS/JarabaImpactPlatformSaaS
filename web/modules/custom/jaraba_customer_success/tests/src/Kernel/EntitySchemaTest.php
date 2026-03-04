<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_customer_success\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_customer_success.
 *
 * @group jaraba_customer_success
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_customer_success'];

  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_customer_success'));
  }

}
