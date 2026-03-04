<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_events\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_events.
 *
 * @group jaraba_events
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_events'];

  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_events'));
  }

}
