<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_tenant_knowledge\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_tenant_knowledge.
 *
 * @group jaraba_tenant_knowledge
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_tenant_knowledge'];
  protected $defaultTheme = 'stark';

  /**
   *
   */
  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_tenant_knowledge'));
  }

}
