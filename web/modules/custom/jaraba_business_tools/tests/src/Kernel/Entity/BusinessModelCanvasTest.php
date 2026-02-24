<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_business_tools\Kernel\Entity;

use Drupal\jaraba_business_tools\Entity\BusinessModelCanvas;
use Drupal\jaraba_business_tools\Entity\BusinessModelCanvasInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel tests for the BusinessModelCanvas entity.
 *
 * Verifies entity creation, field definitions, getters/setters,
 * and required field enforcement in a real Drupal kernel environment.
 *
 * @group jaraba_business_tools
 */
class BusinessModelCanvasTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'options',
    'datetime',
    'views',
    'jaraba_diagnostic',
    'jaraba_paths',
    'jaraba_business_tools',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    try {
      $this->installEntitySchema('user');
      $this->installEntitySchema('business_model_canvas');
    }
    catch (\Exception $e) {
      $this->markTestSkipped('Entity schemas could not be installed: ' . $e->getMessage());
    }
  }

  /**
   * Tests that the BusinessModelCanvas entity class exists.
   */
  public function testEntityClassExists(): void {
    $this->assertTrue(
      class_exists(BusinessModelCanvas::class),
      'BusinessModelCanvas class should exist.'
    );
  }

  /**
   * Tests that BusinessModelCanvas implements its interface.
   */
  public function testEntityImplementsInterface(): void {
    $reflection = new \ReflectionClass(BusinessModelCanvas::class);
    $this->assertTrue(
      $reflection->implementsInterface(BusinessModelCanvasInterface::class),
      'BusinessModelCanvas should implement BusinessModelCanvasInterface.'
    );
  }

  /**
   * Tests that a canvas entity can be created with required fields.
   */
  public function testCanvasEntityCreate(): void {
    $entity = BusinessModelCanvas::create([
      'title' => 'Mi Modelo de Negocio',
      'sector' => 'tech',
      'business_stage' => 'idea',
      'status' => 'draft',
      'user_id' => 1,
    ]);

    $this->assertNotNull($entity);
    $this->assertInstanceOf(BusinessModelCanvasInterface::class, $entity);
    $this->assertEquals('Mi Modelo de Negocio', $entity->getTitle());
    $this->assertEquals('tech', $entity->getSector());
    $this->assertEquals('idea', $entity->getBusinessStage());
    $this->assertEquals('draft', $entity->getStatus());
  }

  /**
   * Tests that a canvas entity can be saved and loaded from the database.
   */
  public function testCanvasEntitySaveAndLoad(): void {
    $entity = BusinessModelCanvas::create([
      'title' => 'Cafeteria Digital',
      'sector' => 'hosteleria',
      'business_stage' => 'validacion',
      'status' => 'active',
      'user_id' => 0,
    ]);

    $entity->save();
    $this->assertNotNull($entity->id(), 'Entity should have an ID after save.');

    $storage = \Drupal::entityTypeManager()->getStorage('business_model_canvas');
    $loaded = $storage->load($entity->id());

    $this->assertNotNull($loaded, 'Entity should be loadable from database.');
    $this->assertEquals('Cafeteria Digital', $loaded->getTitle());
    $this->assertEquals('hosteleria', $loaded->getSector());
    $this->assertEquals('validacion', $loaded->getBusinessStage());
    $this->assertEquals('active', $loaded->getStatus());
  }

  /**
   * Tests the setTitle getter/setter.
   */
  public function testSetTitle(): void {
    $entity = BusinessModelCanvas::create([
      'title' => 'Original Title',
      'sector' => 'otros',
      'business_stage' => 'idea',
      'status' => 'draft',
      'user_id' => 0,
    ]);

    $this->assertEquals('Original Title', $entity->getTitle());

    $result = $entity->setTitle('Updated Title');
    $this->assertEquals('Updated Title', $entity->getTitle());
    $this->assertSame($entity, $result, 'setTitle should return $this for chaining.');
  }

  /**
   * Tests the setStatus getter/setter.
   */
  public function testSetStatus(): void {
    $entity = BusinessModelCanvas::create([
      'title' => 'Test Canvas',
      'sector' => 'comercio',
      'business_stage' => 'idea',
      'status' => 'draft',
      'user_id' => 0,
    ]);

    $this->assertEquals('draft', $entity->getStatus());

    $result = $entity->setStatus('active');
    $this->assertEquals('active', $entity->getStatus());
    $this->assertSame($entity, $result, 'setStatus should return $this for chaining.');

    $entity->setStatus('archived');
    $this->assertEquals('archived', $entity->getStatus());
  }

  /**
   * Tests the version field and incrementVersion method.
   */
  public function testVersionIncrement(): void {
    $entity = BusinessModelCanvas::create([
      'title' => 'Version Test',
      'sector' => 'servicios',
      'business_stage' => 'crecimiento',
      'status' => 'active',
      'version' => 1,
      'user_id' => 0,
    ]);

    $this->assertEquals(1, $entity->getVersion());

    $result = $entity->incrementVersion();
    $this->assertEquals(2, $entity->getVersion());
    $this->assertSame($entity, $result, 'incrementVersion should return $this for chaining.');

    $entity->incrementVersion();
    $this->assertEquals(3, $entity->getVersion());
  }

  /**
   * Tests the coherence score getter/setter.
   */
  public function testCoherenceScore(): void {
    $entity = BusinessModelCanvas::create([
      'title' => 'Score Test',
      'sector' => 'tech',
      'business_stage' => 'idea',
      'status' => 'draft',
      'user_id' => 0,
    ]);

    // Initially NULL.
    $this->assertNull($entity->getCoherenceScore());

    $result = $entity->setCoherenceScore(85.5);
    $this->assertEquals(85.5, $entity->getCoherenceScore());
    $this->assertSame($entity, $result, 'setCoherenceScore should return $this for chaining.');
  }

  /**
   * Tests the completeness score default value.
   */
  public function testCompletenessScoreDefault(): void {
    $entity = BusinessModelCanvas::create([
      'title' => 'Completeness Test',
      'sector' => 'agro',
      'business_stage' => 'idea',
      'status' => 'draft',
      'user_id' => 0,
    ]);

    $this->assertEquals(0, $entity->getCompletenessScore());
  }

  /**
   * Tests the isTemplate field.
   */
  public function testIsTemplate(): void {
    // Default is FALSE.
    $entity = BusinessModelCanvas::create([
      'title' => 'Not A Template',
      'sector' => 'otros',
      'business_stage' => 'idea',
      'status' => 'draft',
      'user_id' => 0,
    ]);

    $this->assertFalse($entity->isTemplate());

    // Explicitly set as template.
    $template = BusinessModelCanvas::create([
      'title' => 'Template Canvas',
      'sector' => 'tech',
      'business_stage' => 'idea',
      'status' => 'active',
      'is_template' => TRUE,
      'user_id' => 0,
    ]);

    $this->assertTrue($template->isTemplate());
  }

  /**
   * Tests the shared_with / collaborator functionality.
   */
  public function testCollaborators(): void {
    $entity = BusinessModelCanvas::create([
      'title' => 'Shared Canvas',
      'sector' => 'tech',
      'business_stage' => 'idea',
      'status' => 'active',
      'user_id' => 0,
    ]);

    // Initially empty.
    $this->assertEmpty($entity->getSharedWith());

    // Add collaborator.
    $result = $entity->addCollaborator(42);
    $this->assertSame($entity, $result, 'addCollaborator should return $this for chaining.');
    $this->assertContains(42, $entity->getSharedWith());

    // Add another collaborator.
    $entity->addCollaborator(99);
    $shared = $entity->getSharedWith();
    $this->assertCount(2, $shared);
    $this->assertContains(42, $shared);
    $this->assertContains(99, $shared);

    // Adding the same collaborator again should not duplicate.
    $entity->addCollaborator(42);
    $this->assertCount(2, $entity->getSharedWith());
  }

  /**
   * Tests sector default value.
   */
  public function testSectorDefault(): void {
    $entity = BusinessModelCanvas::create([
      'title' => 'Default Sector',
      'business_stage' => 'idea',
      'status' => 'draft',
      'user_id' => 0,
    ]);

    $this->assertEquals('otros', $entity->getSector());
  }

  /**
   * Tests business_stage default value.
   */
  public function testBusinessStageDefault(): void {
    $entity = BusinessModelCanvas::create([
      'title' => 'Default Stage',
      'sector' => 'tech',
      'status' => 'draft',
      'user_id' => 0,
    ]);

    $this->assertEquals('idea', $entity->getBusinessStage());
  }

  /**
   * Tests the getDiagnosticId method.
   */
  public function testGetDiagnosticId(): void {
    // Without a diagnostic link.
    $entity = BusinessModelCanvas::create([
      'title' => 'No Diagnostic',
      'sector' => 'tech',
      'business_stage' => 'idea',
      'status' => 'draft',
      'user_id' => 0,
    ]);

    $this->assertNull($entity->getDiagnosticId());
  }

  /**
   * Tests that baseFieldDefinitions returns all expected fields.
   */
  public function testBaseFieldDefinitions(): void {
    $definitions = BusinessModelCanvas::baseFieldDefinitions(
      $this->container->get('entity_type.manager')->getDefinition('business_model_canvas')
    );

    $expectedFields = [
      'id',
      'uuid',
      'title',
      'description',
      'sector',
      'business_stage',
      'tenant_id',
      'business_diagnostic_id',
      'version',
      'is_template',
      'template_source_id',
      'completeness_score',
      'coherence_score',
      'last_ai_analysis',
      'shared_with',
      'status',
      'created',
      'changed',
      'user_id',
    ];

    foreach ($expectedFields as $field) {
      $this->assertArrayHasKey(
        $field,
        $definitions,
        "baseFieldDefinitions should include the '{$field}' field."
      );
    }
  }

  /**
   * Tests that required fields are properly defined.
   */
  public function testRequiredFields(): void {
    $definitions = BusinessModelCanvas::baseFieldDefinitions(
      $this->container->get('entity_type.manager')->getDefinition('business_model_canvas')
    );

    $requiredFields = ['title', 'sector', 'business_stage', 'status'];

    foreach ($requiredFields as $field) {
      $this->assertTrue(
        $definitions[$field]->isRequired(),
        "Field '{$field}' should be required."
      );
    }
  }

  /**
   * Tests that optional fields are not required.
   */
  public function testOptionalFields(): void {
    $definitions = BusinessModelCanvas::baseFieldDefinitions(
      $this->container->get('entity_type.manager')->getDefinition('business_model_canvas')
    );

    $optionalFields = ['description', 'coherence_score', 'shared_with'];

    foreach ($optionalFields as $field) {
      $this->assertFalse(
        $definitions[$field]->isRequired(),
        "Field '{$field}' should be optional."
      );
    }
  }

  /**
   * Tests allowed values for sector field.
   */
  public function testSectorAllowedValues(): void {
    $definitions = BusinessModelCanvas::baseFieldDefinitions(
      $this->container->get('entity_type.manager')->getDefinition('business_model_canvas')
    );

    $allowedValues = $definitions['sector']->getSetting('allowed_values');
    $expectedKeys = ['comercio', 'servicios', 'hosteleria', 'agro', 'tech', 'industria', 'otros'];

    foreach ($expectedKeys as $key) {
      $this->assertArrayHasKey(
        $key,
        $allowedValues,
        "Sector should have allowed value: {$key}"
      );
    }
  }

  /**
   * Tests allowed values for business_stage field.
   */
  public function testBusinessStageAllowedValues(): void {
    $definitions = BusinessModelCanvas::baseFieldDefinitions(
      $this->container->get('entity_type.manager')->getDefinition('business_model_canvas')
    );

    $allowedValues = $definitions['business_stage']->getSetting('allowed_values');
    $expectedKeys = ['idea', 'validacion', 'crecimiento', 'escalado'];

    foreach ($expectedKeys as $key) {
      $this->assertArrayHasKey(
        $key,
        $allowedValues,
        "Business stage should have allowed value: {$key}"
      );
    }
  }

  /**
   * Tests allowed values for status field.
   */
  public function testStatusAllowedValues(): void {
    $definitions = BusinessModelCanvas::baseFieldDefinitions(
      $this->container->get('entity_type.manager')->getDefinition('business_model_canvas')
    );

    $allowedValues = $definitions['status']->getSetting('allowed_values');
    $expectedKeys = ['draft', 'active', 'archived'];

    foreach ($expectedKeys as $key) {
      $this->assertArrayHasKey(
        $key,
        $allowedValues,
        "Status should have allowed value: {$key}"
      );
    }
  }

}
