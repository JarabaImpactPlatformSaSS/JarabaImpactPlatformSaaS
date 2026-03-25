<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_funding\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that jaraba_funding entity types are properly defined.
 *
 * @group jaraba_funding
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
    'jaraba_funding',
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
    $this->assertTrue($etm->hasDefinition('funding_application'));
    $this->assertTrue($etm->hasDefinition('funding_opportunity'));
    $this->assertTrue($etm->hasDefinition('technical_report'));
  }

}
