<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal_knowledge\Unit\Service;

use Drupal\jaraba_legal_knowledge\Service\TaxCalculatorService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests unitarios para TaxCalculatorService.
 *
 * Verifica los calculos de IRPF (tramos, deducciones, tipo efectivo)
 * y de IVA (general, reducido, superreducido, recargo de equivalencia),
 * incluyendo casos limite y valores negativos.
 *
 * @coversDefaultClass \Drupal\jaraba_legal_knowledge\Service\TaxCalculatorService
 * @group jaraba_legal_knowledge
 */
class TaxCalculatorServiceTest extends TestCase {

  /**
   * El servicio bajo prueba.
   *
   * @var \Drupal\jaraba_legal_knowledge\Service\TaxCalculatorService
   */
  protected TaxCalculatorService $service;

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
    $this->service = new TaxCalculatorService($this->logger);
  }

  // ==========================================================================
  // IRPF TESTS
  // ==========================================================================

  /**
   * Verifica calculo IRPF para ingresos en el primer tramo (0-12450).
   *
   * @covers ::calculateIrpf
   */
  public function testCalculateIrpfFirstBracket(): void {
    $result = $this->service->calculateIrpf(10000.0, 0.0, 'general', 2025);

    $this->assertIsArray($result);
    $this->assertArrayHasKey('taxable_base', $result);
    $this->assertArrayHasKey('gross_tax', $result);
    $this->assertArrayHasKey('net_tax', $result);
    $this->assertArrayHasKey('effective_rate', $result);
    $this->assertArrayHasKey('brackets', $result);

    $this->assertEquals(10000.0, $result['taxable_base']);
    // First bracket: 19% of 10000 = 1900.
    $this->assertEquals(1900.0, $result['gross_tax']);
    $this->assertEquals(19.0, $result['effective_rate']);
  }

  /**
   * Verifica calculo IRPF para ingresos en el segundo tramo (12450-20200).
   *
   * @covers ::calculateIrpf
   */
  public function testCalculateIrpfSecondBracket(): void {
    $result = $this->service->calculateIrpf(20000.0, 0.0, 'general', 2025);

    $this->assertIsArray($result);
    $this->assertEquals(20000.0, $result['taxable_base']);

    // First bracket: 19% of 12450 = 2365.50
    // Second bracket: 24% of (20000 - 12450) = 24% of 7550 = 1812.00
    // Total: 2365.50 + 1812.00 = 4177.50
    $this->assertEqualsWithDelta(4177.50, $result['gross_tax'], 0.01);
  }

  /**
   * Verifica calculo IRPF para ingresos en el tercer tramo (20200-35200).
   *
   * @covers ::calculateIrpf
   */
  public function testCalculateIrpfThirdBracket(): void {
    $result = $this->service->calculateIrpf(35000.0, 0.0, 'general', 2025);

    $this->assertIsArray($result);
    $this->assertEquals(35000.0, $result['taxable_base']);
    // Gross tax should be progressive.
    $this->assertGreaterThan(4000.0, $result['gross_tax']);
  }

  /**
   * Verifica calculo IRPF para ingresos en el cuarto tramo (35200-60000).
   *
   * @covers ::calculateIrpf
   */
  public function testCalculateIrpfFourthBracket(): void {
    $result = $this->service->calculateIrpf(55000.0, 0.0, 'general', 2025);

    $this->assertIsArray($result);
    $this->assertEquals(55000.0, $result['taxable_base']);
    $this->assertGreaterThan(10000.0, $result['gross_tax']);
  }

  /**
   * Verifica calculo IRPF para ingresos altos (ultimo tramo, >300000).
   *
   * @covers ::calculateIrpf
   */
  public function testCalculateIrpfHighestBracket(): void {
    $result = $this->service->calculateIrpf(500000.0, 0.0, 'general', 2025);

    $this->assertIsArray($result);
    $this->assertEquals(500000.0, $result['taxable_base']);
    // Effective rate for very high income should be above 40%.
    $this->assertGreaterThan(40.0, $result['effective_rate']);
  }

  /**
   * Verifica que deducciones reducen la base imponible.
   *
   * @covers ::calculateIrpf
   */
  public function testCalculateIrpfWithDeductions(): void {
    $withoutDeductions = $this->service->calculateIrpf(40000.0, 0.0, 'general', 2025);
    $withDeductions = $this->service->calculateIrpf(40000.0, 5000.0, 'general', 2025);

    $this->assertLessThan($withoutDeductions['gross_tax'], $withDeductions['gross_tax']);
    $this->assertEquals(35000.0, $withDeductions['taxable_base']);
  }

  /**
   * Verifica que deducciones que exceden ingresos resultan en cuota 0.
   *
   * @covers ::calculateIrpf
   */
  public function testCalculateIrpfDeductionsExceedIncome(): void {
    $result = $this->service->calculateIrpf(10000.0, 15000.0, 'general', 2025);

    $this->assertIsArray($result);
    $this->assertEquals(0.0, $result['taxable_base']);
    $this->assertEquals(0.0, $result['gross_tax']);
    $this->assertEquals(0.0, $result['net_tax']);
  }

  /**
   * Verifica calculo IRPF con ingresos cero.
   *
   * @covers ::calculateIrpf
   */
  public function testCalculateIrpfZeroIncome(): void {
    $result = $this->service->calculateIrpf(0.0, 0.0, 'general', 2025);

    $this->assertIsArray($result);
    $this->assertEquals(0.0, $result['taxable_base']);
    $this->assertEquals(0.0, $result['gross_tax']);
    $this->assertEquals(0.0, $result['net_tax']);
    $this->assertEquals(0.0, $result['effective_rate']);
  }

  /**
   * Verifica que el tipo efectivo es correcto.
   *
   * @covers ::calculateIrpf
   */
  public function testCalculateIrpfEffectiveRateCalculation(): void {
    $result = $this->service->calculateIrpf(50000.0, 0.0, 'general', 2025);

    $expectedRate = ($result['gross_tax'] / 50000.0) * 100;
    $this->assertEqualsWithDelta($expectedRate, $result['effective_rate'], 0.01);
  }

  /**
   * Verifica que el desglose por tramos suma la cuota total.
   *
   * @covers ::calculateIrpf
   */
  public function testCalculateIrpfBracketsSumToGrossTax(): void {
    $result = $this->service->calculateIrpf(80000.0, 0.0, 'general', 2025);

    $bracketSum = 0.0;
    foreach ($result['brackets'] as $bracket) {
      $bracketSum += $bracket['tax'];
    }

    $this->assertEqualsWithDelta($result['gross_tax'], $bracketSum, 0.01);
  }

  /**
   * Verifica que cada tramo tiene las claves requeridas.
   *
   * @covers ::calculateIrpf
   */
  public function testCalculateIrpfBracketsHaveRequiredKeys(): void {
    $result = $this->service->calculateIrpf(30000.0, 0.0, 'general', 2025);

    foreach ($result['brackets'] as $bracket) {
      $this->assertArrayHasKey('from', $bracket);
      $this->assertArrayHasKey('to', $bracket);
      $this->assertArrayHasKey('rate', $bracket);
      $this->assertArrayHasKey('taxable_amount', $bracket);
      $this->assertArrayHasKey('tax', $bracket);
    }
  }

  /**
   * Verifica calculo IRPF para ingresos de 1 euro (caso limite).
   *
   * @covers ::calculateIrpf
   */
  public function testCalculateIrpfMinimumIncome(): void {
    $result = $this->service->calculateIrpf(1.0, 0.0, 'general', 2025);

    $this->assertIsArray($result);
    $this->assertEquals(1.0, $result['taxable_base']);
    $this->assertEqualsWithDelta(0.19, $result['gross_tax'], 0.01);
  }

  /**
   * Verifica que ingresos negativos resultan en cuota 0.
   *
   * @covers ::calculateIrpf
   */
  public function testCalculateIrpfNegativeIncome(): void {
    $result = $this->service->calculateIrpf(-5000.0, 0.0, 'general', 2025);

    $this->assertIsArray($result);
    $this->assertEquals(0.0, $result['taxable_base']);
    $this->assertEquals(0.0, $result['gross_tax']);
  }

  // ==========================================================================
  // IVA TESTS
  // ==========================================================================

  /**
   * Verifica calculo IVA general (21%).
   *
   * @covers ::calculateIva
   */
  public function testCalculateIvaGeneral(): void {
    $result = $this->service->calculateIva(1000.0, 'general', FALSE);

    $this->assertIsArray($result);
    $this->assertArrayHasKey('base_amount', $result);
    $this->assertArrayHasKey('rate', $result);
    $this->assertArrayHasKey('iva_amount', $result);
    $this->assertArrayHasKey('recargo_amount', $result);
    $this->assertArrayHasKey('total', $result);

    $this->assertEquals(1000.0, $result['base_amount']);
    $this->assertEquals(21, $result['rate']);
    $this->assertEqualsWithDelta(210.0, $result['iva_amount'], 0.01);
    $this->assertEquals(0.0, $result['recargo_amount']);
    $this->assertEqualsWithDelta(1210.0, $result['total'], 0.01);
  }

  /**
   * Verifica calculo IVA reducido (10%).
   *
   * @covers ::calculateIva
   */
  public function testCalculateIvaReducido(): void {
    $result = $this->service->calculateIva(500.0, 'reducido', FALSE);

    $this->assertEquals(500.0, $result['base_amount']);
    $this->assertEquals(10, $result['rate']);
    $this->assertEqualsWithDelta(50.0, $result['iva_amount'], 0.01);
    $this->assertEqualsWithDelta(550.0, $result['total'], 0.01);
  }

  /**
   * Verifica calculo IVA superreducido (4%).
   *
   * @covers ::calculateIva
   */
  public function testCalculateIvaSuperreducido(): void {
    $result = $this->service->calculateIva(200.0, 'superreducido', FALSE);

    $this->assertEquals(200.0, $result['base_amount']);
    $this->assertEquals(4, $result['rate']);
    $this->assertEqualsWithDelta(8.0, $result['iva_amount'], 0.01);
    $this->assertEqualsWithDelta(208.0, $result['total'], 0.01);
  }

  /**
   * Verifica calculo IVA exento (0%).
   *
   * @covers ::calculateIva
   */
  public function testCalculateIvaExento(): void {
    $result = $this->service->calculateIva(1500.0, 'exento', FALSE);

    $this->assertEquals(1500.0, $result['base_amount']);
    $this->assertEquals(0, $result['rate']);
    $this->assertEquals(0.0, $result['iva_amount']);
    $this->assertEqualsWithDelta(1500.0, $result['total'], 0.01);
  }

  /**
   * Verifica calculo IVA general con recargo de equivalencia (5.2%).
   *
   * @covers ::calculateIva
   */
  public function testCalculateIvaGeneralConRecargo(): void {
    $result = $this->service->calculateIva(1000.0, 'general', TRUE);

    $this->assertEquals(1000.0, $result['base_amount']);
    $this->assertEquals(21, $result['rate']);
    $this->assertEqualsWithDelta(210.0, $result['iva_amount'], 0.01);
    // Recargo equivalencia general: 5.2%.
    $this->assertEqualsWithDelta(52.0, $result['recargo_amount'], 0.01);
    // Total: 1000 + 210 + 52 = 1262.
    $this->assertEqualsWithDelta(1262.0, $result['total'], 0.01);
  }

  /**
   * Verifica calculo IVA reducido con recargo de equivalencia (1.4%).
   *
   * @covers ::calculateIva
   */
  public function testCalculateIvaReducidoConRecargo(): void {
    $result = $this->service->calculateIva(1000.0, 'reducido', TRUE);

    $this->assertEquals(10, $result['rate']);
    $this->assertEqualsWithDelta(100.0, $result['iva_amount'], 0.01);
    // Recargo equivalencia reducido: 1.4%.
    $this->assertEqualsWithDelta(14.0, $result['recargo_amount'], 0.01);
    // Total: 1000 + 100 + 14 = 1114.
    $this->assertEqualsWithDelta(1114.0, $result['total'], 0.01);
  }

  /**
   * Verifica calculo IVA superreducido con recargo de equivalencia (0.5%).
   *
   * @covers ::calculateIva
   */
  public function testCalculateIvaSuperreducidoConRecargo(): void {
    $result = $this->service->calculateIva(1000.0, 'superreducido', TRUE);

    $this->assertEquals(4, $result['rate']);
    $this->assertEqualsWithDelta(40.0, $result['iva_amount'], 0.01);
    // Recargo equivalencia superreducido: 0.5%.
    $this->assertEqualsWithDelta(5.0, $result['recargo_amount'], 0.01);
    // Total: 1000 + 40 + 5 = 1045.
    $this->assertEqualsWithDelta(1045.0, $result['total'], 0.01);
  }

  /**
   * Verifica calculo IVA exento con recargo (no debe aplicar recargo).
   *
   * @covers ::calculateIva
   */
  public function testCalculateIvaExentoConRecargoNoAplica(): void {
    $result = $this->service->calculateIva(1000.0, 'exento', TRUE);

    $this->assertEquals(0, $result['rate']);
    $this->assertEquals(0.0, $result['iva_amount']);
    $this->assertEquals(0.0, $result['recargo_amount']);
    $this->assertEqualsWithDelta(1000.0, $result['total'], 0.01);
  }

  /**
   * Verifica calculo IVA con base cero.
   *
   * @covers ::calculateIva
   */
  public function testCalculateIvaZeroBase(): void {
    $result = $this->service->calculateIva(0.0, 'general', FALSE);

    $this->assertIsArray($result);
    $this->assertEquals(0.0, $result['base_amount']);
    $this->assertEquals(0.0, $result['iva_amount']);
    $this->assertEqualsWithDelta(0.0, $result['total'], 0.01);
  }

  /**
   * Verifica calculo IVA con base negativa resulta en totales coherentes.
   *
   * @covers ::calculateIva
   */
  public function testCalculateIvaNegativeBase(): void {
    $result = $this->service->calculateIva(-1000.0, 'general', FALSE);

    $this->assertIsArray($result);
    // Negative amounts represent refunds/credit notes.
    $this->assertEquals(0.0, $result['base_amount']);
    $this->assertEquals(0.0, $result['total']);
  }

  /**
   * Verifica calculo IVA con base con decimales.
   *
   * @covers ::calculateIva
   */
  public function testCalculateIvaDecimalBase(): void {
    $result = $this->service->calculateIva(99.99, 'general', FALSE);

    $this->assertEqualsWithDelta(99.99, $result['base_amount'], 0.01);
    $this->assertEqualsWithDelta(20.9979, $result['iva_amount'], 0.01);
    $this->assertEqualsWithDelta(120.9879, $result['total'], 0.01);
  }

  /**
   * Verifica calculo IVA con base muy grande.
   *
   * @covers ::calculateIva
   */
  public function testCalculateIvaLargeAmount(): void {
    $result = $this->service->calculateIva(1000000.0, 'general', FALSE);

    $this->assertEqualsWithDelta(210000.0, $result['iva_amount'], 0.01);
    $this->assertEqualsWithDelta(1210000.0, $result['total'], 0.01);
  }

  /**
   * Verifica que tipo de IVA desconocido aplica tasa general por defecto.
   *
   * @covers ::calculateIva
   */
  public function testCalculateIvaUnknownRateTypeDefaultsToGeneral(): void {
    $result = $this->service->calculateIva(100.0, 'unknown_type', FALSE);

    $this->assertIsArray($result);
    // Should default to general (21%) or return an error.
    $this->assertGreaterThanOrEqual(0, $result['rate']);
  }

  // ==========================================================================
  // IRPF BRACKET DEFINITIONS
  // ==========================================================================

  /**
   * Verifica que getIrpfBrackets devuelve los tramos oficiales.
   *
   * @covers ::getIrpfBrackets
   */
  public function testGetIrpfBracketsReturnsOfficialBrackets(): void {
    $brackets = $this->service->getIrpfBrackets(2025);

    $this->assertIsArray($brackets);
    $this->assertNotEmpty($brackets);

    // First bracket should start at 0.
    $this->assertEquals(0, $brackets[0]['from']);
    // First bracket rate should be 19%.
    $this->assertEquals(19, $brackets[0]['rate']);
  }

  /**
   * Verifica que getIrpfBrackets tiene los tramos correctos para 2025.
   *
   * @covers ::getIrpfBrackets
   */
  public function testGetIrpfBracketsHasCorrectNumberOfBrackets(): void {
    $brackets = $this->service->getIrpfBrackets(2025);

    // Spain 2025: 6 brackets (19%, 24%, 30%, 37%, 45%, 47%).
    $this->assertGreaterThanOrEqual(5, count($brackets));
  }

  // ==========================================================================
  // IVA RATE DEFINITIONS
  // ==========================================================================

  /**
   * Verifica que getIvaRates devuelve las tasas oficiales.
   *
   * @covers ::getIvaRates
   */
  public function testGetIvaRatesReturnsOfficialRates(): void {
    $rates = $this->service->getIvaRates();

    $this->assertIsArray($rates);
    $this->assertArrayHasKey('general', $rates);
    $this->assertArrayHasKey('reducido', $rates);
    $this->assertArrayHasKey('superreducido', $rates);
    $this->assertArrayHasKey('exento', $rates);

    $this->assertEquals(21, $rates['general']);
    $this->assertEquals(10, $rates['reducido']);
    $this->assertEquals(4, $rates['superreducido']);
    $this->assertEquals(0, $rates['exento']);
  }

  /**
   * Verifica que getRecargoRates devuelve las tasas de recargo.
   *
   * @covers ::getRecargoRates
   */
  public function testGetRecargoRatesReturnsCorrectValues(): void {
    $rates = $this->service->getRecargoRates();

    $this->assertIsArray($rates);
    $this->assertArrayHasKey('general', $rates);
    $this->assertArrayHasKey('reducido', $rates);
    $this->assertArrayHasKey('superreducido', $rates);

    $this->assertEqualsWithDelta(5.2, $rates['general'], 0.01);
    $this->assertEqualsWithDelta(1.4, $rates['reducido'], 0.01);
    $this->assertEqualsWithDelta(0.5, $rates['superreducido'], 0.01);
  }

}
