<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_email\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_email.
 *
 * @group jaraba_email
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_email'];

  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_email'));
  }

}
