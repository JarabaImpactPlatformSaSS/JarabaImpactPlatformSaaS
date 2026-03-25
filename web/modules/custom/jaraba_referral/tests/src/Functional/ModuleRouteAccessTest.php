<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_referral\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_referral.
 *
 * @group jaraba_referral
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_referral'];
  protected $defaultTheme = 'stark';

  /**
   *
   */
  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_referral'));
  }

}
