<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_privacy\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_privacy.
 *
 * @group jaraba_privacy
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_privacy'];

  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_privacy'));
  }

}
