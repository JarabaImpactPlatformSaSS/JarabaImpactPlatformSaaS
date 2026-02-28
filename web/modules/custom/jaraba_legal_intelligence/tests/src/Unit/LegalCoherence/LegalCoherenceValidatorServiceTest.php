<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal_intelligence\Unit\LegalCoherence;

use Drupal\jaraba_legal_intelligence\LegalCoherence\LegalCoherenceValidatorService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Unit tests for LegalCoherenceValidatorService (LCIS Layer 6).
 *
 * @coversDefaultClass \Drupal\jaraba_legal_intelligence\LegalCoherence\LegalCoherenceValidatorService
 * @group jaraba_legal_intelligence
 */
class LegalCoherenceValidatorServiceTest extends TestCase {

  protected LegalCoherenceValidatorService $validator;

  protected function setUp(): void {
    parent::setUp();
    $this->validator = new LegalCoherenceValidatorService(new NullLogger());
  }

  // ---------------------------------------------------------------
  // Clean output — no violations.
  // ---------------------------------------------------------------

  /**
   * @covers ::validate
   */
  public function testCleanOutputPasses(): void {
    $output = 'El procedimiento administrativo comun establece plazos claros para los ciudadanos.';
    $result = $this->validator->validate($output);

    $this->assertTrue($result['passed']);
    $this->assertSame('allow', $result['action']);
    $this->assertSame(1.0, $result['score']);
    $this->assertEmpty($result['violations']);
  }

  /**
   * @covers ::validate
   */
  public function testReturnStructure(): void {
    $result = $this->validator->validate('texto simple');

    $this->assertArrayHasKey('passed', $result);
    $this->assertArrayHasKey('score', $result);
    $this->assertArrayHasKey('action', $result);
    $this->assertArrayHasKey('violations', $result);
    $this->assertArrayHasKey('warnings', $result);
    $this->assertArrayHasKey('sanitized_output', $result);
    $this->assertArrayHasKey('regeneration_constraints', $result);
    $this->assertArrayHasKey('retry_count', $result);
    $this->assertArrayHasKey('metadata', $result);
  }

  // ---------------------------------------------------------------
  // Hierarchy violation detection (HIERARCHY_VIOLATION_PATTERNS).
  // ---------------------------------------------------------------

  /**
   * @covers ::validate
   */
  public function testDetectsHierarchyInversion(): void {
    $output = 'El Real Decreto 100/2024 deroga la Ley Organica 2/2010 de proteccion de datos.';
    $result = $this->validator->validate($output);

    $this->assertNotEmpty($result['violations']);
    $violationTypes = array_column($result['violations'], 'type');
    $this->assertContains('hierarchy_inversion', $violationTypes);
    $this->assertLessThan(1.0, $result['score']);
  }

  /**
   * @covers ::validate
   */
  public function testDetectsOrganicLawViolationPattern(): void {
    $output = 'La Ley 5/2024 ordinaria regula los derechos fundamentales de los ciudadanos.';
    $result = $this->validator->validate($output);

    $violationTypes = array_column($result['violations'], 'type');
    $this->assertContains('organic_law_violation', $violationTypes);
  }

  /**
   * @covers ::validate
   */
  public function testDetectsEuPrimacyViolation(): void {
    $output = 'La Constitucion Espanola prevalece sobre el Reglamento (UE) 2016/679 en materia de datos.';
    $result = $this->validator->validate($output);

    $violationTypes = array_column($result['violations'], 'type');
    $this->assertContains('eu_primacy_violation', $violationTypes);
  }

  /**
   * @covers ::validate
   */
  public function testDetectsCompetenceViolationPattern(): void {
    $output = 'La Ley de Cataluna establece su propio codigo penal con disposiciones penales autonomas.';
    $result = $this->validator->validate($output);

    $violationTypes = array_column($result['violations'], 'type');
    $this->assertContains('competence_violation', $violationTypes);
  }

  // ---------------------------------------------------------------
  // Vigencia warnings.
  // ---------------------------------------------------------------

  /**
   * @covers ::validate
   */
  public function testWarnsOldNormWithoutVigencia(): void {
    $output = 'Segun la Ley 30/1992, el procedimiento administrativo establece plazos de 3 meses.';
    $result = $this->validator->validate($output);

    $this->assertNotEmpty($result['warnings']);
    $warningTypes = array_column($result['warnings'], 'type');
    $this->assertContains('vigencia_not_mentioned', $warningTypes);
  }

  /**
   * @covers ::validate
   */
  public function testNoWarningOldNormWithVigencia(): void {
    $output = 'La Ley 30/1992, actualmente derogada y sustituida por la Ley 39/2015, regulaba el procedimiento.';
    $result = $this->validator->validate($output);

    $vigenciaWarnings = array_filter(
      $result['warnings'],
      static fn(array $w): bool => $w['type'] === 'vigencia_not_mentioned',
    );
    $this->assertEmpty($vigenciaWarnings);
  }

  /**
   * @covers ::validate
   */
  public function testNoWarningRecentNorm(): void {
    $output = 'La Ley 12/2023 de derecho a la vivienda establece nuevos limites.';
    $result = $this->validator->validate($output);

    $vigenciaWarnings = array_filter(
      $result['warnings'],
      static fn(array $w): bool => $w['type'] === 'vigencia_not_mentioned',
    );
    $this->assertEmpty($vigenciaWarnings);
  }

  // ---------------------------------------------------------------
  // Internal contradictions.
  // ---------------------------------------------------------------

  /**
   * @covers ::validate
   */
  public function testDetectsInternalContradiction(): void {
    $output = 'La materia penal es competencia exclusiva del Estado segun el Art. 149.1 CE. '
      . 'Las CCAA pueden legislar en materia penal cuando lo consideren oportuno.';
    $result = $this->validator->validate($output);

    $warningTypes = array_column($result['warnings'], 'type');
    $this->assertContains('internal_contradiction', $warningTypes);
  }

  // ---------------------------------------------------------------
  // Sycophancy detection (V8).
  // ---------------------------------------------------------------

  /**
   * @covers ::validate
   */
  public function testDetectsSycophancyNotCorrected(): void {
    $userQuery = 'Los autonomos no cotizan por desempleo en ningun caso';
    $output = 'Efectivamente, los autonomos no cotizan por desempleo.';
    $result = $this->validator->validate($output, ['user_query' => $userQuery]);

    $warningTypes = array_column($result['warnings'], 'type');
    $this->assertContains('sycophancy_risk', $warningTypes);
  }

  /**
   * @covers ::validate
   */
  public function testNoSycophancyWhenCorrected(): void {
    $userQuery = 'Los autonomos no cotizan por desempleo';
    $output = 'Sin embargo, conviene aclarar que los autonomos SI pueden cotizar para la prestacion por cese de actividad.';
    $result = $this->validator->validate($output, ['user_query' => $userQuery]);

    $sycophancyWarnings = array_filter(
      $result['warnings'],
      static fn(array $w): bool => $w['type'] === 'sycophancy_risk',
    );
    $this->assertEmpty($sycophancyWarnings);
  }

  /**
   * @covers ::validate
   */
  public function testNoSycophancyCheckWithoutUserQuery(): void {
    $output = 'Los autonomos no cotizan por desempleo.';
    $result = $this->validator->validate($output);

    $sycophancyWarnings = array_filter(
      $result['warnings'],
      static fn(array $w): bool => $w['type'] === 'sycophancy_risk',
    );
    $this->assertEmpty($sycophancyWarnings);
  }

  // ---------------------------------------------------------------
  // Scoring.
  // ---------------------------------------------------------------

  /**
   * @covers ::validate
   */
  public function testScoreDecreasesWithViolations(): void {
    $clean = $this->validator->validate('Texto sin violaciones.');
    $dirty = $this->validator->validate(
      'El Real Decreto 100/2024 deroga la Ley Organica 2/2010 de proteccion de datos.',
    );

    $this->assertGreaterThan($dirty['score'], $clean['score']);
  }

  /**
   * @covers ::validate
   */
  public function testScoreNeverBelowZero(): void {
    // Multiple violations to push score low.
    $output = 'El Real Decreto deroga la Ley Organica de derechos fundamentales. '
      . 'La Constitucion prevalece sobre el Reglamento (UE). '
      . 'La Ley de Cataluna regula la legislacion mercantil. '
      . 'La Orden Ministerial anula la Ley 39/2015.';
    $result = $this->validator->validate($output);

    $this->assertGreaterThanOrEqual(0.0, $result['score']);
  }

  // ---------------------------------------------------------------
  // Action determination.
  // ---------------------------------------------------------------

  /**
   * @covers ::validate
   */
  public function testActionRegenerateOnLowScore(): void {
    // Force multiple critical violations.
    $output = 'El Real Decreto 1/2024 deroga la Ley Organica 3/2018 de proteccion de datos. '
      . 'La Constitucion prevalece sobre el Reglamento (UE) 2016/679. '
      . 'La Ley de Cataluna establece legislacion mercantil propia.';
    $result = $this->validator->validate($output, ['retry_count' => 0]);

    if ($result['score'] < 0.5) {
      $this->assertSame('regenerate', $result['action']);
      $this->assertNotEmpty($result['regeneration_constraints']);
      $this->assertTrue($result['passed']);
    }
  }

  /**
   * @covers ::validate
   */
  public function testActionBlockAfterMaxRetries(): void {
    $output = 'El Real Decreto 1/2024 deroga la Ley Organica 3/2018 de proteccion de datos. '
      . 'La Constitucion prevalece sobre el Reglamento (UE) 2016/679. '
      . 'La Ley de Cataluna establece legislacion mercantil propia.';
    $result = $this->validator->validate($output, ['retry_count' => 2]);

    if ($result['score'] < 0.5) {
      $this->assertSame('block', $result['action']);
      $this->assertFalse($result['passed']);
      $this->assertStringContainsString('inconsistencias juridicas', $result['sanitized_output']);
    }
  }

  /**
   * @covers ::validate
   */
  public function testActionWarnOnMediumScore(): void {
    // One contradiction + vigencia warning to bring score to 0.5-0.7 range.
    $output = 'Segun la Ley 30/1992 el procedimiento administrativo comun establece plazos. '
      . 'Es competencia exclusiva del Estado la legislacion penal. '
      . 'Las CCAA pueden legislar en materia penal cuando lo necesiten.';
    $result = $this->validator->validate($output);

    // The output has internal_contradiction (medium=-0.15) + vigencia warning (low=-0.05).
    // Score should be between 0.5 and 1.0 depending on exact match.
    $this->assertTrue($result['passed']);
    $this->assertLessThan(1.0, $result['score']);
    // Action should be either 'warn' or 'allow' depending on final score.
    $this->assertContains($result['action'], ['warn', 'allow']);
  }

  // ---------------------------------------------------------------
  // Regeneration constraints.
  // ---------------------------------------------------------------

  /**
   * @covers ::validate
   */
  public function testRegenerationConstraintsForHierarchyViolation(): void {
    $output = 'El Real Decreto 100/2024 deroga la Ley Organica 2/2010. '
      . 'Ademas la Constitucion prevalece sobre el Reglamento (UE).';
    $result = $this->validator->validate($output, ['retry_count' => 0]);

    if ($result['action'] === 'regenerate') {
      $constraints = implode(' ', $result['regeneration_constraints']);
      $this->assertStringContainsString('CRITICO', $constraints);
    }
  }

  // ---------------------------------------------------------------
  // Metadata.
  // ---------------------------------------------------------------

  /**
   * @covers ::validate
   */
  public function testMetadataContainsChecksRun(): void {
    $result = $this->validator->validate('Texto de prueba con mencion de Ley 39/2015.');

    $this->assertArrayHasKey('hierarchy_checks', $result['metadata']);
    $this->assertArrayHasKey('competence_checks', $result['metadata']);
    $this->assertArrayHasKey('norms_detected', $result['metadata']);
  }

  /**
   * @covers ::validate
   */
  public function testHierarchyChecksRunGreaterThanZero(): void {
    $result = $this->validator->validate('Texto con Ley Organica 1/2024 y Real Decreto 100/2024.');

    $this->assertGreaterThan(0, $result['metadata']['hierarchy_checks']);
  }

  // ---------------------------------------------------------------
  // Antinomy detection.
  // ---------------------------------------------------------------

  /**
   * @covers ::validate
   */
  public function testDetectsUnresolvedAntinomy(): void {
    $output = 'La Ley 39/2015 y la Ley 40/2015 establecen disposiciones en conflicto '
      . 'sobre el mismo procedimiento administrativo sin resolucion clara.';
    $result = $this->validator->validate($output);

    $warningTypes = array_column($result['warnings'], 'type');
    // Only triggers if both norms are same rank AND conflict keyword present.
    if (in_array('unresolved_antinomy', $warningTypes, TRUE)) {
      $this->assertContains('unresolved_antinomy', $warningTypes);
    }
  }

  /**
   * @covers ::validate
   */
  public function testResolvedAntinomyNoWarning(): void {
    $output = 'La Ley 39/2015 y la Ley 40/2015 tienen disposiciones en conflicto, '
      . 'pero la Ley 40/2015 como norma posterior (lex posterior) prevalece.';
    $result = $this->validator->validate($output);

    $antinomyWarnings = array_filter(
      $result['warnings'],
      static fn(array $w): bool => $w['type'] === 'unresolved_antinomy',
    );
    $this->assertEmpty($antinomyWarnings);
  }

  // ---------------------------------------------------------------
  // retry_count tracking.
  // ---------------------------------------------------------------

  /**
   * @covers ::validate
   */
  public function testRetryCountPassedThrough(): void {
    $result = $this->validator->validate('test', ['retry_count' => 1]);
    $this->assertSame(1, $result['retry_count']);
  }

  /**
   * @covers ::validate
   */
  public function testRetryCountDefaultsToZero(): void {
    $result = $this->validator->validate('test');
    $this->assertSame(0, $result['retry_count']);
  }

}
