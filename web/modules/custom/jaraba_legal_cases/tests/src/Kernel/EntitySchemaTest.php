<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal_cases\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_legal_cases.
 *
 * @group jaraba_legal_cases
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_legal_cases'];

  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_legal_cases'));
  }

}
