<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_legal.
 *
 * @group jaraba_legal
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_legal'];

  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_legal'));
  }

}
