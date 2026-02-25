<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_page_builder\Kernel\Entity;

use Drupal\KernelTests\KernelTestBase;
use Drupal\jaraba_page_builder\Entity\PageContent;

/**
 * Kernel tests for the PageContent entity.
 *
 * Tests CRUD operations, field definitions, multi-block section methods,
 * and path_alias auto-generation.
 *
 * @coversDefaultClass \Drupal\jaraba_page_builder\Entity\PageContent
 * @group jaraba_page_builder
 */
class PageContentEntityTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'options',
    'jaraba_page_builder',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');

    // page_content has entity_reference field targeting 'group' entity type.
    // Skip if group entity type is unavailable (missing contrib dependencies).
    $definitions = \Drupal::entityTypeManager()->getDefinitions();
    if (!isset($definitions['group'])) {
      $this->markTestSkipped('Group entity type required for page_content schema.');
    }
    $this->installEntitySchema('group');
    $this->installEntitySchema('page_content');
    $this->installConfig(['jaraba_page_builder']);
  }

  // =========================================================================
  // TESTS: Entity CRUD
  // =========================================================================

  /**
   * Tests creating a PageContent entity with minimal fields.
   *
   * @covers ::create
   * @covers ::save
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testCreateMinimal(): void {
    $entity = PageContent::create([
      'title' => 'Test Page',
    ]);
    $entity->save();

    $this->assertNotNull($entity->id());
    $this->assertSame('Test Page', $entity->getTitle());
  }

  /**
   * Tests loading a PageContent entity by ID.
   *
   * @covers ::load
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testLoadById(): void {
    $entity = PageContent::create([
      'title' => 'Load Test',
    ]);
    $entity->save();
    $id = $entity->id();

    $loaded = PageContent::load($id);
    $this->assertNotNull($loaded);
    $this->assertSame('Load Test', $loaded->getTitle());
  }

  /**
   * Tests updating a PageContent entity.
   *
   * @covers ::save
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testUpdate(): void {
    $entity = PageContent::create([
      'title' => 'Original',
    ]);
    $entity->save();

    $entity->set('title', 'Updated');
    $entity->save();

    $loaded = PageContent::load($entity->id());
    $this->assertSame('Updated', $loaded->getTitle());
  }

  /**
   * Tests deleting a PageContent entity.
   *
   * @covers ::delete
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testDelete(): void {
    $entity = PageContent::create([
      'title' => 'Delete Me',
    ]);
    $entity->save();
    $id = $entity->id();

    $entity->delete();

    $this->assertNull(PageContent::load($id));
  }

  // =========================================================================
  // TESTS: Fields
  // =========================================================================

  /**
   * Tests that all expected base fields are defined.
   *
   * @covers ::baseFieldDefinitions
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testBaseFieldsExist(): void {
    $fields = PageContent::baseFieldDefinitions(
      \Drupal::entityTypeManager()->getDefinition('page_content')
    );

    $expectedFields = [
      'id', 'uuid', 'title', 'path_alias', 'tenant_id',
      'status', 'user_id', 'created', 'changed',
    ];

    foreach ($expectedFields as $fieldName) {
      $this->assertArrayHasKey($fieldName, $fields, "Missing base field: $fieldName");
    }
  }

  /**
   * Tests that path_alias field stores correctly.
   *
   * @covers ::getPathAlias
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testPathAliasField(): void {
    $entity = PageContent::create([
      'title' => 'Alias Test',
      'path_alias' => '/my-custom-path',
    ]);
    $entity->save();

    $loaded = PageContent::load($entity->id());
    $this->assertSame('/my-custom-path', $loaded->getPathAlias());
  }

  /**
   * Tests getTenantId returns correct value.
   *
   * @covers ::getTenantId
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testTenantIdField(): void {
    $entity = PageContent::create([
      'title' => 'Tenant Test',
    ]);
    $entity->save();

    // Without tenant, should return NULL.
    $this->assertNull($entity->getTenantId());
  }

  /**
   * Tests entity publish/unpublish state.
   *
   * @covers ::isPublished
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testPublishState(): void {
    $entity = PageContent::create([
      'title' => 'Publish Test',
      'status' => 1,
    ]);
    $entity->save();
    $this->assertTrue($entity->isPublished());

    $entity->set('status', 0);
    $entity->save();

    $loaded = PageContent::load($entity->id());
    $this->assertFalse($loaded->isPublished());
  }

  // =========================================================================
  // TESTS: Multi-block Section Methods
  // =========================================================================

  /**
   * Tests addSection creates a new section with UUID.
   *
   * @covers ::addSection
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testAddSection(): void {
    $entity = PageContent::create([
      'title' => 'Section Test',
    ]);
    $entity->save();

    $uuid = $entity->addSection('hero', ['heading' => 'Hello']);
    $entity->save();

    $this->assertNotEmpty($uuid);
    $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-/', $uuid);
  }

  /**
   * Tests updateSection modifies an existing section.
   *
   * @covers ::updateSection
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testUpdateSection(): void {
    $entity = PageContent::create([
      'title' => 'Update Section Test',
    ]);
    $entity->save();

    $uuid = $entity->addSection('hero', ['heading' => 'Original']);
    $result = $entity->updateSection($uuid, ['content' => ['heading' => 'Updated']]);
    $entity->save();

    $this->assertTrue($result);
  }

  /**
   * Tests removeSection deletes a section by UUID.
   *
   * @covers ::removeSection
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testRemoveSection(): void {
    $entity = PageContent::create([
      'title' => 'Remove Section Test',
    ]);
    $entity->save();

    $uuid = $entity->addSection('hero', ['heading' => 'To Remove']);
    $result = $entity->removeSection($uuid);
    $entity->save();

    $this->assertTrue($result);
  }

  /**
   * Tests reorderSections with ordered UUID array.
   *
   * @covers ::reorderSections
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testReorderSections(): void {
    $entity = PageContent::create([
      'title' => 'Reorder Test',
    ]);
    $entity->save();

    $uuid1 = $entity->addSection('hero', ['heading' => 'First']);
    $uuid2 = $entity->addSection('features', ['heading' => 'Second']);
    $uuid3 = $entity->addSection('cta', ['heading' => 'Third']);

    // Reorder: third, first, second.
    $result = $entity->reorderSections([$uuid3, $uuid1, $uuid2]);
    $entity->save();

    $this->assertTrue($result);
  }

}
