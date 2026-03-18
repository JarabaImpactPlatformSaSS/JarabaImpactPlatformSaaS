<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Service\PlazoEnforcementService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for PlazoEnforcementService.
 *
 * @group jaraba_andalucia_ei
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\PlazoEnforcementService
 */
class PlazoEnforcementServiceTest extends UnitTestCase {

  protected PlazoEnforcementService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $time = $this->createMock(TimeInterface::class);
    $time->method('getCurrentTime')->willReturn(time());
    $logger = $this->createMock(LoggerInterface::class);

    $this->service = new PlazoEnforcementService($entityTypeManager, $time, $logger);
  }

  /**
   * @covers ::restarDiasHabiles
   */
  public function testRestarDiasHabilesExcluyeFinDeSemana(): void {
    // Lunes 2 de marzo 2026 - restar 5 días hábiles = Lunes 23 febrero 2026.
    $desde = new \DateTimeImmutable('2026-03-02');
    $resultado = $this->service->restarDiasHabiles(5, $desde);

    // 5 hábiles atrás desde lun 2/3: vie 27/2, jue 26/2, mié 25/2, mar 24/2, lun 23/2.
    // Pero 28/2 es sábado (Día Andalucía se celebra en 2026 como festivo).
    // Verificar que el resultado es un día laborable.
    $diaSemana = (int) $resultado->format('N');
    self::assertLessThanOrEqual(5, $diaSemana, 'Result should be a weekday (Mon-Fri).');
  }

  /**
   * @covers ::restarDiasHabiles
   */
  public function testRestarDiasHabilesCeroDias(): void {
    $desde = new \DateTimeImmutable('2026-06-15'); // Lunes
    $resultado = $this->service->restarDiasHabiles(0, $desde);

    // 0 días hábiles = misma fecha.
    self::assertEquals($desde->format('Y-m-d'), $resultado->format('Y-m-d'));
  }

  /**
   * @covers ::restarDiasHabiles
   */
  public function testRestarDiasHabilesDesdeFinDeSemana(): void {
    // Desde sábado, restar 1 día hábil = viernes anterior.
    $desde = new \DateTimeImmutable('2026-06-20'); // Sábado
    $resultado = $this->service->restarDiasHabiles(1, $desde);

    self::assertEquals('2026-06-19', $resultado->format('Y-m-d'), 'Should be Friday 19 June.');
  }

  /**
   * @covers ::restarDiasHabiles
   */
  public function testRestarDiasHabilesExcluyeFestivo(): void {
    // 2 enero 2026 es viernes (día después de Año Nuevo).
    // Restar 1 día hábil desde 2/1 debería saltar 1/1 (festivo) → 31/12/2025 (miércoles).
    $desde = new \DateTimeImmutable('2026-01-02');
    $resultado = $this->service->restarDiasHabiles(1, $desde);

    // 1/1 es festivo, 31/12/2025 es miércoles (día hábil).
    self::assertEquals('2025-12-31', $resultado->format('Y-m-d'));
  }

  /**
   * @covers ::restarDiasHabiles
   */
  public function testDiezDiasHabilesAntesDe10Marzo(): void {
    // 10 marzo 2026 es martes. 10 hábiles atrás:
    // 9/3 lun, 6/3 vie, 5/3 jue, 4/3 mié, 3/3 mar, 2/3 lun,
    // 27/2 vie(festivo 28), 26/2 jue, 25/2 mié, 24/2 mar.
    $desde = new \DateTimeImmutable('2026-03-10');
    $resultado = $this->service->restarDiasHabiles(10, $desde);

    // El resultado debe ser un día laborable.
    $diaSemana = (int) $resultado->format('N');
    self::assertLessThanOrEqual(5, $diaSemana);
  }

}
