<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal_knowledge\Kernel;

use Drupal\jaraba_legal_knowledge\Entity\LegalNorm;
use Drupal\jaraba_legal_knowledge\Entity\LegalNormRelation;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Kernel tests for the LegalNormRelation entity.
 *
 * Verifies CRUD operations, field validation, relation types,
 * and references to LegalNorm entities.
 *
 * @group jaraba_legal_knowledge
 */
class LegalNormRelationEntityTest extends KernelTestBase {

  /**
   * KERNEL-TEST-DEPS-001: List ALL required modules explicitly.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'options',
    'jaraba_legal_knowledge',
  ];

  /**
   * {@inheritdoc}
   *
   * KERNEL-SYNTH-001: Register synthetic services for unloaded modules.
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);
    $container->register('ecosistema_jaraba_core.tenant_context')->setSynthetic(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container->set(
      'ecosistema_jaraba_core.tenant_context',
      $this->getMockBuilder('Drupal\ecosistema_jaraba_core\Service\TenantContextService')
        ->disableOriginalConstructor()
        ->getMock()
    );

    $this->installEntitySchema('user');
    $this->installEntitySchema('legal_norm');
    $this->installEntitySchema('legal_norm_relation');
  }

  /**
   * Tests that a LegalNormRelation can be created, saved and reloaded.
   */
  public function testCreateAndLoad(): void {
    // Create two norms to relate.
    $normStorage = $this->container->get('entity_type.manager')->getStorage('legal_norm');

    $sourceNorm = $normStorage->create([
      'title' => 'Ley 39/2015 LPAC',
      'norm_type' => 'ley',
      'scope' => 'nacional',
      'status' => 'vigente',
      'tenant_id' => 1,
    ]);
    $sourceNorm->save();

    $targetNorm = $normStorage->create([
      'title' => 'Ley 30/1992 LRJPAC',
      'norm_type' => 'ley',
      'scope' => 'nacional',
      'status' => 'derogada',
      'tenant_id' => 1,
    ]);
    $targetNorm->save();

    // Create the relation.
    $relationStorage = $this->container->get('entity_type.manager')->getStorage('legal_norm_relation');
    $relation = $relationStorage->create([
      'source_norm_id' => $sourceNorm->id(),
      'target_norm_id' => $targetNorm->id(),
      'relation_type' => 'deroga_total',
      'affected_articles' => json_encode(['art. 1', 'art. 2', 'disposicion transitoria']),
      'effective_date' => strtotime('2015-10-02'),
      'metadata' => json_encode(['boe_ref' => 'BOE-A-2015-10565']),
      'tenant_id' => 1,
    ]);
    $relation->save();

    // Reload and verify.
    $loaded = $relationStorage->load($relation->id());
    $this->assertNotNull($loaded);
    $this->assertInstanceOf(LegalNormRelation::class, $loaded);
    $this->assertSame('deroga_total', $loaded->get('relation_type')->value);
    $this->assertSame((int) $sourceNorm->id(), (int) $loaded->get('source_norm_id')->target_id);
    $this->assertSame((int) $targetNorm->id(), (int) $loaded->get('target_norm_id')->target_id);

    // Verify JSON fields.
    $affected = json_decode($loaded->get('affected_articles')->value, TRUE);
    $this->assertCount(3, $affected);
    $this->assertContains('art. 1', $affected);

    $metadata = json_decode($loaded->get('metadata')->value, TRUE);
    $this->assertSame('BOE-A-2015-10565', $metadata['boe_ref']);
  }

  /**
   * Tests all 10 relation types are valid.
   */
  public function testAllRelationTypesValid(): void {
    $expectedTypes = array_keys(LegalNormRelation::RELATION_TYPES);
    $this->assertCount(10, $expectedTypes);
    $this->assertContains('deroga_total', $expectedTypes);
    $this->assertContains('deroga_parcial', $expectedTypes);
    $this->assertContains('modifica', $expectedTypes);
    $this->assertContains('desarrolla', $expectedTypes);
    $this->assertContains('transpone', $expectedTypes);
    $this->assertContains('cita', $expectedTypes);
    $this->assertContains('complementa', $expectedTypes);
    $this->assertContains('prevalece_sobre', $expectedTypes);
    $this->assertContains('es_especial_de', $expectedTypes);
    $this->assertContains('sustituye', $expectedTypes);
  }

  /**
   * Tests entity deletion.
   */
  public function testDelete(): void {
    $normStorage = $this->container->get('entity_type.manager')->getStorage('legal_norm');
    $norm = $normStorage->create([
      'title' => 'Test Norm',
      'norm_type' => 'ley',
      'scope' => 'nacional',
      'status' => 'vigente',
      'tenant_id' => 1,
    ]);
    $norm->save();

    $relationStorage = $this->container->get('entity_type.manager')->getStorage('legal_norm_relation');
    $relation = $relationStorage->create([
      'source_norm_id' => $norm->id(),
      'target_norm_id' => $norm->id(),
      'relation_type' => 'cita',
      'tenant_id' => 1,
    ]);
    $relation->save();

    $id = $relation->id();
    $relation->delete();

    $this->assertNull($relationStorage->load($id));
  }

  /**
   * Tests that entity implements required interfaces (ENTITY-001).
   */
  public function testEntityInterfaces(): void {
    $this->assertTrue(
      is_subclass_of(LegalNormRelation::class, 'Drupal\user\EntityOwnerInterface'),
      'LegalNormRelation must implement EntityOwnerInterface'
    );
    $this->assertTrue(
      is_subclass_of(LegalNormRelation::class, 'Drupal\Core\Entity\EntityChangedInterface'),
      'LegalNormRelation must implement EntityChangedInterface'
    );
  }

}
