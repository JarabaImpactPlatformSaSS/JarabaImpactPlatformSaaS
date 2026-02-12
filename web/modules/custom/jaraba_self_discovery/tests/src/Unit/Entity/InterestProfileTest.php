<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_self_discovery\Unit\Entity;

use PHPUnit\Framework\TestCase;

/**
 * Tests para la logica de InterestProfile entity.
 *
 * Verifica la logica de campos RIASEC, generacion de codigo
 * y parsing de JSON sin instanciar la entidad Drupal.
 *
 * @group jaraba_self_discovery
 */
class InterestProfileTest extends TestCase {

  /**
   * Los 6 tipos RIASEC.
   */
  private const RIASEC_TYPES = [
    'R' => 'realistic',
    'I' => 'investigative',
    'A' => 'artistic',
    'S' => 'social',
    'E' => 'enterprising',
    'C' => 'conventional',
  ];

  /**
   * Genera un codigo RIASEC de 3 letras a partir de scores.
   *
   * Replica la logica de InterestsAssessmentForm::generateCode().
   *
   * @param array<string, int> $scores
   *   Puntuaciones por tipo (R, I, A, S, E, C).
   *
   * @return string
   *   Codigo de 3 letras.
   */
  private function generateCode(array $scores): string {
    arsort($scores);
    return implode('', array_slice(array_keys($scores), 0, 3));
  }

  /**
   * Normaliza puntuaciones de 1-5 a 0-100.
   *
   * Replica la logica de InterestsAssessmentForm::calculateScores().
   *
   * @param int $rawTotal
   *   Suma de 6 respuestas (cada una 1-5, max 30).
   *
   * @return int
   *   Puntuacion normalizada 0-100.
   */
  private function normalizeScore(int $rawTotal): int {
    return (int) round(($rawTotal / 30) * 100);
  }

  /**
   * Tests que el codigo RIASEC se genera correctamente con scores variados.
   */
  public function testGenerateCodeFromScores(): void {
    $scores = [
      'R' => 80,
      'I' => 90,
      'A' => 70,
      'S' => 60,
      'E' => 50,
      'C' => 40,
    ];

    $code = $this->generateCode($scores);

    $this->assertSame('IRA', $code);
    $this->assertSame(3, strlen($code));
  }

  /**
   * Tests que scores iguales producen un codigo valido de 3 letras.
   */
  public function testGenerateCodeWithEqualScores(): void {
    $scores = [
      'R' => 50,
      'I' => 50,
      'A' => 50,
      'S' => 50,
      'E' => 50,
      'C' => 50,
    ];

    $code = $this->generateCode($scores);

    $this->assertSame(3, strlen($code));
    // Con scores iguales, el orden depende de arsort (estable en PHP 8+).
  }

  /**
   * Tests la normalizacion de puntuaciones.
   */
  public function testNormalizeScores(): void {
    // 6 preguntas, todas en 5 (maximo) = 30 -> 100%
    $this->assertSame(100, $this->normalizeScore(30));

    // 6 preguntas, todas en 1 (minimo) = 6 -> 20%
    $this->assertSame(20, $this->normalizeScore(6));

    // 6 preguntas, todas en 3 (medio) = 18 -> 60%
    $this->assertSame(60, $this->normalizeScore(18));
  }

  /**
   * Tests que existen exactamente 6 tipos RIASEC.
   */
  public function testSixRiasecTypes(): void {
    $this->assertCount(6, self::RIASEC_TYPES);
  }

  /**
   * Tests que los campos de score corresponden a los tipos.
   */
  public function testScoreFieldMapping(): void {
    $expectedFields = [
      'score_realistic',
      'score_investigative',
      'score_artistic',
      'score_social',
      'score_enterprising',
      'score_conventional',
    ];

    $actualFields = array_map(fn($type) => "score_$type", array_values(self::RIASEC_TYPES));

    $this->assertSame($expectedFields, $actualFields);
  }

  /**
   * Tests el parsing de JSON de dominant_types.
   */
  public function testDominantTypesJsonParsing(): void {
    $json = '["R","I","A"]';
    $parsed = json_decode($json, TRUE);

    $this->assertIsArray($parsed);
    $this->assertCount(3, $parsed);
    $this->assertSame(['R', 'I', 'A'], $parsed);
  }

  /**
   * Tests el parsing de JSON de suggested_careers.
   */
  public function testSuggestedCareersJsonParsing(): void {
    $careers = ['Ingeniero/a Mecanico', 'Arquitecto/a', 'Disenador/a Industrial'];
    $json = json_encode($careers);
    $parsed = json_decode($json, TRUE);

    $this->assertSame($careers, $parsed);
  }

  /**
   * Tests que JSON null/vacio retorna array vacio.
   */
  public function testEmptyJsonReturnsEmptyArray(): void {
    $this->assertSame([], json_decode('', TRUE) ?? []);
    $this->assertSame([], json_decode('null', TRUE) ?? []);
  }

}
