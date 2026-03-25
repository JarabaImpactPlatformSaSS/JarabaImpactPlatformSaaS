<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_mentoring\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_mentoring.
 *
 * @group jaraba_mentoring
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_mentoring'];

  /**
   *
   */
  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_mentoring'));
  }

}
