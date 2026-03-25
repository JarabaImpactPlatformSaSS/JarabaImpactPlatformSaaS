<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_multiregion\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that jaraba_multiregion entity types are properly defined.
 *
 * @group jaraba_multiregion
 */
class EntitySchemaTest extends KernelTestBase {

  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'options',
    'flexible_permissions',
    'group',
    'ecosistema_jaraba_core',
    'jaraba_multiregion',
  ];

  protected $strictConfigSchema = FALSE;

  /**
   *
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
  }

  /**
   *
   */
  public function testEntityTypesExist(): void {
    $etm = \Drupal::entityTypeManager();
    $this->assertTrue($etm->hasDefinition('vies_validation'));
  }

}
