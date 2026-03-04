<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_security_compliance\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_security_compliance.
 *
 * @group jaraba_security_compliance
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_security_compliance'];

  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_security_compliance'));
  }

}
