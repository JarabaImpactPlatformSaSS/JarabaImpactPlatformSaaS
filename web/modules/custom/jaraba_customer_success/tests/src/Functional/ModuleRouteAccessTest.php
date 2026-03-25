<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_customer_success\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_customer_success.
 *
 * @group jaraba_customer_success
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_customer_success'];
  protected $defaultTheme = 'stark';

  /**
   *
   */
  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_customer_success'));
  }

}
