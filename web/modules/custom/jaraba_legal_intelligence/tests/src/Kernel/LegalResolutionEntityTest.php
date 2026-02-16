<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal_intelligence\Kernel;

use Drupal\jaraba_legal_intelligence\Entity\LegalResolution;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Kernel tests for the LegalResolution entity.
 *
 * Verifies entity CRUD operations, helper methods (isEuSource, getTopics,
 * getCitedLegislation, formatCitation) and field persistence against a real
 * SQLite database with full Drupal bootstrap.
 *
 * @group jaraba_legal_intelligence
 */
class LegalResolutionEntityTest extends KernelTestBase {

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
    'taxonomy',
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
    $this->installConfig(['jaraba_legal_intelligence']);
  }

  /**
   * Tests that a LegalResolution entity can be created, saved and reloaded.
   */
  public function testEntityCreation(): void {
    $entity = $this->createResolution();

    $this->assertNotNull($entity->id(), 'Entity should have an ID after save.');

    // Reload from storage.
    $storage = \Drupal::entityTypeManager()->getStorage('legal_resolution');
    $loaded = $storage->load($entity->id());

    $this->assertNotNull($loaded, 'Entity should be loadable from storage.');
    $this->assertEquals('Test Resolution', $loaded->get('title')->value);
    $this->assertEquals('dgt', $loaded->get('source_id')->value);
    $this->assertEquals('V0123-24', $loaded->get('external_ref')->value);
    $this->assertEquals('consulta_vinculante', $loaded->get('resolution_type')->value);
    $this->assertEquals('DGT', $loaded->get('issuing_body')->value);
    $this->assertEquals('nacional', $loaded->get('jurisdiction')->value);
    $this->assertEquals('vigente', $loaded->get('status_legal')->value);
  }

  /**
   * Tests isEuSource() returns TRUE for TJUE source.
   */
  public function testIsEuSourceReturnsTrueForTjue(): void {
    $entity = $this->createResolution([
      'source_id' => 'tjue',
      'external_ref' => 'C-415/11',
    ]);

    $this->assertTrue($entity->isEuSource(), 'TJUE should be recognized as an EU source.');
  }

  /**
   * Tests isEuSource() returns FALSE for CENDOJ source.
   */
  public function testIsEuSourceReturnsFalseForCendoj(): void {
    $entity = $this->createResolution([
      'source_id' => 'cendoj',
      'external_ref' => 'STS-1234-2024',
    ]);

    $this->assertFalse($entity->isEuSource(), 'CENDOJ should NOT be recognized as an EU source.');
  }

  /**
   * Tests isEuSource() for all 7 EU sources.
   */
  public function testIsEuSourceForAllEuSources(): void {
    $euSources = ['tjue', 'eurlex', 'tedh', 'edpb', 'eba', 'esma', 'ag_tjue'];

    foreach ($euSources as $index => $sourceId) {
      $entity = $this->createResolution([
        'source_id' => $sourceId,
        'external_ref' => 'EU-REF-' . $index,
      ]);

      $this->assertTrue(
        $entity->isEuSource(),
        sprintf('Source "%s" should be recognized as an EU source.', $sourceId)
      );
    }
  }

  /**
   * Tests getTopics() decodes JSON string into PHP array.
   */
  public function testGetTopicsDecodesJson(): void {
    $entity = $this->createResolution([
      'external_ref' => 'TOPICS-TEST-01',
      'topics' => '["fiscal","laboral"]',
    ]);

    $topics = $entity->getTopics();

    $this->assertIsArray($topics);
    $this->assertCount(2, $topics);
    $this->assertContains('fiscal', $topics);
    $this->assertContains('laboral', $topics);
  }

  /**
   * Tests getTopics() returns empty array when topics field is NULL.
   */
  public function testGetTopicsReturnsEmptyForNull(): void {
    $entity = $this->createResolution([
      'external_ref' => 'TOPICS-NULL-01',
    ]);

    $topics = $entity->getTopics();

    $this->assertIsArray($topics);
    $this->assertEmpty($topics, 'getTopics() should return empty array when topics is NULL.');
  }

  /**
   * Tests getCitedLegislation() decodes JSON into PHP array.
   */
  public function testGetCitedLegislationDecodesJson(): void {
    $legislation = [
      ['law' => 'Ley 35/2006', 'articles' => ['art. 33', 'art. 34']],
      ['law' => 'Real Decreto 439/2007', 'articles' => ['art. 49']],
    ];
    $entity = $this->createResolution([
      'external_ref' => 'LEGIS-TEST-01',
      'cited_legislation' => json_encode($legislation),
    ]);

    $result = $entity->getCitedLegislation();

    $this->assertIsArray($result);
    $this->assertCount(2, $result);
    $this->assertEquals('Ley 35/2006', $result[0]['law']);
    $this->assertEquals(['art. 33', 'art. 34'], $result[0]['articles']);
  }

  /**
   * Tests formatCitation() produces non-empty string for 'formal' format.
   */
  public function testFormatCitation(): void {
    $entity = $this->createResolution([
      'external_ref' => 'V0456-24',
      'resolution_type' => 'consulta_vinculante',
      'issuing_body' => 'DGT',
      'key_holdings' => 'La ganancia patrimonial se calcula conforme al art. 33 LIRPF.',
    ]);

    $citation = $entity->formatCitation('formal');

    $this->assertNotEmpty($citation, 'Formal citation should produce a non-empty string.');
    $this->assertStringContainsString('V0456-24', $citation);
    $this->assertStringContainsString('DGT', $citation);
    $this->assertStringContainsString('la Consulta Vinculante', $citation);

    // Test 'resumida' format.
    $resumida = $entity->formatCitation('resumida');
    $this->assertNotEmpty($resumida);
    $this->assertStringContainsString('V0456-24', $resumida);

    // Test 'bibliografica' format.
    $bibliografica = $entity->formatCitation('bibliografica');
    $this->assertNotEmpty($bibliografica);

    // Test 'nota_al_pie' format.
    $notaPie = $entity->formatCitation('nota_al_pie');
    $this->assertNotEmpty($notaPie);
    $this->assertStringContainsString('Vid.', $notaPie);
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
