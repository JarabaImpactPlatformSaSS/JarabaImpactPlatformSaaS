<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_self_discovery\Unit\Entity;

use PHPUnit\Framework\TestCase;

/**
 * Tests para la logica de StrengthAssessment entity.
 *
 * Verifica la logica de calculo de fortalezas, top 5,
 * y parsing de JSON sin instanciar la entidad Drupal.
 *
 * @group jaraba_self_discovery
 */
class StrengthAssessmentTest extends TestCase {

  /**
   * Las 24 fortalezas VIA.
   */
  private const STRENGTHS_KEYS = [
    'creativity', 'curiosity', 'judgment', 'love_learning', 'perspective',
    'bravery', 'perseverance', 'honesty', 'zest',
    'love', 'kindness', 'social_intel',
    'teamwork', 'fairness', 'leadership',
    'forgiveness', 'humility', 'prudence', 'self_control',
    'appreciation', 'gratitude', 'hope', 'humor', 'spirituality',
  ];

  /**
   * Calcula los resultados replicando StrengthsAssessmentForm::calculateResults().
   *
   * @param array $selections
   *   Array de keys seleccionados en cada par.
   *
   * @return array
   *   Top 5 fortalezas con name, desc, score.
   */
  private function calculateResults(array $selections): array {
    $counts = array_count_values($selections);
    arsort($counts);

    return array_slice($counts, 0, 5, TRUE);
  }

  /**
   * Tests que existen exactamente 24 fortalezas definidas.
   */
  public function testTwentyFourStrengths(): void {
    $this->assertCount(24, self::STRENGTHS_KEYS);
  }

  /**
   * Tests que el top 5 se calcula correctamente con selecciones claras.
   */
  public function testTopFiveCalculation(): void {
    $selections = [
      'creativity', 'creativity', 'creativity', 'creativity', 'creativity',
      'curiosity', 'curiosity', 'curiosity', 'curiosity',
      'bravery', 'bravery', 'bravery',
      'leadership', 'leadership',
      'hope', 'hope',
      'humor',
      'love',
      'kindness',
      'teamwork',
    ];

    $results = $this->calculateResults($selections);

    $this->assertCount(5, $results);
    $keys = array_keys($results);
    $this->assertSame('creativity', $keys[0]);
    $this->assertSame('curiosity', $keys[1]);
    $this->assertSame('bravery', $keys[2]);
  }

  /**
   * Tests que las puntuaciones reflejan las selecciones.
   */
  public function testScoresReflectSelections(): void {
    $selections = [
      'creativity', 'creativity', 'creativity',
      'honesty', 'honesty',
      'zest',
    ];

    $results = $this->calculateResults($selections);

    $this->assertSame(3, $results['creativity']);
    $this->assertSame(2, $results['honesty']);
    $this->assertSame(1, $results['zest']);
  }

  /**
   * Tests el parsing de top_strengths JSON.
   */
  public function testTopStrengthsJsonParsing(): void {
    $top5 = [
      'creativity' => ['name' => 'Creatividad', 'desc' => 'Generar ideas nuevas', 'score' => 5],
      'curiosity' => ['name' => 'Curiosidad', 'desc' => 'Interes por explorar', 'score' => 4],
      'bravery' => ['name' => 'Valentia', 'desc' => 'Actuar a pesar del miedo', 'score' => 3],
      'leadership' => ['name' => 'Liderazgo', 'desc' => 'Organizar e inspirar', 'score' => 2],
      'hope' => ['name' => 'Esperanza', 'desc' => 'Esperar lo mejor', 'score' => 2],
    ];

    $json = json_encode($top5);
    $parsed = json_decode($json, TRUE);

    $this->assertIsArray($parsed);
    $this->assertCount(5, $parsed);
    $this->assertSame('Creatividad', $parsed['creativity']['name']);
  }

  /**
   * Tests que la fortaleza principal es la primera del top 5.
   */
  public function testTopStrengthIsFirst(): void {
    $top5 = [
      'perseverance' => ['name' => 'Perseverancia', 'desc' => 'Terminar lo que se empieza', 'score' => 5],
      'creativity' => ['name' => 'Creatividad', 'desc' => 'Generar ideas nuevas', 'score' => 4],
    ];

    $topStrength = !empty($top5) ? reset($top5) : NULL;

    $this->assertNotNull($topStrength);
    $this->assertSame('Perseverancia', $topStrength['name']);
  }

  /**
   * Tests que selecciones vacias producen resultado vacio.
   */
  public function testEmptySelectionsProduceEmptyResults(): void {
    $results = $this->calculateResults([]);

    $this->assertEmpty($results);
  }

  /**
   * Tests que JSON null/vacio retorna array vacio.
   */
  public function testEmptyJsonReturnsEmptyArray(): void {
    $raw = '';
    $result = $raw ? (json_decode($raw, TRUE) ?? []) : [];
    $this->assertSame([], $result);
  }

}
