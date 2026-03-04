<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal_calendar\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_legal_calendar.
 *
 * @group jaraba_legal_calendar
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_legal_calendar'];

  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_legal_calendar'));
  }

}
