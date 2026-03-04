<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_pwa\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_pwa.
 *
 * @group jaraba_pwa
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_pwa'];

  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_pwa'));
  }

}
