<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_journey\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_journey.
 *
 * @group jaraba_journey
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_journey'];

  /**
   *
   */
  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_journey'));
  }

}
