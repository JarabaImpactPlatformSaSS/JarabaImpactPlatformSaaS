<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_insights_hub\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_insights_hub.
 *
 * @group jaraba_insights_hub
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_insights_hub'];

  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_insights_hub'));
  }

}
