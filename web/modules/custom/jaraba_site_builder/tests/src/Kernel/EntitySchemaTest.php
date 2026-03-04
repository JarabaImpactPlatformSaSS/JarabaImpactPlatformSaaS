<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_site_builder\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_site_builder.
 *
 * @group jaraba_site_builder
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_site_builder'];

  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_site_builder'));
  }

}
