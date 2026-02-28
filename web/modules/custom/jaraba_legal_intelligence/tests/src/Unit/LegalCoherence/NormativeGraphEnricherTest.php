<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal_intelligence\Unit\LegalCoherence;

use Drupal\jaraba_legal_intelligence\LegalCoherence\NormativeGraphEnricher;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Unit tests for NormativeGraphEnricher (LCIS Layer 3).
 *
 * @coversDefaultClass \Drupal\jaraba_legal_intelligence\LegalCoherence\NormativeGraphEnricher
 * @group jaraba_legal_intelligence
 */
class NormativeGraphEnricherTest extends TestCase {

  protected NormativeGraphEnricher $enricher;

  protected function setUp(): void {
    parent::setUp();
    $this->enricher = new NormativeGraphEnricher(NULL, new NullLogger());
  }

  // ---------------------------------------------------------------
  // enrichRetrieval() — basic behavior.
  // ---------------------------------------------------------------

  /**
   * @covers ::enrichRetrieval
   */
  public function testEnrichRetrievalEmptyResults(): void {
    $result = $this->enricher->enrichRetrieval([]);
    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * @covers ::enrichRetrieval
   */
  public function testEnrichRetrievalAddsMetadata(): void {
    $rag = [
      [
        'score' => 0.9,
        'payload' => [
          'title' => 'Ley Organica 3/2018',
          'status_legal' => 'vigente',
          'publication_date' => '2018-12-05',
        ],
      ],
    ];

    $result = $this->enricher->enrichRetrieval($rag);

    $this->assertCount(1, $result);
    $this->assertArrayHasKey('final_score', $result[0]);
    $this->assertArrayHasKey('authority_weight', $result[0]);
    $this->assertArrayHasKey('norm_type_detected', $result[0]);
    $this->assertArrayHasKey('recency_bonus', $result[0]);
    $this->assertArrayHasKey('vigencia_status', $result[0]);
  }

  /**
   * @covers ::enrichRetrieval
   */
  public function testEnrichRetrievalDetectsNormType(): void {
    $rag = [
      [
        'score' => 0.8,
        'payload' => [
          'title' => 'Ley Organica 1/2024',
          'status_legal' => 'vigente',
        ],
      ],
    ];

    $result = $this->enricher->enrichRetrieval($rag);

    $this->assertSame('ley_organica', $result[0]['norm_type_detected']);
  }

  /**
   * @covers ::enrichRetrieval
   */
  public function testEnrichRetrievalUsesExplicitNormType(): void {
    $rag = [
      [
        'score' => 0.8,
        'payload' => [
          'title' => 'Some document',
          'norm_type' => 'constitucion',
          'status_legal' => 'vigente',
        ],
      ],
    ];

    $result = $this->enricher->enrichRetrieval($rag);

    $this->assertSame('constitucion', $result[0]['norm_type_detected']);
  }

  // ---------------------------------------------------------------
  // Derogation filter.
  // ---------------------------------------------------------------

  /**
   * @covers ::enrichRetrieval
   */
  public function testFiltersTotallyDerogated(): void {
    $rag = [
      [
        'score' => 0.9,
        'payload' => [
          'title' => 'Ley 30/1992',
          'status_legal' => 'derogada_total',
        ],
      ],
      [
        'score' => 0.7,
        'payload' => [
          'title' => 'Ley 39/2015',
          'status_legal' => 'vigente',
        ],
      ],
    ];

    $result = $this->enricher->enrichRetrieval($rag);

    $this->assertCount(1, $result);
    $this->assertStringContainsString('39/2015', $result[0]['payload']['title']);
  }

  /**
   * @covers ::enrichRetrieval
   */
  public function testFiltersDerogatedStatus(): void {
    $rag = [
      [
        'score' => 0.9,
        'payload' => ['title' => 'Norma antigua', 'status_legal' => 'derogada'],
      ],
    ];

    $result = $this->enricher->enrichRetrieval($rag);
    $this->assertEmpty($result);
  }

  /**
   * @covers ::enrichRetrieval
   */
  public function testFiltersAnuladaStatus(): void {
    $rag = [
      [
        'score' => 0.9,
        'payload' => ['title' => 'Norma nula', 'status_legal' => 'anulada'],
      ],
    ];

    $result = $this->enricher->enrichRetrieval($rag);
    $this->assertEmpty($result);
  }

  /**
   * @covers ::enrichRetrieval
   */
  public function testPartialDerogationAddsWarning(): void {
    $rag = [
      [
        'score' => 0.9,
        'payload' => [
          'title' => 'Ley 39/2015',
          'status_legal' => 'derogada_parcial',
        ],
      ],
    ];

    $result = $this->enricher->enrichRetrieval($rag);

    $this->assertCount(1, $result);
    $this->assertArrayHasKey('derogation_warning', $result[0]);
    $this->assertStringContainsString('derogados o modificados', $result[0]['derogation_warning']);
  }

  // ---------------------------------------------------------------
  // Ordering by final_score.
  // ---------------------------------------------------------------

  /**
   * @covers ::enrichRetrieval
   */
  public function testReorderedByFinalScoreDescending(): void {
    $rag = [
      [
        'score' => 0.5,
        'payload' => [
          'title' => 'Ordenanza municipal de Sevilla',
          'status_legal' => 'vigente',
          'publication_date' => '2020-01-01',
        ],
      ],
      [
        'score' => 0.6,
        'payload' => [
          'title' => 'Ley Organica 2/2024',
          'status_legal' => 'vigente',
          'publication_date' => '2024-01-01',
        ],
      ],
    ];

    $result = $this->enricher->enrichRetrieval($rag);

    $this->assertCount(2, $result);
    // LO should rank higher due to authority weight.
    $this->assertGreaterThanOrEqual($result[1]['final_score'], $result[0]['final_score']);
  }

  // ---------------------------------------------------------------
  // Authority-Aware Ranking weights.
  // ---------------------------------------------------------------

  /**
   * @covers ::enrichRetrieval
   */
  public function testConstitutionHigherAuthorityThanLey(): void {
    $rag = [
      [
        'score' => 0.8,
        'payload' => [
          'norm_type' => 'constitucion',
          'title' => 'CE',
          'status_legal' => 'vigente',
          'publication_date' => '1978-12-29',
        ],
      ],
      [
        'score' => 0.8,
        'payload' => [
          'norm_type' => 'ley_ordinaria',
          'title' => 'Ley 39/2015',
          'status_legal' => 'vigente',
          'publication_date' => '2015-10-01',
        ],
      ],
    ];

    $result = $this->enricher->enrichRetrieval($rag);

    // With same semantic score, CE has higher authority weight.
    $ceResult = NULL;
    $leyResult = NULL;
    foreach ($result as $r) {
      if ($r['norm_type_detected'] === 'constitucion') {
        $ceResult = $r;
      }
      if ($r['norm_type_detected'] === 'ley_ordinaria') {
        $leyResult = $r;
      }
    }

    $this->assertNotNull($ceResult);
    $this->assertNotNull($leyResult);
    $this->assertGreaterThan($leyResult['authority_weight'], $ceResult['authority_weight']);
  }

  // ---------------------------------------------------------------
  // CCAA exclusive competence bonus.
  // ---------------------------------------------------------------

  /**
   * @covers ::enrichRetrieval
   */
  public function testCcaaExclusiveCompetenceBonus(): void {
    $rag = [
      [
        'score' => 0.8,
        'payload' => [
          'norm_type' => 'ley_autonomica',
          'title' => 'Ley de Andalucia 7/2002 de urbanismo',
          'status_legal' => 'vigente',
          'publication_date' => '2022-01-01',
        ],
      ],
    ];

    $context = [
      'territory' => 'Andalucia',
      'subject_areas' => ['urbanismo'],
    ];

    $result = $this->enricher->enrichRetrieval($rag, $context);

    $this->assertCount(1, $result);
    // ley_autonomica base weight is 0.55, with bonus should be 0.67.
    $this->assertGreaterThan(0.55, $result[0]['authority_weight']);
  }

  // ---------------------------------------------------------------
  // Territory warning.
  // ---------------------------------------------------------------

  /**
   * @covers ::enrichRetrieval
   */
  public function testTerritoryWarningDifferentCcaa(): void {
    $rag = [
      [
        'score' => 0.8,
        'payload' => [
          'title' => 'Ley de Cataluna 2/2020',
          'status_legal' => 'vigente',
          'autonomous_community' => 'Cataluna',
        ],
      ],
    ];

    $context = ['territory' => 'Andalucia'];

    $result = $this->enricher->enrichRetrieval($rag, $context);

    $this->assertArrayHasKey('territory_warning', $result[0]);
    $this->assertStringContainsString('Cataluna', $result[0]['territory_warning']);
    $this->assertStringContainsString('Andalucia', $result[0]['territory_warning']);
  }

  /**
   * @covers ::enrichRetrieval
   */
  public function testNoTerritoryWarningSameCcaa(): void {
    $rag = [
      [
        'score' => 0.8,
        'payload' => [
          'title' => 'Ley de Andalucia',
          'status_legal' => 'vigente',
          'autonomous_community' => 'Andalucia',
        ],
      ],
    ];

    $context = ['territory' => 'Andalucia'];

    $result = $this->enricher->enrichRetrieval($rag, $context);

    $this->assertArrayNotHasKey('territory_warning', $result[0]);
  }

  // ---------------------------------------------------------------
  // Recency bonus.
  // ---------------------------------------------------------------

  /**
   * @covers ::enrichRetrieval
   */
  public function testRecentNormHigherRecencyBonus(): void {
    $rag = [
      [
        'score' => 0.8,
        'payload' => [
          'title' => 'Ley 1/2025',
          'norm_type' => 'ley_ordinaria',
          'status_legal' => 'vigente',
          'publication_date' => '2025-06-01',
        ],
      ],
      [
        'score' => 0.8,
        'payload' => [
          'title' => 'Ley 2/2000',
          'norm_type' => 'ley_ordinaria',
          'status_legal' => 'vigente',
          'publication_date' => '2000-01-01',
        ],
      ],
    ];

    $result = $this->enricher->enrichRetrieval($rag, ['query_date' => '2026-02-28']);

    $recent = NULL;
    $old = NULL;
    foreach ($result as $r) {
      if (str_contains($r['payload']['title'], '2025')) {
        $recent = $r;
      }
      if (str_contains($r['payload']['title'], '2000')) {
        $old = $r;
      }
    }

    $this->assertNotNull($recent);
    $this->assertNotNull($old);
    $this->assertGreaterThan($old['recency_bonus'], $recent['recency_bonus']);
  }

  /**
   * @covers ::enrichRetrieval
   */
  public function testMissingDateDefaultRecencyBonus(): void {
    $rag = [
      [
        'score' => 0.8,
        'payload' => [
          'title' => 'Norma sin fecha',
          'norm_type' => 'ley_ordinaria',
          'status_legal' => 'vigente',
        ],
      ],
    ];

    $result = $this->enricher->enrichRetrieval($rag);
    $this->assertSame(0.3, $result[0]['recency_bonus']);
  }

  // ---------------------------------------------------------------
  // generatePromptAnnotations().
  // ---------------------------------------------------------------

  /**
   * @covers ::generatePromptAnnotations
   */
  public function testAnnotationsEmptyResults(): void {
    $result = $this->enricher->generatePromptAnnotations([]);
    $this->assertSame('', $result);
  }

  /**
   * @covers ::generatePromptAnnotations
   */
  public function testAnnotationsWithDerogationWarning(): void {
    $enriched = [
      ['derogation_warning' => 'Norma con articulos derogados'],
    ];

    $result = $this->enricher->generatePromptAnnotations($enriched);

    $this->assertStringContainsString('ATENCION', $result);
    $this->assertStringContainsString('articulos derogados', $result);
  }

  /**
   * @covers ::generatePromptAnnotations
   */
  public function testAnnotationsWithTerritoryWarning(): void {
    $enriched = [
      ['territory_warning' => 'Norma de Cataluna aplicada a Andalucia'],
    ];

    $result = $this->enricher->generatePromptAnnotations($enriched);

    $this->assertStringContainsString('ATENCION', $result);
    $this->assertStringContainsString('otras Comunidades Autonomas', $result);
  }

  /**
   * @covers ::generatePromptAnnotations
   */
  public function testAnnotationsAlwaysIncludeOrderingNote(): void {
    $enriched = [['final_score' => 0.9]];

    $result = $this->enricher->generatePromptAnnotations($enriched);

    $this->assertStringContainsString('autoridad jerarquica', $result);
  }

  /**
   * @covers ::generatePromptAnnotations
   */
  public function testAnnotationsCombineMultipleWarnings(): void {
    $enriched = [
      ['derogation_warning' => 'Derogada parcialmente', 'territory_warning' => 'Otra CCAA'],
    ];

    $result = $this->enricher->generatePromptAnnotations($enriched);

    $this->assertStringContainsString('articulos derogados', $result);
    $this->assertStringContainsString('otras Comunidades', $result);
    $this->assertStringContainsString('autoridad jerarquica', $result);
  }

  // ---------------------------------------------------------------
  // Final score formula.
  // ---------------------------------------------------------------

  /**
   * Tests final score formula: 0.55 * semantic + 0.30 * authority + 0.15 * recency.
   *
   * @covers ::enrichRetrieval
   */
  public function testFinalScoreFormulaApplied(): void {
    $rag = [
      [
        'score' => 1.0,
        'payload' => [
          'norm_type' => 'constitucion',
          'title' => 'CE',
          'status_legal' => 'vigente',
          'publication_date' => '2025-06-01',
        ],
      ],
    ];

    $result = $this->enricher->enrichRetrieval($rag, ['query_date' => '2026-01-01']);

    // semantic=1.0, authority=0.98 (constitucion weight), recency=1.0 (< 1 year).
    // expected = 1.0*0.55 + 0.98*0.30 + 1.0*0.15 = 0.55 + 0.294 + 0.15 = 0.994.
    $expected = round(1.0 * 0.55 + 0.98 * 0.30 + 1.0 * 0.15, 4);
    $this->assertSame($expected, $result[0]['final_score']);
  }

}
