<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_diagnostic\Unit\Service;

use Drupal\jaraba_diagnostic\Service\EmployabilityScoringService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests del servicio EmployabilityScoringService.
 *
 * Verifica el calculo de score, perfiles y gaps para el
 * diagnostico express de empleabilidad.
 *
 * @coversDefaultClass \Drupal\jaraba_diagnostic\Service\EmployabilityScoringService
 * @group jaraba_diagnostic
 */
class EmployabilityDiagnosticInsertTest extends UnitTestCase {

  /**
   * Servicio bajo test.
   */
  protected EmployabilityScoringService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $logger = $this->createMock(LoggerInterface::class);
    $this->service = new EmployabilityScoringService($logger);
  }

  /**
   * Test 1: Score minimo (todas respuestas = 1) produce perfil 'invisible'.
   *
   * Con todas las respuestas en 1:
   * - linkedin: (1-1)*2.5 = 0 * 0.40 = 0
   * - cv_ats: (1-1)*2.5 = 0 * 0.35 = 0
   * - estrategia: (1-1)*2.5 = 0 * 0.25 = 0
   * Total = 0
   */
  public function testMinimumScoreProducesInvisibleProfile(): void {
    $result = $this->service->calculate(1, 1, 1);

    $this->assertEquals(0, $result['score']);
    $this->assertEquals('invisible', $result['profile_type']);
    $this->assertEquals('Invisible', $result['profile_label']);
    $this->assertNotEmpty($result['profile_description']);
    $this->assertNotEmpty($result['primary_gap']);
  }

  /**
   * Test 2: Score maximo (todas respuestas = 5) produce perfil 'magnetico'.
   *
   * Con todas las respuestas en 5:
   * - linkedin: (5-1)*2.5 = 10 * 0.40 = 4.0
   * - cv_ats: (5-1)*2.5 = 10 * 0.35 = 3.5
   * - estrategia: (5-1)*2.5 = 10 * 0.25 = 2.5
   * Total = 10.0
   */
  public function testMaximumScoreProducesMagneticProfile(): void {
    $result = $this->service->calculate(5, 5, 5);

    $this->assertEquals(10.0, $result['score']);
    $this->assertEquals('magnetico', $result['profile_type']);
    $this->assertEquals('Magnetico', $result['profile_label']);
  }

  /**
   * Test 3: Gap principal detecta la dimension mas baja.
   *
   * Con linkedin=1, cv=5, estrategia=5:
   * LinkedIn tiene score 0, las demas 10.
   * Gap principal debe ser 'linkedin'.
   */
  public function testPrimaryGapDetectsLowestDimension(): void {
    $result = $this->service->calculate(1, 5, 5);

    $this->assertEquals('linkedin', $result['primary_gap']);
    $this->assertNotEmpty($result['recommendations']);

    // Verificar que dimension_scores tiene las 3 dimensiones.
    $this->assertArrayHasKey('linkedin', $result['dimension_scores']);
    $this->assertArrayHasKey('cv_ats', $result['dimension_scores']);
    $this->assertArrayHasKey('estrategia', $result['dimension_scores']);

    // LinkedIn debe tener el score mas bajo.
    $this->assertLessThan(
      $result['dimension_scores']['cv_ats'],
      $result['dimension_scores']['linkedin']
    );
  }

}
