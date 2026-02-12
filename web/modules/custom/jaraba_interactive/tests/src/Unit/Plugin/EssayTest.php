<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_interactive\Unit\Plugin;

use Drupal\jaraba_interactive\Plugin\InteractiveType\Essay;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitarios para el plugin Essay.
 *
 * Verifica esquema, calculo de puntuacion por rubrica con pesos,
 * validacion de conteo de palabras, verbos xAPI y renderizado.
 *
 * @coversDefaultClass \Drupal\jaraba_interactive\Plugin\InteractiveType\Essay
 * @group jaraba_interactive
 */
class EssayTest extends TestCase {

  /**
   * El plugin bajo prueba.
   *
   * @var \Drupal\jaraba_interactive\Plugin\InteractiveType\Essay&\PHPUnit\Framework\MockObject\MockObject
   */
  private Essay&MockObject $plugin;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->plugin = $this->getMockBuilder(Essay::class)
      ->disableOriginalConstructor()
      ->onlyMethods([])
      ->getMock();
  }

  // ==========================================================================
  // Helper para construir datos de ensayo.
  // ==========================================================================

  /**
   * Construye un criterio de rubrica.
   *
   * @param string $id
   *   ID del criterio.
   * @param int $maxPoints
   *   Puntos maximos del criterio.
   * @param float $weight
   *   Peso del criterio en la puntuacion final.
   * @param string $name
   *   Nombre descriptivo del criterio.
   *
   * @return array
   *   Definicion del criterio de rubrica.
   */
  private function buildCriterion(string $id, int $maxPoints = 10, float $weight = 1.0, string $name = ''): array {
    return [
      'id' => $id,
      'criterion' => $name ?: "Criterio $id",
      'description' => "Descripcion del criterio $id",
      'max_points' => $maxPoints,
      'weight' => $weight,
    ];
  }

  /**
   * Construye datos completos de ejercicio de ensayo.
   *
   * @param array $rubric
   *   Lista de criterios de rubrica.
   * @param array $settings
   *   Configuracion del ensayo (passing_score, min_words, max_words, etc).
   *
   * @return array
   *   Datos completos del ensayo.
   */
  private function buildEssayData(array $rubric = [], array $settings = []): array {
    return [
      'prompt' => 'Escribe un ensayo sobre sostenibilidad.',
      'instructions' => 'Incluye al menos tres argumentos.',
      'rubric' => $rubric,
      'settings' => array_merge([
        'passing_score' => 60,
        'min_words' => 100,
        'max_words' => 2000,
        'evaluation_mode' => 'ai',
      ], $settings),
    ];
  }

  /**
   * Genera texto con un numero aproximado de palabras.
   *
   * @param int $wordCount
   *   Numero de palabras a generar.
   *
   * @return string
   *   Texto generado con el numero de palabras solicitado.
   */
  private function generateText(int $wordCount): string {
    $words = [];
    for ($i = 0; $i < $wordCount; $i++) {
      $words[] = 'palabra';
    }
    return implode(' ', $words);
  }

  // ==========================================================================
  // SCHEMA TESTS
  // ==========================================================================

  #[\PHPUnit\Framework\Attributes\Test]
  public function testGetSchemaReturnsArrayWithRequiredKeys(): void {
    $schema = $this->plugin->getSchema();

    $this->assertIsArray($schema);
    $this->assertArrayHasKey('prompt', $schema);
    $this->assertArrayHasKey('rubric', $schema);
    $this->assertArrayHasKey('settings', $schema);

    // Verificar que prompt y rubric son requeridos.
    $this->assertTrue($schema['prompt']['required']);
    $this->assertTrue($schema['rubric']['required']);
  }

  // ==========================================================================
  // CALCULATE SCORE TESTS
  // ==========================================================================

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateScorePerfectRubricScores(): void {
    $rubric = [
      $this->buildCriterion('clarity', 10, 1.0, 'Claridad'),
      $this->buildCriterion('argument', 10, 1.0, 'Argumentacion'),
      $this->buildCriterion('grammar', 10, 1.0, 'Gramatica'),
    ];

    $data = $this->buildEssayData($rubric);

    // Puntuaciones perfectas en todos los criterios + texto valido.
    $responses = [
      'criterion_scores' => [
        'clarity' => 10,
        'argument' => 10,
        'grammar' => 10,
      ],
      'text' => $this->generateText(150),
    ];

    $result = $this->plugin->calculateScore($data, $responses);

    $this->assertSame(100.0, $result['score']);
    $this->assertSame(100, $result['max_score']);
    $this->assertTrue($result['passed']);
    $this->assertSame(30.0, $result['raw_score']);
    $this->assertSame(30.0, $result['raw_max']);
    $this->assertTrue($result['length_valid']);
    $this->assertSame(150, $result['word_count']);
    $this->assertSame('ai', $result['evaluation_mode']);

    // Verificar detalles de cada criterio.
    $this->assertSame(10, $result['details']['clarity']['points_earned']);
    $this->assertSame(10, $result['details']['clarity']['max_points']);
    $this->assertSame(1.0, $result['details']['clarity']['weight']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateScorePartialRubricScores(): void {
    $rubric = [
      $this->buildCriterion('clarity', 10, 1.0, 'Claridad'),
      $this->buildCriterion('argument', 10, 1.0, 'Argumentacion'),
      $this->buildCriterion('grammar', 10, 1.0, 'Gramatica'),
    ];

    $data = $this->buildEssayData($rubric);

    // Puntuaciones parciales: 7 + 5 + 8 = 20 de 30 = 66.67%.
    $responses = [
      'criterion_scores' => [
        'clarity' => 7,
        'argument' => 5,
        'grammar' => 8,
      ],
      'text' => $this->generateText(200),
    ];

    $result = $this->plugin->calculateScore($data, $responses);

    $this->assertSame(66.67, $result['score']);
    $this->assertTrue($result['passed']);
    $this->assertSame(20.0, $result['raw_score']);
    $this->assertSame(30.0, $result['raw_max']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateScoreWithWeightedCriteria(): void {
    $rubric = [
      $this->buildCriterion('clarity', 10, 2.0, 'Claridad'),
      $this->buildCriterion('grammar', 10, 1.0, 'Gramatica'),
    ];

    $data = $this->buildEssayData($rubric);

    // clarity: 8 pts * peso 2.0 = 16 earned, 10 * 2.0 = 20 max.
    // grammar: 6 pts * peso 1.0 = 6 earned, 10 * 1.0 = 10 max.
    // Total earned: 22.0, Total max: 30.0 => 73.33%.
    $responses = [
      'criterion_scores' => [
        'clarity' => 8,
        'grammar' => 6,
      ],
      'text' => $this->generateText(150),
    ];

    $result = $this->plugin->calculateScore($data, $responses);

    $this->assertSame(73.33, $result['score']);
    $this->assertTrue($result['passed']);
    $this->assertSame(22.0, $result['raw_score']);
    $this->assertSame(30.0, $result['raw_max']);

    // El criterio de claridad deberia pesar el doble.
    $this->assertSame(2.0, $result['details']['clarity']['weight']);
    $this->assertSame(1.0, $result['details']['grammar']['weight']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateScoreFailsWhenWordCountBelowMinimum(): void {
    $rubric = [
      $this->buildCriterion('clarity', 10, 1.0, 'Claridad'),
    ];

    $data = $this->buildEssayData($rubric, ['min_words' => 100, 'max_words' => 2000]);

    // Puntuacion perfecta PERO texto demasiado corto.
    $responses = [
      'criterion_scores' => [
        'clarity' => 10,
      ],
      'text' => $this->generateText(50),
    ];

    $result = $this->plugin->calculateScore($data, $responses);

    // Score alto pero passed = false por longitud invalida.
    $this->assertSame(100.0, $result['score']);
    $this->assertFalse($result['passed']);
    $this->assertFalse($result['length_valid']);
    $this->assertSame(50, $result['word_count']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateScoreFailsWhenWordCountAboveMaximum(): void {
    $rubric = [
      $this->buildCriterion('clarity', 10, 1.0, 'Claridad'),
    ];

    $data = $this->buildEssayData($rubric, ['min_words' => 100, 'max_words' => 500]);

    // Puntuacion perfecta PERO texto demasiado largo.
    $responses = [
      'criterion_scores' => [
        'clarity' => 10,
      ],
      'text' => $this->generateText(600),
    ];

    $result = $this->plugin->calculateScore($data, $responses);

    $this->assertSame(100.0, $result['score']);
    $this->assertFalse($result['passed']);
    $this->assertFalse($result['length_valid']);
    $this->assertSame(600, $result['word_count']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateScoreWithValidWordCountPasses(): void {
    $rubric = [
      $this->buildCriterion('clarity', 10, 1.0, 'Claridad'),
      $this->buildCriterion('argument', 10, 1.0, 'Argumentacion'),
    ];

    $data = $this->buildEssayData($rubric, [
      'min_words' => 100,
      'max_words' => 500,
      'passing_score' => 60,
    ]);

    // Score suficiente y longitud valida.
    $responses = [
      'criterion_scores' => [
        'clarity' => 8,
        'argument' => 7,
      ],
      'text' => $this->generateText(250),
    ];

    $result = $this->plugin->calculateScore($data, $responses);

    // (8 + 7) / (10 + 10) * 100 = 75%.
    $this->assertSame(75.0, $result['score']);
    $this->assertTrue($result['passed']);
    $this->assertTrue($result['length_valid']);
    $this->assertSame(250, $result['word_count']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateScoreWithEmptyRubricReturnsZero(): void {
    $data = $this->buildEssayData([]);

    $responses = [
      'criterion_scores' => [],
      'text' => $this->generateText(150),
    ];

    $result = $this->plugin->calculateScore($data, $responses);

    // Sin criterios: totalWeightedMax = 0 => percentage = 0.
    $this->assertSame(0.0, $result['score']);
    $this->assertSame(0.0, $result['raw_score']);
    $this->assertSame(0.0, $result['raw_max']);
    $this->assertEmpty($result['details']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateScoreClampsEarnedToMaxPointsPerCriterion(): void {
    $rubric = [
      $this->buildCriterion('clarity', 10, 1.0, 'Claridad'),
      $this->buildCriterion('argument', 5, 1.0, 'Argumentacion'),
    ];

    $data = $this->buildEssayData($rubric);

    // Enviar puntuaciones superiores al maximo: se deben limitar.
    $responses = [
      'criterion_scores' => [
        'clarity' => 15,
        'argument' => 20,
      ],
      'text' => $this->generateText(150),
    ];

    $result = $this->plugin->calculateScore($data, $responses);

    // clarity: min(15, 10) = 10; argument: min(20, 5) = 5.
    // Total: 15 / 15 * 100 = 100%.
    $this->assertSame(100.0, $result['score']);
    $this->assertSame(15.0, $result['raw_score']);
    $this->assertSame(15.0, $result['raw_max']);

    // Los detalles deben reflejar el valor clamped.
    $this->assertSame(10, $result['details']['clarity']['points_earned']);
    $this->assertSame(5, $result['details']['argument']['points_earned']);
  }

  // ==========================================================================
  // XAPI VERBS TESTS
  // ==========================================================================

  #[\PHPUnit\Framework\Attributes\Test]
  public function testGetXapiVerbsReturnsFiveVerbsIncludingScored(): void {
    $verbs = $this->plugin->getXapiVerbs();

    $this->assertIsArray($verbs);
    $this->assertCount(5, $verbs);
    $this->assertContains('scored', $verbs);
    $this->assertContains('attempted', $verbs);
    $this->assertContains('completed', $verbs);
    $this->assertContains('passed', $verbs);
    $this->assertContains('failed', $verbs);
  }

  // ==========================================================================
  // RENDER TESTS
  // ==========================================================================

  #[\PHPUnit\Framework\Attributes\Test]
  public function testRenderReturnsThemeInteractiveEssay(): void {
    $data = [
      'prompt' => 'Escribe sobre medio ambiente.',
      'instructions' => 'Tres parrafos minimo.',
      'rubric' => [
        ['id' => 'c1', 'criterion' => 'Claridad', 'max_points' => 10, 'weight' => 1.0],
      ],
      'settings' => ['show_rubric' => TRUE, 'evaluation_mode' => 'ai'],
    ];

    $result = $this->plugin->render($data);

    $this->assertIsArray($result);
    $this->assertSame('interactive_essay', $result['#theme']);
    $this->assertSame('Escribe sobre medio ambiente.', $result['#prompt']);
    $this->assertSame('Tres parrafos minimo.', $result['#instructions']);
    $this->assertSame($data['rubric'], $result['#rubric']);
    $this->assertArrayHasKey('#settings', $result);
  }

  // ==========================================================================
  // RESULT STRUCTURE TESTS
  // ==========================================================================

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateScoreResultIncludesWordCountLengthValidAndEvaluationMode(): void {
    $rubric = [
      $this->buildCriterion('clarity', 10, 1.0, 'Claridad'),
    ];

    $data = $this->buildEssayData($rubric, ['evaluation_mode' => 'hybrid']);

    $responses = [
      'criterion_scores' => ['clarity' => 7],
      'text' => $this->generateText(300),
    ];

    $result = $this->plugin->calculateScore($data, $responses);

    // Verificar que el resultado incluye campos especificos del ensayo.
    $this->assertArrayHasKey('word_count', $result);
    $this->assertArrayHasKey('length_valid', $result);
    $this->assertArrayHasKey('evaluation_mode', $result);

    $this->assertSame(300, $result['word_count']);
    $this->assertTrue($result['length_valid']);
    $this->assertSame('hybrid', $result['evaluation_mode']);
  }

}
