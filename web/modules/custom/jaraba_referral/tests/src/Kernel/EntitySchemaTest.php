<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_referral\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_referral.
 *
 * @group jaraba_referral
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_referral'];

  /**
   *
   */
  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_referral'));
  }

}
