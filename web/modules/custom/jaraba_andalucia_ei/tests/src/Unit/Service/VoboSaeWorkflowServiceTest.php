<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Entity\AccionFormativaEiInterface;
use Drupal\jaraba_andalucia_ei\Service\VoboSaeWorkflowService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para VoboSaeWorkflowService.
 *
 * Verifica la máquina de estados VoBo SAE: transiciones válidas/inválidas,
 * estados terminales, registro de respuestas, y alertas por timeout.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\VoboSaeWorkflowService
 * @group jaraba_andalucia_ei
 */
class VoboSaeWorkflowServiceTest extends UnitTestCase {

  /**
   * El servicio bajo test.
   */
  protected VoboSaeWorkflowService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $logger = $this->createMock(LoggerInterface::class);

    $this->service = new VoboSaeWorkflowService($entityTypeManager, $logger);
  }

  /**
   * @covers ::isTransitionValid
   * @dataProvider validTransitionsProvider
   */
  public function testValidTransitions(string $from, string $to): void {
    $this->assertTrue(
      $this->service->isTransitionValid($from, $to),
      "Transición $from → $to debería ser válida."
    );
  }

  /**
   * Proveedor de transiciones válidas.
   *
   * @return array<string, array{string, string}>
   */
  public static function validTransitionsProvider(): array {
    return [
      'borrador → pendiente_vobo' => ['borrador', 'pendiente_vobo'],
      'pendiente_vobo → vobo_enviado' => ['pendiente_vobo', 'vobo_enviado'],
      'pendiente_vobo → borrador (retroceso)' => ['pendiente_vobo', 'borrador'],
      'vobo_enviado → vobo_aprobado' => ['vobo_enviado', 'vobo_aprobado'],
      'vobo_enviado → vobo_rechazado' => ['vobo_enviado', 'vobo_rechazado'],
      'vobo_aprobado → en_ejecucion' => ['vobo_aprobado', 'en_ejecucion'],
      'vobo_rechazado → en_subsanacion' => ['vobo_rechazado', 'en_subsanacion'],
      'vobo_rechazado → borrador' => ['vobo_rechazado', 'borrador'],
      'en_subsanacion → vobo_enviado' => ['en_subsanacion', 'vobo_enviado'],
      'en_subsanacion → borrador' => ['en_subsanacion', 'borrador'],
      'en_ejecucion → finalizada' => ['en_ejecucion', 'finalizada'],
    ];
  }

  /**
   * @covers ::isTransitionValid
   * @dataProvider invalidTransitionsProvider
   */
  public function testInvalidTransitions(string $from, string $to): void {
    $this->assertFalse(
      $this->service->isTransitionValid($from, $to),
      "Transición $from → $to debería ser inválida."
    );
  }

  /**
   * Proveedor de transiciones inválidas.
   *
   * @return array<string, array{string, string}>
   */
  public static function invalidTransitionsProvider(): array {
    return [
      'borrador → vobo_aprobado (salta pasos)' => ['borrador', 'vobo_aprobado'],
      'borrador → finalizada (salta todo)' => ['borrador', 'finalizada'],
      'vobo_aprobado → borrador (no retrocede)' => ['vobo_aprobado', 'borrador'],
      'finalizada → borrador (estado terminal)' => ['finalizada', 'borrador'],
      'finalizada → en_ejecucion (terminal)' => ['finalizada', 'en_ejecucion'],
      'en_ejecucion → borrador (no retrocede)' => ['en_ejecucion', 'borrador'],
      'vobo_enviado → en_ejecucion (salta)' => ['vobo_enviado', 'en_ejecucion'],
      'estado_inexistente → borrador' => ['inexistente', 'borrador'],
    ];
  }

  /**
   * @covers ::isEstadoTerminal
   */
  public function testEstadoTerminal(): void {
    $this->assertTrue($this->service->isEstadoTerminal('finalizada'));
    $this->assertFalse($this->service->isEstadoTerminal('borrador'));
    $this->assertFalse($this->service->isEstadoTerminal('vobo_enviado'));
    $this->assertFalse($this->service->isEstadoTerminal('en_ejecucion'));
  }

  /**
   * @covers ::getTransicionesPosibles
   */
  public function testTransicionesPosibles(): void {
    $this->assertEquals(['pendiente_vobo'], $this->service->getTransicionesPosibles('borrador'));
    $this->assertEquals(['vobo_aprobado', 'vobo_rechazado'], $this->service->getTransicionesPosibles('vobo_enviado'));
    $this->assertEquals([], $this->service->getTransicionesPosibles('finalizada'));
    $this->assertEquals([], $this->service->getTransicionesPosibles('inexistente'));
  }

  /**
   * @covers ::transicionar
   */
  public function testTransicionarInvalida(): void {
    $accion = $this->createMock(AccionFormativaEiInterface::class);
    $accion->method('getEstado')->willReturn('borrador');

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessageMatches('/Transición inválida: borrador → finalizada/');

    $this->service->transicionar($accion, 'finalizada');
  }

}
