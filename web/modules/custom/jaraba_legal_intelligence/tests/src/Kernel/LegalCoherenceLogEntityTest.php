<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal_intelligence\Kernel;

use Drupal\jaraba_legal_intelligence\Entity\LegalCoherenceLog;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Kernel tests for the LegalCoherenceLog entity.
 *
 * Verifies CRUD operations, JSON fields, score persistence,
 * and audit trail integrity. EU AI Act Art. 12 compliance.
 *
 * @group jaraba_legal_intelligence
 */
class LegalCoherenceLogEntityTest extends KernelTestBase {

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
    'jaraba_legal_intelligence',
  ];

  /**
   * {@inheritdoc}
   *
   * KERNEL-SYNTH-001: Register synthetic services for unloaded modules.
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
    $this->installEntitySchema('legal_coherence_log');
  }

  /**
   * Tests that a LegalCoherenceLog can be created, saved and reloaded.
   */
  public function testCreateAndLoad(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('legal_coherence_log');

    $validatorResults = [
      'violations' => [
        ['type' => 'hierarchy_inversion', 'severity' => 'critical'],
      ],
      'warnings' => [],
      'action' => 'block',
    ];

    $log = $storage->create([
      'query_text' => 'Puede un decreto ley derogar una ley organica?',
      'intent_type' => 'legal',
      'coherence_score' => 0.350,
      'validator_results' => json_encode($validatorResults),
      'disclaimer_appended' => FALSE,
      'retries_needed' => 2,
      'blocked' => TRUE,
      'block_reason' => 'Hierarchy inversion: RD-ley no puede derogar LO',
      'response_snippet' => 'Segun el Real Decreto-ley...',
      'vertical' => 'jarabalex',
      'tenant_id' => 1,
      'trace_id' => 'abc-def-123-456',
    ]);
    $log->save();

    // Reload and verify all fields.
    $loaded = $storage->load($log->id());
    $this->assertNotNull($loaded);
    $this->assertInstanceOf(LegalCoherenceLog::class, $loaded);

    $this->assertSame('Puede un decreto ley derogar una ley organica?', $loaded->get('query_text')->value);
    $this->assertSame('legal', $loaded->get('intent_type')->value);
    $this->assertEqualsWithDelta(0.350, (float) $loaded->get('coherence_score')->value, 0.001);
    $this->assertTrue((bool) $loaded->get('blocked')->value);
    $this->assertSame(2, (int) $loaded->get('retries_needed')->value);
    $this->assertSame('jarabalex', $loaded->get('vertical')->value);
    $this->assertSame('abc-def-123-456', $loaded->get('trace_id')->value);

    // Verify JSON field.
    $results = json_decode($loaded->get('validator_results')->value, TRUE);
    $this->assertCount(1, $results['violations']);
    $this->assertSame('hierarchy_inversion', $results['violations'][0]['type']);
  }

  /**
   * Tests log with verifier results (semantic verification).
   */
  public function testVerifierResults(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('legal_coherence_log');

    $verifierResults = [
      'scores_detail' => [
        'jerarquia_normativa' => 0.9,
        'primacia_ue' => 1.0,
        'competencias' => 0.8,
      ],
      'issues' => [],
      'is_coherent' => TRUE,
      'summary' => 'Respuesta juridicamente coherente.',
      'premise_issues' => [],
      'citation_alignment' => [
        ['claim' => 'Art. 149.1 CE', 'status' => 'supported'],
      ],
    ];

    $log = $storage->create([
      'query_text' => 'Competencias exclusivas del Estado',
      'intent_type' => 'legal',
      'coherence_score' => 0.900,
      'verifier_results' => json_encode($verifierResults),
      'norm_citations' => json_encode(['Art. 149.1 CE', 'Art. 148 CE']),
      'blocked' => FALSE,
      'vertical' => 'jarabalex',
      'tenant_id' => 1,
    ]);
    $log->save();

    $loaded = $storage->load($log->id());
    $verResults = json_decode($loaded->get('verifier_results')->value, TRUE);
    $this->assertTrue($verResults['is_coherent']);
    $this->assertSame(0.9, $verResults['scores_detail']['jerarquia_normativa']);

    $citations = json_decode($loaded->get('norm_citations')->value, TRUE);
    $this->assertCount(2, $citations);
    $this->assertContains('Art. 149.1 CE', $citations);
  }

  /**
   * Tests that entity implements required interfaces (ENTITY-001).
   */
  public function testEntityInterfaces(): void {
    $this->assertTrue(
      is_subclass_of(LegalCoherenceLog::class, 'Drupal\user\EntityOwnerInterface'),
      'LegalCoherenceLog must implement EntityOwnerInterface'
    );
    $this->assertTrue(
      is_subclass_of(LegalCoherenceLog::class, 'Drupal\Core\Entity\EntityChangedInterface'),
      'LegalCoherenceLog must implement EntityChangedInterface'
    );
  }

  /**
   * Tests deletion of log entries.
   */
  public function testDelete(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('legal_coherence_log');

    $log = $storage->create([
      'query_text' => 'Test query',
      'intent_type' => 'non_legal',
      'coherence_score' => 1.0,
      'blocked' => FALSE,
      'vertical' => 'demo',
      'tenant_id' => 1,
    ]);
    $log->save();

    $id = $log->id();
    $log->delete();
    $this->assertNull($storage->load($id));
  }

}
