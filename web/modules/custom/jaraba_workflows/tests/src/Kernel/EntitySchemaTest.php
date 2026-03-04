<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_workflows\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_workflows.
 *
 * @group jaraba_workflows
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_workflows'];

  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_workflows'));
  }

}
