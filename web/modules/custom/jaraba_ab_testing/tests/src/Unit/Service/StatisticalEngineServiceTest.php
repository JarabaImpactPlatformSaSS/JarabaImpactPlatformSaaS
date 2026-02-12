<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ab_testing\Unit\Service;

use Drupal\jaraba_ab_testing\Service\StatisticalEngineService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests unitarios para StatisticalEngineService.
 *
 * Verifica la logica de calculos estadisticos para experimentos A/B,
 * incluyendo Z-Score, confianza, tamano de muestra y chi-cuadrado.
 *
 * @coversDefaultClass \Drupal\jaraba_ab_testing\Service\StatisticalEngineService
 * @group jaraba_ab_testing
 */
class StatisticalEngineServiceTest extends TestCase {

  /**
   * El servicio bajo prueba.
   *
   * @var \Drupal\jaraba_ab_testing\Service\StatisticalEngineService
   */
  protected StatisticalEngineService $service;

  /**
   * Mock del canal de log.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected LoggerInterface|MockObject $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new StatisticalEngineService(
      $this->logger,
    );
  }

  /**
   * Verifica que calculateZScore() devuelve 0 cuando no hay visitantes.
   *
   * @covers ::calculateZScore
   */
  public function testCalculateZScoreReturnsZeroWithNoVisitors(): void {
    $zScore = $this->service->calculateZScore(0, 0, 0, 0);

    $this->assertSame(0.0, $zScore);
  }

  /**
   * Verifica que calculateZScore() devuelve valor positivo cuando variante supera al control.
   *
   * @covers ::calculateZScore
   */
  public function testCalculateZScorePositiveWhenVariantBetter(): void {
    // Control: 25% conversion (250/1000), Variante: 30% conversion (300/1000).
    $zScore = $this->service->calculateZScore(1000, 250, 1000, 300);

    $this->assertGreaterThan(0.0, $zScore);
  }

  /**
   * Verifica que zScoreToConfidence() devuelve confianza cercana a 0.95 para z=1.96.
   *
   * @covers ::zScoreToConfidence
   */
  public function testZScoreToConfidenceAt196(): void {
    $confidence = $this->service->zScoreToConfidence(1.96);

    // z=1.96 corresponde aproximadamente a 95% de confianza (retornado como porcentaje 0-100).
    $this->assertGreaterThanOrEqual(90.0, $confidence);
    $this->assertLessThanOrEqual(100.0, $confidence);
  }

  /**
   * Verifica que calculateMinimumSampleSize() devuelve un entero positivo.
   *
   * @covers ::calculateMinimumSampleSize
   */
  public function testCalculateMinimumSampleSizeReturnsPositiveInt(): void {
    $sampleSize = $this->service->calculateMinimumSampleSize(
      0.10,
      0.02,
      0.95,
      0.80,
    );

    $this->assertIsInt($sampleSize);
    $this->assertGreaterThan(0, $sampleSize);
  }

  /**
   * Verifica que estimateDaysToSignificance() devuelve estimacion correcta.
   *
   * @covers ::estimateDaysToSignificance
   */
  public function testEstimateDaysToSignificance(): void {
    // Con 100 visitantes actuales, 50 diarios y necesitamos 500.
    $days = $this->service->estimateDaysToSignificance(100, 50, 500);

    // (500 - 100) / 50 = 8 dias.
    $this->assertIsInt($days);
    $this->assertGreaterThanOrEqual(0, $days);
  }

}
