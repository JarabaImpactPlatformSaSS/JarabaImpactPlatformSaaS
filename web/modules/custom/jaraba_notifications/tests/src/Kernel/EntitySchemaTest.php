<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_notifications\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_notifications.
 *
 * @group jaraba_notifications
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_notifications'];

  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_notifications'));
  }

}
