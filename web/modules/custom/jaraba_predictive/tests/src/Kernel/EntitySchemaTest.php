<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_predictive\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_predictive.
 *
 * @group jaraba_predictive
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_predictive'];

  /**
   *
   */
  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_predictive'));
  }

}
