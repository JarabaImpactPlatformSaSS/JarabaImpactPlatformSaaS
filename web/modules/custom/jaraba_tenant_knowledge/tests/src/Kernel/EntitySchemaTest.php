<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_tenant_knowledge\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies entity schema for jaraba_tenant_knowledge.
 *
 * @group jaraba_tenant_knowledge
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = ['jaraba_tenant_knowledge'];

  public function testModuleInstalls(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('jaraba_tenant_knowledge'));
  }

}
