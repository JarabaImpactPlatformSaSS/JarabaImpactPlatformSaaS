<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_sso\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_sso.
 *
 * @group jaraba_sso
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_sso'];

  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_sso'));
  }

}
