<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_funding\Unit\Service;

use Drupal\jaraba_funding\Service\FundingNormalizerService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests unitarios para FundingNormalizerService.
 *
 * Verifica el parsing de importes, fechas, regiones, tipos de beneficiario
 * y sectores procedentes de diversas fuentes de subvenciones.
 *
 * @coversDefaultClass \Drupal\jaraba_funding\Service\FundingNormalizerService
 * @group jaraba_funding
 */
class FundingNormalizerServiceTest extends TestCase {

  /**
   * El servicio bajo prueba.
   *
   * @var \Drupal\jaraba_funding\Service\FundingNormalizerService
   */
  protected FundingNormalizerService $service;

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
    $this->service = new FundingNormalizerService($this->logger);
  }

  // ==========================================================================
  // AMOUNT PARSING TESTS
  // ==========================================================================

  /**
   * Verifica parsing de importe estandar (numeros planos).
   *
   * @covers ::parseAmount
   */
  public function testParseAmountStandard(): void {
    $result = $this->service->parseAmount('50000');

    $this->assertIsFloat($result);
    $this->assertEqualsWithDelta(50000.0, $result, 0.01);
  }

  /**
   * Verifica parsing de importe con puntos de miles.
   *
   * @covers ::parseAmount
   */
  public function testParseAmountWithDots(): void {
    $result = $this->service->parseAmount('1.500.000,00');

    $this->assertIsFloat($result);
    $this->assertEqualsWithDelta(1500000.0, $result, 0.01);
  }

  /**
   * Verifica parsing de importe con simbolo de euro.
   *
   * @covers ::parseAmount
   */
  public function testParseAmountWithEuroSign(): void {
    $result = $this->service->parseAmount('250.000 EUR');

    $this->assertIsFloat($result);
    $this->assertEqualsWithDelta(250000.0, $result, 0.01);
  }

  /**
   * Verifica parsing de importe vacio.
   *
   * @covers ::parseAmount
   */
  public function testParseAmountEmpty(): void {
    $result = $this->service->parseAmount('');

    $this->assertIsFloat($result);
    $this->assertEquals(0.0, $result);
  }

  // ==========================================================================
  // DATE PARSING TESTS
  // ==========================================================================

  /**
   * Verifica parsing de fecha en formato espanol (dd/mm/yyyy).
   *
   * @covers ::parseDate
   */
  public function testParseDateSpanishFormat(): void {
    $result = $this->service->parseDate('15/06/2026');

    $this->assertIsString($result);
    $this->assertEquals('2026-06-15', $result);
  }

  /**
   * Verifica parsing de fecha en formato ISO (yyyy-mm-dd).
   *
   * @covers ::parseDate
   */
  public function testParseDateIsoFormat(): void {
    $result = $this->service->parseDate('2026-03-20');

    $this->assertIsString($result);
    $this->assertEquals('2026-03-20', $result);
  }

  /**
   * Verifica parsing de fecha invalida.
   *
   * @covers ::parseDate
   */
  public function testParseDateInvalid(): void {
    $result = $this->service->parseDate('not-a-date');

    $this->assertIsString($result);
    $this->assertEquals('', $result);
  }

  // ==========================================================================
  // REGION NORMALIZATION TESTS
  // ==========================================================================

  /**
   * Verifica normalizacion de region con variantes de nombre.
   *
   * @covers ::normalizeRegion
   */
  public function testNormalizeRegion(): void {
    // Standard name.
    $this->assertEquals('andalucia', $this->service->normalizeRegion('Andalucia'));
    // With accents and uppercase.
    $this->assertEquals('cataluna', $this->service->normalizeRegion('CATALUNA'));
    // National scope.
    $this->assertEquals('nacional', $this->service->normalizeRegion('Nacional'));
    // European scope.
    $this->assertEquals('europeo', $this->service->normalizeRegion('Europeo'));
    // Unknown region returns lowercase trimmed.
    $result = $this->service->normalizeRegion('  Desconocida  ');
    $this->assertIsString($result);
    $this->assertNotEmpty($result);
  }

  // ==========================================================================
  // BENEFICIARY TYPE EXTRACTION TESTS
  // ==========================================================================

  /**
   * Verifica extraccion de tipos de beneficiario desde texto.
   *
   * @covers ::extractBeneficiaryTypes
   */
  public function testExtractBeneficiaryTypes(): void {
    $text = 'Dirigido a PYMES y autonomos del sector tecnologico, asi como entidades sin animo de lucro.';

    $result = $this->service->extractBeneficiaryTypes($text);

    $this->assertIsArray($result);
    $this->assertNotEmpty($result);
    // Should detect PYMES.
    $this->assertTrue(
      in_array('pymes', $result) || in_array('pyme', $result),
      'Should detect PYMES in text'
    );
    // Should detect autonomos.
    $this->assertTrue(
      in_array('autonomos', $result) || in_array('autonomo', $result),
      'Should detect autonomos in text'
    );
  }

  // ==========================================================================
  // SECTOR EXTRACTION TESTS
  // ==========================================================================

  /**
   * Verifica extraccion de sectores desde texto descriptivo.
   *
   * @covers ::extractSectors
   */
  public function testExtractSectors(): void {
    $text = 'Subvenciones para empresas del sector tecnologia e industria que promuevan la innovacion digital.';

    $result = $this->service->extractSectors($text);

    $this->assertIsArray($result);
    $this->assertNotEmpty($result);
    // Should detect tecnologia.
    $this->assertTrue(
      in_array('tecnologia', $result),
      'Should detect tecnologia sector in text'
    );
  }

}
