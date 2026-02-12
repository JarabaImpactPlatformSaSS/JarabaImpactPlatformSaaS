<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_self_discovery\Unit\Service;

use PHPUnit\Framework\TestCase;

/**
 * Tests para la logica de calculo del LifeWheelService.
 *
 * Verifica calculos de promedio, areas bajas/altas y tendencia
 * sin depender del Entity API de Drupal.
 *
 * @group jaraba_self_discovery
 */
class LifeWheelServiceTest extends TestCase {

  /**
   * Las 8 areas de la Rueda de la Vida.
   */
  private const AREAS = [
    'career', 'finance', 'health', 'family',
    'social', 'growth', 'leisure', 'environment',
  ];

  /**
   * Calcula el promedio replicando la logica de LifeWheelAssessment::getAverageScore().
   */
  private function calculateAverage(array $scores): float {
    $total = 0;
    $count = 0;

    foreach (self::AREAS as $area) {
      $value = $scores[$area] ?? 0;
      if ($value) {
        $total += (int) $value;
        $count++;
      }
    }

    return $count > 0 ? round($total / $count, 1) : 0.0;
  }

  /**
   * Obtiene las areas con puntuacion mas baja.
   */
  private function getLowestAreas(array $scores, int $count = 2): array {
    asort($scores);
    return array_slice($scores, 0, $count, TRUE);
  }

  /**
   * Obtiene las areas con puntuacion mas alta.
   */
  private function getHighestAreas(array $scores, int $count = 2): array {
    arsort($scores);
    return array_slice($scores, 0, $count, TRUE);
  }

  /**
   * Calcula la tendencia entre dos evaluaciones.
   */
  private function calculateTrend(float $current, float $previous): array {
    $diff = round($current - $previous, 1);
    return [
      'trend' => $diff > 0 ? 'improving' : ($diff < 0 ? 'declining' : 'stable'),
      'diff' => $diff,
      'current' => $current,
      'previous' => $previous,
    ];
  }

  /**
   * Tests el calculo de promedio con scores variados.
   */
  public function testAverageCalculation(): void {
    $scores = [
      'career' => 8, 'finance' => 6, 'health' => 7, 'family' => 5,
      'social' => 9, 'growth' => 4, 'leisure' => 3, 'environment' => 8,
    ];

    $average = $this->calculateAverage($scores);

    // 50 / 8 = 6.25, round(6.25, 1) = 6.3
    $this->assertSame(6.3, $average);
  }

  /**
   * Tests las 2 areas mas bajas.
   */
  public function testLowestAreas(): void {
    $scores = [
      'career' => 8, 'finance' => 6, 'health' => 7, 'family' => 5,
      'social' => 9, 'growth' => 4, 'leisure' => 3, 'environment' => 8,
    ];

    $lowest = $this->getLowestAreas($scores, 2);

    $this->assertCount(2, $lowest);
    $keys = array_keys($lowest);
    $this->assertSame('leisure', $keys[0]);
    $this->assertSame('growth', $keys[1]);
  }

  /**
   * Tests las 2 areas mas altas.
   */
  public function testHighestAreas(): void {
    $scores = [
      'career' => 8, 'finance' => 6, 'health' => 7, 'family' => 5,
      'social' => 9, 'growth' => 4, 'leisure' => 3, 'environment' => 8,
    ];

    $highest = $this->getHighestAreas($scores, 2);

    $this->assertCount(2, $highest);
    $keys = array_keys($highest);
    $this->assertSame('social', $keys[0]);
  }

  /**
   * Tests tendencia positiva (mejora).
   */
  public function testTrendImproving(): void {
    $trend = $this->calculateTrend(7.5, 6.0);

    $this->assertSame('improving', $trend['trend']);
    $this->assertSame(1.5, $trend['diff']);
  }

  /**
   * Tests tendencia negativa (declive).
   */
  public function testTrendDeclining(): void {
    $trend = $this->calculateTrend(5.0, 7.0);

    $this->assertSame('declining', $trend['trend']);
    $this->assertSame(-2.0, $trend['diff']);
  }

  /**
   * Tests tendencia estable (sin cambio).
   */
  public function testTrendStable(): void {
    $trend = $this->calculateTrend(6.0, 6.0);

    $this->assertSame('stable', $trend['trend']);
    $this->assertSame(0.0, $trend['diff']);
  }

  /**
   * Tests promedio con todos los scores en maximo.
   */
  public function testAverageMaxScores(): void {
    $scores = array_fill_keys(self::AREAS, 10);

    $this->assertSame(10.0, $this->calculateAverage($scores));
  }

  /**
   * Tests promedio con scores a cero retorna 0.0.
   */
  public function testAverageZeroScores(): void {
    $scores = array_fill_keys(self::AREAS, 0);

    $this->assertSame(0.0, $this->calculateAverage($scores));
  }

}
