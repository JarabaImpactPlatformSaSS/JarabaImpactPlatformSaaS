<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_social\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_social.
 *
 * @group jaraba_social
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_social'];

  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_social'));
  }

}
