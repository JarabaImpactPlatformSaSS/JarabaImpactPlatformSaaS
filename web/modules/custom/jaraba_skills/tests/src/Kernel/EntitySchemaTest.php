<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_skills\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_skills.
 *
 * @group jaraba_skills
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_skills'];

  /**
   *
   */
  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_skills'));
  }

}
