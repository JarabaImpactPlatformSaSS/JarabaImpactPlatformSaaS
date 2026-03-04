<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_onboarding\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_onboarding.
 *
 * @group jaraba_onboarding
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_onboarding'];

  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_onboarding'));
  }

}
