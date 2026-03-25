<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_theming\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_theming.
 *
 * @group jaraba_theming
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_theming'];

  /**
   *
   */
  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_theming'));
  }

}
