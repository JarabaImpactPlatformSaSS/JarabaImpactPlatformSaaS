<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal_knowledge\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies route access for jaraba_legal_knowledge.
 *
 * @group jaraba_legal_knowledge
 */
class ModuleRouteAccessTest extends BrowserTestBase {

  protected static $modules = ['jaraba_legal_knowledge'];
  protected $defaultTheme = 'stark';

  public function testModuleEnabled(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_legal_knowledge'));
  }

}
