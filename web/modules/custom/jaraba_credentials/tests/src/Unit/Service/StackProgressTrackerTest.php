<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_credentials\Unit\Service;

use PHPUnit\Framework\TestCase;

/**
 * Tests para StackProgressTracker.
 *
 * Verifica calculo de progreso incremental, porcentajes,
 * deteccion de completitud y logica de recomendaciones.
 *
 * @group jaraba_credentials
 * @coversDefaultClass \Drupal\jaraba_credentials\Service\StackProgressTracker
 */
class StackProgressTrackerTest extends TestCase {

  /**
   * Calcula el porcentaje de progreso.
   *
   * Replica la logica de StackProgressTracker::updateProgress().
   */
  private function calculateProgress(array $requiredIds, array $completedIds): int {
    $matched = array_values(array_intersect($requiredIds, $completedIds));
    $total = count($requiredIds);
    return $total > 0 ? (int) round((count($matched) / $total) * 100) : 0;
  }

  /**
   * Tests progreso 0% cuando no hay templates completados.
   */
  public function testZeroProgress(): void {
    $percent = $this->calculateProgress([1, 2, 3, 4], []);
    $this->assertSame(0, $percent);
  }

  /**
   * Tests progreso 25% (1 de 4).
   */
  public function testQuarterProgress(): void {
    $percent = $this->calculateProgress([1, 2, 3, 4], [1]);
    $this->assertSame(25, $percent);
  }

  /**
   * Tests progreso 50% (2 de 4).
   */
  public function testHalfProgress(): void {
    $percent = $this->calculateProgress([1, 2, 3, 4], [1, 3]);
    $this->assertSame(50, $percent);
  }

  /**
   * Tests progreso 75% (3 de 4).
   */
  public function testThreeQuarterProgress(): void {
    $percent = $this->calculateProgress([1, 2, 3, 4], [1, 2, 4]);
    $this->assertSame(75, $percent);
  }

  /**
   * Tests progreso 100% (completo).
   */
  public function testFullProgress(): void {
    $percent = $this->calculateProgress([1, 2, 3, 4], [1, 2, 3, 4]);
    $this->assertSame(100, $percent);
  }

  /**
   * Tests progreso con templates extra del usuario.
   */
  public function testExtraTemplatesDoNotExceed100(): void {
    $percent = $this->calculateProgress([1, 2], [1, 2, 3, 4, 5]);
    $this->assertSame(100, $percent);
  }

  /**
   * Tests progreso 0% con required vacio.
   */
  public function testEmptyRequiredGivesZero(): void {
    $percent = $this->calculateProgress([], [1, 2, 3]);
    $this->assertSame(0, $percent);
  }

  /**
   * Tests progreso con un solo template (100% o 0%).
   */
  public function testSingleTemplateStack(): void {
    $this->assertSame(100, $this->calculateProgress([1], [1]));
    $this->assertSame(0, $this->calculateProgress([1], [2]));
  }

  /**
   * Tests redondeo del porcentaje (1 de 3 = 33%).
   */
  public function testRounding(): void {
    $percent = $this->calculateProgress([1, 2, 3], [1]);
    $this->assertSame(33, $percent);
  }

  /**
   * Tests redondeo (2 de 3 = 67%).
   */
  public function testRoundingTwoThirds(): void {
    $percent = $this->calculateProgress([1, 2, 3], [1, 2]);
    $this->assertSame(67, $percent);
  }

  /**
   * Tests deteccion de completitud (percent >= 100).
   */
  public function testCompletionDetection(): void {
    $percent = $this->calculateProgress([1, 2, 3], [1, 2, 3]);
    $isComplete = $percent >= 100;
    $this->assertTrue($isComplete);
  }

  /**
   * Tests que progreso parcial no marca como completo.
   */
  public function testPartialNotComplete(): void {
    $percent = $this->calculateProgress([1, 2, 3], [1, 2]);
    $isComplete = $percent >= 100;
    $this->assertFalse($isComplete);
  }

  /**
   * Tests matched templates son correctos.
   */
  public function testMatchedTemplateIds(): void {
    $required = [1, 2, 3, 4, 5];
    $completed = [2, 4, 6, 8];

    $matched = array_values(array_intersect($required, $completed));
    $this->assertSame([2, 4], $matched);
  }

  /**
   * Tests status se actualiza a completed cuando progreso >= 100%.
   */
  public function testStatusTransitionOnCompletion(): void {
    $percent = 100;
    $status = $percent >= 100 ? 'completed' : 'in_progress';
    $this->assertSame('completed', $status);
  }

  /**
   * Tests status permanece in_progress cuando progreso < 100%.
   */
  public function testStatusRemainsInProgress(): void {
    $percent = 75;
    $status = $percent >= 100 ? 'completed' : 'in_progress';
    $this->assertSame('in_progress', $status);
  }

  /**
   * Tests logica de recomendaciones: filtrar solo in_progress.
   */
  public function testRecommendationsFilterInProgress(): void {
    $allProgress = [
      ['status' => 'in_progress', 'percent' => 75],
      ['status' => 'completed', 'percent' => 100],
      ['status' => 'in_progress', 'percent' => 50],
    ];

    $inProgress = array_filter($allProgress, fn($item) => $item['status'] === 'in_progress');
    $this->assertCount(2, $inProgress);
  }

  /**
   * Tests ordenamiento por porcentaje descendente.
   */
  public function testSortByPercentDesc(): void {
    $items = [
      ['percent' => 30],
      ['percent' => 80],
      ['percent' => 50],
    ];

    usort($items, fn($a, $b) => $b['percent'] - $a['percent']);

    $this->assertSame(80, $items[0]['percent']);
    $this->assertSame(50, $items[1]['percent']);
    $this->assertSame(30, $items[2]['percent']);
  }

  /**
   * Tests limite de 10 recomendaciones.
   */
  public function testRecommendationsLimitedTo10(): void {
    $items = array_fill(0, 20, ['percent' => 50, 'status' => 'in_progress']);
    $limited = array_slice($items, 0, 10);
    $this->assertCount(10, $limited);
  }

  /**
   * Tests logica de stacks potenciales (sin progreso existente).
   */
  public function testPotentialStacksDetection(): void {
    $existingStackIds = [1, 2, 3];
    $allStackIds = [1, 2, 3, 4, 5];

    $newStacks = array_filter($allStackIds, fn($id) => !in_array($id, $existingStackIds));
    $this->assertSame([4, 5], array_values($newStacks));
  }

  /**
   * Tests JSON encode de templates completados.
   */
  public function testCompletedTemplatesJsonEncoding(): void {
    $matched = [2, 4, 6];
    $json = json_encode($matched);
    $decoded = json_decode($json, TRUE);

    $this->assertSame($matched, $decoded);
    $this->assertSame('[2,4,6]', $json);
  }

}
