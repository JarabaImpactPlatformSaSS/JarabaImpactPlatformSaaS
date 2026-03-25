<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ads\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_ads.
 *
 * @group jaraba_ads
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_ads'];

  /**
   *
   */
  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_ads'));
  }

}
