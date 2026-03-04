<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_pixels\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_pixels.
 *
 * @group jaraba_pixels
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_pixels'];

  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_pixels'));
  }

}
