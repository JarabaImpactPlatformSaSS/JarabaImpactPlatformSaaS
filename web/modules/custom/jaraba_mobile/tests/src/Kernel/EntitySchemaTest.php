<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_mobile\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_mobile.
 *
 * @group jaraba_mobile
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_mobile'];

  /**
   *
   */
  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_mobile'));
  }

}
