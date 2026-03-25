<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_identity\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_identity.
 *
 * @group jaraba_identity
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_identity'];

  /**
   *
   */
  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_identity'));
  }

}
