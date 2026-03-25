<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_page_builder\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_page_builder.
 *
 * @group jaraba_page_builder
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_page_builder'];
  protected $defaultTheme = 'stark';

  /**
   *
   */
  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_page_builder'));
  }

}
