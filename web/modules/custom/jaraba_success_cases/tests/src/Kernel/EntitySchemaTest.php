<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_success_cases\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that jaraba_success_cases entity types are properly defined.
 *
 * @group jaraba_success_cases
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'options',
    'file',
    'media',
    'image',
    'flexible_permissions',
    'group',
    'ecosistema_jaraba_core',
    'jaraba_success_cases',
  ];

  protected $strictConfigSchema = FALSE;

  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
  }

  public function testEntityTypesExist(): void {
    $etm = \Drupal::entityTypeManager();
    $this->assertTrue($etm->hasDefinition('success_case'));
  }

}
