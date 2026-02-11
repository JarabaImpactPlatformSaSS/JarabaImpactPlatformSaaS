<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_self_discovery\Unit\Entity;

use PHPUnit\Framework\TestCase;

/**
 * Tests para la logica de calculo de la entidad LifeWheelAssessment.
 *
 * La entidad depende del Entity API completo de Drupal, por lo que no se puede
 * instanciar directamente en un test unitario. En su lugar, verificamos la
 * logica matematica que subyace a getAverageScore() y las 8 areas definidas.
 *
 * @group jaraba_self_discovery
 */
class LifeWheelAssessmentTest extends TestCase {

  /**
   * Las 8 areas de la Rueda de la Vida segun metodologia Osterwalder.
   *
   * @var string[]
   */
  private const LIFE_WHEEL_AREAS = [
    'career',
    'finance',
    'health',
    'family',
    'social',
    'growth',
    'leisure',
    'environment',
  ];

  /**
   * Calcula el promedio replicando la logica de LifeWheelAssessment::getAverageScore().
   *
   * @param array<string, int> $scores
   *   Puntuaciones por area.
   *
   * @return float
   *   Promedio redondeado a 1 decimal.
   */
  private function calculateAverage(array $scores): float {
    $total = 0;
    $count = 0;

    foreach (self::LIFE_WHEEL_AREAS as $area) {
      $value = $scores[$area] ?? 0;
      if ($value) {
        $total += (int) $value;
        $count++;
      }
    }

    return $count > 0 ? round($total / $count, 1) : 0.0;
  }

  /**
   * Tests que el promedio de 8 puntuaciones variadas es correcto.
   *
   * Datos: career=8, finance=6, health=7, family=5, social=9, growth=4,
   *        leisure=3, environment=8.
   * Suma = 50, promedio = 50/8 = 6.25 redondeado a 1 decimal = 6.3.
   *
   * Nota: La entidad usa round($total/$count, 1), asi que 6.25 se redondea a 6.3
   * segun el modo de redondeo PHP_ROUND_HALF_UP por defecto.
   */
  public function testAverageOfEightScores(): void {
    $scores = [
      'career' => 8,
      'finance' => 6,
      'health' => 7,
      'family' => 5,
      'social' => 9,
      'growth' => 4,
      'leisure' => 3,
      'environment' => 8,
    ];

    $average = $this->calculateAverage($scores);

    // 50 / 8 = 6.25, round(6.25, 1) = 6.3 en PHP.
    $this->assertSame(6.3, $average);
  }

  /**
   * Tests que todas las puntuaciones en 5 producen un promedio de 5.0.
   */
  public function testAverageWithDefaultScores(): void {
    $scores = [];
    foreach (self::LIFE_WHEEL_AREAS as $area) {
      $scores[$area] = 5;
    }

    $average = $this->calculateAverage($scores);

    $this->assertSame(5.0, $average);
  }

  /**
   * Tests que existen exactamente 8 areas definidas.
   */
  public function testEightAreasExist(): void {
    $this->assertCount(8, self::LIFE_WHEEL_AREAS);
  }

  /**
   * Tests que las areas contienen las claves esperadas.
   */
  public function testAreaKeysAreCorrect(): void {
    $expected = [
      'career',
      'finance',
      'health',
      'family',
      'social',
      'growth',
      'leisure',
      'environment',
    ];

    $this->assertSame($expected, self::LIFE_WHEEL_AREAS);
  }

  /**
   * Tests que puntuaciones vacias producen promedio 0.0.
   */
  public function testAverageWithZeroScoresReturnsZero(): void {
    $scores = [];
    foreach (self::LIFE_WHEEL_AREAS as $area) {
      $scores[$area] = 0;
    }

    $average = $this->calculateAverage($scores);

    $this->assertSame(0.0, $average);
  }

  /**
   * Tests el promedio con puntuaciones maximas (todos 10).
   */
  public function testAverageWithMaxScores(): void {
    $scores = [];
    foreach (self::LIFE_WHEEL_AREAS as $area) {
      $scores[$area] = 10;
    }

    $average = $this->calculateAverage($scores);

    $this->assertSame(10.0, $average);
  }

}
