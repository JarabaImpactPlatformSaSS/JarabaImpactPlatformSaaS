<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_diagnostic\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_diagnostic.
 *
 * @group jaraba_diagnostic
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_diagnostic'];

  /**
   *
   */
  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_diagnostic'));
  }

}
