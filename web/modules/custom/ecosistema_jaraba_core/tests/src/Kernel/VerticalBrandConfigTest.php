<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests VerticalBrandConfig entity.
 *
 * @group ecosistema_jaraba_core
 */
class VerticalBrandConfigTest extends KernelTestBase {

  /**
   * KERNEL-TEST-DEPS-001: List ALL required modules.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'options',
    'flexible_permissions',
    'group',
    'ecosistema_jaraba_core',
  ];

  /**
   * {@inheritdoc}
   */
  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    // Only install specific config to avoid pre-existing config issues
    // (design_token_config has dot-key issue).
  }

  /**
   * Tests that VerticalBrandConfig entity type exists.
   */
  public function testEntityTypeExists(): void {
    $entityTypeManager = \Drupal::entityTypeManager();
    $this->assertTrue($entityTypeManager->hasDefinition('vertical_brand'));
  }

  /**
   * Tests loading a vertical brand config.
   */
  public function testLoadVerticalBrand(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('vertical_brand');

    // Create a test config.
    $brand = $storage->create([
      'id' => 'test_vertical',
      'label' => 'Test Vertical',
      'vertical' => 'demo',
      'public_name' => 'Jaraba Test',
      'tagline' => 'Test tagline',
      'enabled' => TRUE,
    ]);
    $brand->save();

    // Reload.
    $loaded = $storage->load('test_vertical');
    $this->assertNotNull($loaded);
    $this->assertEquals('Test Vertical', $loaded->label());
    $this->assertEquals('demo', $loaded->getVertical());
    $this->assertEquals('Jaraba Test', $loaded->getPublicName());
    $this->assertEquals('Test tagline', $loaded->getTagline());
    $this->assertTrue($loaded->isEnabled());
  }

  /**
   * Tests default values.
   */
  public function testDefaultValues(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('vertical_brand');
    $brand = $storage->create([
      'id' => 'defaults_test',
      'label' => 'Defaults Test',
      'vertical' => 'demo',
    ]);

    $this->assertEquals('{page_title} | Jaraba', $brand->getSeoTitleTemplate());
    $this->assertEquals('Organization', $brand->getSchemaOrgType());
    $this->assertEquals('landing', $brand->getRevelationLevel());
    $this->assertEquals('vertical', $brand->getIconCategory());
  }

}
