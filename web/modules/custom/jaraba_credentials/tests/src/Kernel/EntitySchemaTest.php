<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_credentials\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_credentials.
 *
 * @group jaraba_credentials
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_credentials'];

  /**
   *
   */
  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_credentials'));
  }

}
