<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_funding\Unit\Service;

use Drupal\jaraba_funding\Service\FundingMatchingEngine;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests unitarios para FundingMatchingEngine.
 *
 * Verifica cada funcion de scoring del motor de matching: region,
 * beneficiario, sector, tamano y calculo global ponderado.
 *
 * @coversDefaultClass \Drupal\jaraba_funding\Service\FundingMatchingEngine
 * @group jaraba_funding
 */
class FundingMatchingEngineTest extends TestCase {

  /**
   * El servicio bajo prueba.
   *
   * @var \Drupal\jaraba_funding\Service\FundingMatchingEngine
   */
  protected FundingMatchingEngine $service;

  /**
   * Mock del logger.
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
    $this->service = new FundingMatchingEngine($this->logger);
  }

  // ==========================================================================
  // REGION SCORE TESTS
  // ==========================================================================

  /**
   * Verifica que convocatoria nacional obtiene score alto para cualquier region.
   *
   * @covers ::calculateRegionScore
   */
  public function testCalculateRegionScoreNational(): void {
    $callRegion = 'nacional';
    $profileRegion = 'andalucia';

    $score = $this->service->calculateRegionScore($callRegion, $profileRegion);

    $this->assertIsFloat($score);
    // National calls should score high for any region.
    $this->assertGreaterThanOrEqual(80.0, $score);
    $this->assertLessThanOrEqual(100.0, $score);
  }

  /**
   * Verifica que coincidencia exacta de region obtiene score maximo.
   *
   * @covers ::calculateRegionScore
   */
  public function testCalculateRegionScoreExactMatch(): void {
    $callRegion = 'andalucia';
    $profileRegion = 'andalucia';

    $score = $this->service->calculateRegionScore($callRegion, $profileRegion);

    $this->assertIsFloat($score);
    $this->assertEquals(100.0, $score);
  }

  /**
   * Verifica que regiones distintas obtienen score bajo.
   *
   * @covers ::calculateRegionScore
   */
  public function testCalculateRegionScoreNoMatch(): void {
    $callRegion = 'cataluna';
    $profileRegion = 'andalucia';

    $score = $this->service->calculateRegionScore($callRegion, $profileRegion);

    $this->assertIsFloat($score);
    // Different regions with no overlap should score low.
    $this->assertLessThanOrEqual(20.0, $score);
  }

  // ==========================================================================
  // BENEFICIARY SCORE TESTS
  // ==========================================================================

  /**
   * Verifica match directo de tipo de beneficiario.
   *
   * @covers ::calculateBeneficiaryScore
   */
  public function testCalculateBeneficiaryScoreDirectMatch(): void {
    $callBeneficiaries = ['pymes', 'autonomos'];
    $profileType = 'pymes';

    $score = $this->service->calculateBeneficiaryScore($callBeneficiaries, $profileType);

    $this->assertIsFloat($score);
    $this->assertEquals(100.0, $score);
  }

  /**
   * Verifica match por inclusion de tipo de beneficiario.
   *
   * @covers ::calculateBeneficiaryScore
   */
  public function testCalculateBeneficiaryScoreInclusion(): void {
    // "empresas" includes "pymes" as a sub-type.
    $callBeneficiaries = ['empresas'];
    $profileType = 'pymes';

    $score = $this->service->calculateBeneficiaryScore($callBeneficiaries, $profileType);

    $this->assertIsFloat($score);
    // Inclusion match should score at least moderately.
    $this->assertGreaterThanOrEqual(50.0, $score);
  }

  /**
   * Verifica que tipo de beneficiario sin match obtiene score bajo.
   *
   * @covers ::calculateBeneficiaryScore
   */
  public function testCalculateBeneficiaryScoreNoMatch(): void {
    $callBeneficiaries = ['administraciones_publicas'];
    $profileType = 'pymes';

    $score = $this->service->calculateBeneficiaryScore($callBeneficiaries, $profileType);

    $this->assertIsFloat($score);
    $this->assertLessThanOrEqual(10.0, $score);
  }

  // ==========================================================================
  // SECTOR SCORE TESTS
  // ==========================================================================

  /**
   * Verifica que convocatoria sin restriccion de sector obtiene score alto.
   *
   * @covers ::calculateSectorScore
   */
  public function testCalculateSectorScoreUnrestricted(): void {
    // Empty sector list means unrestricted / all sectors.
    $callSectors = [];
    $profileSectors = ['tecnologia', 'servicios'];

    $score = $this->service->calculateSectorScore($callSectors, $profileSectors);

    $this->assertIsFloat($score);
    // Unrestricted calls should match everyone.
    $this->assertGreaterThanOrEqual(80.0, $score);
  }

  /**
   * Verifica match parcial de sectores.
   *
   * @covers ::calculateSectorScore
   */
  public function testCalculateSectorScorePartialMatch(): void {
    $callSectors = ['tecnologia', 'industria', 'comercio'];
    $profileSectors = ['tecnologia', 'servicios'];

    $score = $this->service->calculateSectorScore($callSectors, $profileSectors);

    $this->assertIsFloat($score);
    // Partial overlap should score moderately.
    $this->assertGreaterThanOrEqual(30.0, $score);
    $this->assertLessThanOrEqual(100.0, $score);
  }

  // ==========================================================================
  // SIZE SCORE TESTS
  // ==========================================================================

  /**
   * Verifica score de tamano cuando la empresa esta dentro del rango.
   *
   * @covers ::calculateSizeScore
   */
  public function testCalculateSizeScoreWithinRange(): void {
    // Call accepts 1-250 employees.
    $callMinEmployees = 1;
    $callMaxEmployees = 250;
    $profileEmployees = 50;

    $score = $this->service->calculateSizeScore(
      $callMinEmployees,
      $callMaxEmployees,
      $profileEmployees
    );

    $this->assertIsFloat($score);
    $this->assertEquals(100.0, $score);
  }

  /**
   * Verifica score de tamano cuando la empresa esta fuera del rango.
   *
   * @covers ::calculateSizeScore
   */
  public function testCalculateSizeScoreOutOfRange(): void {
    // Call accepts 1-50 employees (micro/small).
    $callMinEmployees = 1;
    $callMaxEmployees = 50;
    $profileEmployees = 500;

    $score = $this->service->calculateSizeScore(
      $callMinEmployees,
      $callMaxEmployees,
      $profileEmployees
    );

    $this->assertIsFloat($score);
    $this->assertLessThanOrEqual(20.0, $score);
  }

  // ==========================================================================
  // OVERALL SCORE TESTS
  // ==========================================================================

  /**
   * Verifica calculo del score global ponderado.
   *
   * @covers ::calculateOverallScore
   */
  public function testOverallScoreCalculation(): void {
    $call = [
      'id' => 1,
      'region' => 'nacional',
      'beneficiary_types' => ['pymes', 'autonomos'],
      'sectors' => ['tecnologia'],
      'min_employees' => 1,
      'max_employees' => 250,
    ];

    $profile = [
      'region' => 'andalucia',
      'beneficiary_type' => 'pymes',
      'sectors' => ['tecnologia', 'servicios'],
      'employees' => 25,
    ];

    $result = $this->service->calculateOverallScore($call, $profile);

    $this->assertIsArray($result);
    $this->assertArrayHasKey('score', $result);
    $this->assertArrayHasKey('breakdown', $result);

    // Overall score should be between 0 and 100.
    $this->assertGreaterThanOrEqual(0.0, $result['score']);
    $this->assertLessThanOrEqual(100.0, $result['score']);

    // With good matches across all criteria, score should be high.
    $this->assertGreaterThanOrEqual(60.0, $result['score']);

    // Breakdown should have all criteria.
    $this->assertArrayHasKey('region', $result['breakdown']);
    $this->assertArrayHasKey('beneficiary', $result['breakdown']);
    $this->assertArrayHasKey('sector', $result['breakdown']);
    $this->assertArrayHasKey('size', $result['breakdown']);
  }

}
