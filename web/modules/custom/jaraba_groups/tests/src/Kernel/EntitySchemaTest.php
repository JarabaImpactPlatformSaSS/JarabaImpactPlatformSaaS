<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_groups\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_groups.
 *
 * @group jaraba_groups
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_groups'];

  /**
   *
   */
  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_groups'));
  }

}
