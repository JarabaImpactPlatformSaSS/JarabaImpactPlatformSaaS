<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal_intelligence\Kernel;

use Drupal\jaraba_legal_intelligence\Entity\LegalResolution;
use Drupal\jaraba_legal_intelligence\Entity\LegalSource;
use Drupal\jaraba_legal_intelligence\Service\LegalIngestionService;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Kernel tests for the Legal Ingestion pipeline.
 *
 * Verifies frequency interval constants, entity deduplication logic,
 * field persistence for LegalResolution and LegalSource entities, and
 * entity-level uniqueness constraints against a real SQLite database.
 *
 * @group jaraba_legal_intelligence
 */
class LegalIngestionTest extends KernelTestBase {

  /**
   * Modules required for this test.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'options',
    'datetime',
    'jaraba_legal_intelligence',
  ];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);
    if (!$container->hasDefinition('ai.provider')) {
      $container->register('ai.provider', \stdClass::class);
    }
    if (!$container->hasDefinition('ecosistema_jaraba_core.tenant_context')) {
      $container->register('ecosistema_jaraba_core.tenant_context', \stdClass::class);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('legal_resolution');
    $this->installEntitySchema('legal_source');
    $this->installConfig(['jaraba_legal_intelligence']);
  }

  /**
   * Tests that FREQUENCY_INTERVALS constant has the expected values.
   *
   * Uses reflection to access the protected constant on LegalIngestionService.
   */
  public function testFrequencyIntervalsConstant(): void {
    $reflection = new \ReflectionClass(LegalIngestionService::class);
    $constant = $reflection->getConstant('FREQUENCY_INTERVALS');

    $this->assertIsArray($constant);
    $this->assertArrayHasKey('daily', $constant);
    $this->assertArrayHasKey('weekly', $constant);
    $this->assertArrayHasKey('monthly', $constant);
    $this->assertEquals(86400, $constant['daily'], 'Daily interval should be 86400 seconds (24h).');
    $this->assertEquals(604800, $constant['weekly'], 'Weekly interval should be 604800 seconds (7d).');
    $this->assertEquals(2592000, $constant['monthly'], 'Monthly interval should be 2592000 seconds (30d).');
  }

  /**
   * Tests deduplication by external_ref using entity query.
   *
   * Creates a resolution with a specific external_ref and source_id, then
   * verifies that loading by properties finds the existing entity, which is
   * the same check the ingestion service performs before creating duplicates.
   */
  public function testDeduplicationByExternalRef(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('legal_resolution');

    // Create first resolution.
    $entity1 = $this->createResolution([
      'external_ref' => 'V0123-24',
      'source_id' => 'dgt',
    ]);

    // Query by external_ref (same check the ingestion service performs).
    $existing = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('external_ref', 'V0123-24')
      ->execute();

    $this->assertCount(1, $existing, 'Should find exactly one entity with external_ref V0123-24.');
    $this->assertContains($entity1->id(), array_values($existing));

    // Attempt to create second entity with same external_ref should fail
    // validation due to UniqueField constraint.
    $entity2 = LegalResolution::create([
      'title' => 'Duplicate Resolution',
      'source_id' => 'dgt',
      'external_ref' => 'V0123-24',
      'resolution_type' => 'consulta_vinculante',
      'issuing_body' => 'DGT',
      'date_issued' => '2024-03-16',
      'status_legal' => 'vigente',
    ]);

    $violations = $entity2->validate();
    $this->assertGreaterThan(0, $violations->count(), 'Duplicate external_ref should produce validation violations.');
  }

  /**
   * Tests deduplication by content_hash using entity query.
   *
   * Creates a resolution with a content_hash, then queries by that hash
   * to simulate the ingestion service's second-level dedup check.
   */
  public function testDeduplicationByContentHash(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('legal_resolution');

    $testText = 'El contribuyente debe declarar la ganancia patrimonial conforme al art. 33 LIRPF.';
    $contentHash = hash('sha256', $testText);

    // Create first resolution with content_hash.
    $entity1 = $this->createResolution([
      'external_ref' => 'HASH-TEST-01',
      'content_hash' => $contentHash,
      'full_text' => $testText,
    ]);

    // Query by content_hash (same check the ingestion service performs).
    $hashDuplicate = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('content_hash', $contentHash)
      ->execute();

    $this->assertCount(1, $hashDuplicate, 'Should find exactly one entity with the given content_hash.');
    $this->assertContains($entity1->id(), array_values($hashDuplicate));

    // Create second resolution with different external_ref but same hash.
    $entity2 = $this->createResolution([
      'external_ref' => 'HASH-TEST-02',
      'content_hash' => $contentHash,
      'full_text' => $testText,
    ]);

    // Now querying by hash should return 2 entities (no unique constraint on hash).
    // The ingestion service checks BEFORE creating — this confirms the query works.
    $hashResults = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('content_hash', $contentHash)
      ->execute();

    $this->assertCount(2, $hashResults, 'Two entities with same content_hash should exist (dedup is application-level).');
  }

  /**
   * Tests that all key fields persist correctly after entity save and reload.
   */
  public function testEntityFieldsPersistence(): void {
    $entity = $this->createResolution([
      'title' => 'STS 1234/2024 Sala 3a',
      'source_id' => 'cendoj',
      'external_ref' => 'STS-1234-2024',
      'resolution_type' => 'sentencia',
      'issuing_body' => 'TS',
      'jurisdiction' => 'fiscal',
      'date_issued' => '2024-06-10',
      'status_legal' => 'vigente',
      'content_hash' => 'abcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890',
      'original_url' => 'https://www.poderjudicial.es/search/contenidos.action?action=contentpdf&databasematch=TS&reference=1234',
      'celex_number' => NULL,
      'language_original' => 'es',
    ]);

    $storage = \Drupal::entityTypeManager()->getStorage('legal_resolution');
    $loaded = $storage->load($entity->id());

    $this->assertEquals('STS 1234/2024 Sala 3a', $loaded->get('title')->value);
    $this->assertEquals('cendoj', $loaded->get('source_id')->value);
    $this->assertEquals('STS-1234-2024', $loaded->get('external_ref')->value);
    $this->assertEquals('sentencia', $loaded->get('resolution_type')->value);
    $this->assertEquals('TS', $loaded->get('issuing_body')->value);
    $this->assertEquals('fiscal', $loaded->get('jurisdiction')->value);
    $this->assertEquals('vigente', $loaded->get('status_legal')->value);
    $this->assertEquals('abcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890', $loaded->get('content_hash')->value);
    $this->assertEquals('es', $loaded->get('language_original')->value);
  }

  /**
   * Tests that a LegalSource entity can be created, saved and reloaded.
   */
  public function testSourceEntityCreation(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('legal_source');

    $source = $storage->create([
      'name' => 'Centro de Documentacion Judicial',
      'machine_name' => 'cendoj',
      'base_url' => 'https://www.poderjudicial.es/search/',
      'spider_class' => 'Drupal\jaraba_legal_intelligence\Service\Spider\CendojSpider',
      'frequency' => 'daily',
      'is_active' => TRUE,
      'priority' => 1,
      'total_documents' => 0,
      'error_count' => 0,
    ]);
    $source->save();

    $this->assertNotNull($source->id(), 'LegalSource entity should have an ID after save.');

    // Reload from storage.
    $loaded = $storage->load($source->id());

    $this->assertNotNull($loaded, 'LegalSource entity should be loadable from storage.');
    $this->assertEquals('Centro de Documentacion Judicial', $loaded->get('name')->value);
    $this->assertEquals('cendoj', $loaded->get('machine_name')->value);
    $this->assertEquals('daily', $loaded->get('frequency')->value);
    $this->assertTrue((bool) $loaded->get('is_active')->value);
    $this->assertEquals(1, (int) $loaded->get('priority')->value);
    $this->assertEquals(0, (int) $loaded->get('total_documents')->value);
    $this->assertEquals(0, (int) $loaded->get('error_count')->value);
  }

  /**
   * Creates a LegalResolution entity with sensible defaults.
   *
   * @param array $values
   *   Field values to override defaults.
   *
   * @return \Drupal\jaraba_legal_intelligence\Entity\LegalResolution
   *   The saved entity.
   */
  private function createResolution(array $values = []): LegalResolution {
    $defaults = [
      'title' => 'Test Resolution',
      'source_id' => 'dgt',
      'external_ref' => 'V0123-24',
      'resolution_type' => 'consulta_vinculante',
      'issuing_body' => 'DGT',
      'jurisdiction' => 'nacional',
      'date_issued' => '2024-03-15',
      'status_legal' => 'vigente',
    ];
    $entity = LegalResolution::create($values + $defaults);
    $entity->save();
    return $entity;
  }

}
