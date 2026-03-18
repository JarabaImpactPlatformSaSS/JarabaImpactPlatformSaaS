<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Service\InsercionValidatorService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for InsercionValidatorService.
 *
 * @group jaraba_andalucia_ei
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\InsercionValidatorService
 */
class InsercionValidatorServiceTest extends UnitTestCase {

  protected InsercionValidatorService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $logger = $this->createMock(LoggerInterface::class);

    $this->service = new InsercionValidatorService($entityTypeManager, $logger);
  }

  /**
   * @covers ::getDesgloseFiscalIncentivo
   */
  public function testDesgloseFiscalIncentivo(): void {
    $desglose = InsercionValidatorService::getDesgloseFiscalIncentivo();

    self::assertEquals(528.00, $desglose['base_imponible']);
    self::assertEquals(2.0, $desglose['irpf_porcentaje']);
    self::assertEquals(10.56, $desglose['irpf_importe']);
    self::assertEquals(517.44, $desglose['total_percibir']);

    // Verify IRPF calculation is consistent.
    $calculatedIrpf = round($desglose['base_imponible'] * $desglose['irpf_porcentaje'] / 100, 2);
    self::assertEquals($desglose['irpf_importe'], $calculatedIrpf);

    // Verify net = base - IRPF.
    $calculatedNeto = round($desglose['base_imponible'] - $desglose['irpf_importe'], 2);
    self::assertEquals($desglose['total_percibir'], $calculatedNeto);
  }

  /**
   * Tests that constants are properly defined.
   */
  public function testConstantsDefinidos(): void {
    self::assertEquals(528.00, InsercionValidatorService::INCENTIVO_BASE);
    self::assertEquals(2.0, InsercionValidatorService::INCENTIVO_IRPF_PCT);
    self::assertEquals(10.56, InsercionValidatorService::INCENTIVO_IRPF);
    self::assertEquals(517.44, InsercionValidatorService::INCENTIVO_NETO);
  }

}
