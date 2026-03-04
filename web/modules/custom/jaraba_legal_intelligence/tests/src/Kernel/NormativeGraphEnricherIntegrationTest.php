<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal_intelligence\Kernel;

use Drupal\jaraba_legal_intelligence\LegalCoherence\NormativeGraphEnricher;
use Drupal\KernelTests\KernelTestBase;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Integration test: NormativeGraphEnricher uses real legal_norm_relation entities.
 *
 * Verifies that the enricher correctly queries the normative graph to detect
 * derogated and modified norms, instead of relying only on v1 heuristics.
 *
 * @group jaraba_legal_intelligence
 */
class NormativeGraphEnricherIntegrationTest extends KernelTestBase {

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
    'datetime',
    'taxonomy',
    'ecosistema_jaraba_core',
    'jaraba_legal_knowledge',
    'jaraba_legal_intelligence',
  ];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);
    $container->register('ai.provider')->setSynthetic(TRUE);
    $container->register('ecosistema_jaraba_core.tenant_context')->setSynthetic(TRUE);
    $container->register('ecosistema_jaraba_core.jarabalex_feature_gate')->setSynthetic(TRUE);
    $container->register('jaraba_ai_agents.tenant_brand_voice')->setSynthetic(TRUE);
    $container->register('jaraba_ai_agents.observability')->setSynthetic(TRUE);
    $container->register('ecosistema_jaraba_core.unified_prompt_builder')->setSynthetic(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container->set('ai.provider', new \stdClass());
    $this->container->set(
      'ecosistema_jaraba_core.tenant_context',
      $this->getMockBuilder('Drupal\ecosistema_jaraba_core\Service\TenantContextService')
        ->disableOriginalConstructor()
        ->getMock()
    );
    $this->container->set(
      'ecosistema_jaraba_core.jarabalex_feature_gate',
      $this->getMockBuilder('Drupal\ecosistema_jaraba_core\Service\JarabaLexFeatureGateService')
        ->disableOriginalConstructor()
        ->getMock()
    );
    $this->container->set('jaraba_ai_agents.tenant_brand_voice', new \stdClass());
    $this->container->set('jaraba_ai_agents.observability', new \stdClass());
    $this->container->set('ecosistema_jaraba_core.unified_prompt_builder', new \stdClass());

    $this->installEntitySchema('user');
    $this->installEntitySchema('legal_norm');
    $this->installEntitySchema('legal_norm_relation');
  }

  /**
   * Tests that enricher filters norms derogated via legal_norm_relation.
   *
   * Scenario: Norm A (vigente in payload) has a legal_norm_relation of type
   * 'deroga_total' pointing at it. The enricher should detect this and
   * exclude Norm A from results, even though payload.status = 'vigente'.
   */
  public function testDerogatedNormFilteredViaRelation(): void {
    $normStorage = $this->container->get('entity_type.manager')->getStorage('legal_norm');

    // Create the derogating norm (source).
    $newLaw = $normStorage->create([
      'title' => 'Ley 39/2015 LPAC',
      'norm_type' => 'ley',
      'scope' => 'nacional',
      'status' => 'vigente',
      'tenant_id' => 1,
    ]);
    $newLaw->save();

    // Create the derogated norm (target).
    $oldLaw = $normStorage->create([
      'title' => 'Ley 30/1992 LRJPAC',
      'norm_type' => 'ley',
      'scope' => 'nacional',
      'status' => 'vigente',
      'tenant_id' => 1,
    ]);
    $oldLaw->save();

    // Create derogation relation.
    $relationStorage = $this->container->get('entity_type.manager')->getStorage('legal_norm_relation');
    $relationStorage->create([
      'source_norm_id' => $newLaw->id(),
      'target_norm_id' => $oldLaw->id(),
      'relation_type' => 'deroga_total',
      'tenant_id' => 1,
      'effective_date' => strtotime('2015-10-02'),
    ])->save();

    // Create enricher with real EntityTypeManager.
    $enricher = new NormativeGraphEnricher(
      $this->container->get('entity_type.manager'),
      new NullLogger(),
    );

    // Simulate RAG results with the derogated norm still marked as 'vigente'.
    $ragResults = [
      [
        'score' => 0.95,
        'payload' => [
          'norm_id' => $oldLaw->id(),
          'status' => 'vigente',
          'norm_type' => 'ley',
          'title' => 'Ley 30/1992 LRJPAC',
          'publication_date' => '1992-11-27',
        ],
      ],
      [
        'score' => 0.85,
        'payload' => [
          'norm_id' => $newLaw->id(),
          'status' => 'vigente',
          'norm_type' => 'ley',
          'title' => 'Ley 39/2015 LPAC',
          'publication_date' => '2015-10-02',
        ],
      ],
    ];

    $enriched = $enricher->enrichRetrieval($ragResults);

    // Old law should be filtered out because of deroga_total relation.
    $this->assertCount(1, $enriched, 'Derogated norm should be filtered out');
    $this->assertStringContainsString('39/2015', $enriched[0]['payload']['title'] ?? '');
  }

  /**
   * Tests that modified norms get derogation_warning annotation.
   */
  public function testModifiedNormGetsWarning(): void {
    $normStorage = $this->container->get('entity_type.manager')->getStorage('legal_norm');

    $modifyingNorm = $normStorage->create([
      'title' => 'Ley 4/2015',
      'norm_type' => 'ley',
      'scope' => 'nacional',
      'status' => 'vigente',
      'tenant_id' => 1,
    ]);
    $modifyingNorm->save();

    $modifiedNorm = $normStorage->create([
      'title' => 'Ley 1/2000 LEC',
      'norm_type' => 'ley',
      'scope' => 'nacional',
      'status' => 'vigente',
      'tenant_id' => 1,
    ]);
    $modifiedNorm->save();

    // Partial derogation relation.
    $relationStorage = $this->container->get('entity_type.manager')->getStorage('legal_norm_relation');
    $relationStorage->create([
      'source_norm_id' => $modifyingNorm->id(),
      'target_norm_id' => $modifiedNorm->id(),
      'relation_type' => 'deroga_parcial',
      'affected_articles' => json_encode(['art. 23', 'art. 24']),
      'tenant_id' => 1,
    ])->save();

    $enricher = new NormativeGraphEnricher(
      $this->container->get('entity_type.manager'),
      new NullLogger(),
    );

    $ragResults = [
      [
        'score' => 0.9,
        'payload' => [
          'norm_id' => $modifiedNorm->id(),
          'status' => 'vigente',
          'norm_type' => 'ley',
          'title' => 'Ley 1/2000 LEC',
          'publication_date' => '2000-01-08',
        ],
      ],
    ];

    $enriched = $enricher->enrichRetrieval($ragResults);

    // Modified norm should still appear but with derogation_warning.
    $this->assertCount(1, $enriched);
    $this->assertArrayHasKey('derogation_warning', $enriched[0]);
  }

  /**
   * Tests getRelationsForNorms returns correct data.
   */
  public function testGetRelationsForNorms(): void {
    $normStorage = $this->container->get('entity_type.manager')->getStorage('legal_norm');

    $norm1 = $normStorage->create([
      'title' => 'Norm 1', 'norm_type' => 'ley', 'scope' => 'nacional', 'status' => 'vigente', 'tenant_id' => 1,
    ]);
    $norm1->save();
    $norm2 = $normStorage->create([
      'title' => 'Norm 2', 'norm_type' => 'ley', 'scope' => 'nacional', 'status' => 'vigente', 'tenant_id' => 1,
    ]);
    $norm2->save();

    $relationStorage = $this->container->get('entity_type.manager')->getStorage('legal_norm_relation');
    $relationStorage->create([
      'source_norm_id' => $norm1->id(),
      'target_norm_id' => $norm2->id(),
      'relation_type' => 'cita',
      'tenant_id' => 1,
    ])->save();

    $enricher = new NormativeGraphEnricher(
      $this->container->get('entity_type.manager'),
      new NullLogger(),
    );

    $relations = $enricher->getRelationsForNorms([(int) $norm1->id()]);
    $this->assertCount(1, $relations);
    $this->assertSame('cita', $relations[0]['relation_type']);
    $this->assertSame((int) $norm2->id(), $relations[0]['target_norm_id']);
  }

}
