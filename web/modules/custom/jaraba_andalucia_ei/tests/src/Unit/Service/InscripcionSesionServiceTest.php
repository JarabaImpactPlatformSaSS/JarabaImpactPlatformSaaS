<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_andalucia_ei\Entity\InscripcionSesionEiInterface;
use Drupal\jaraba_andalucia_ei\Entity\SesionProgramadaEiInterface;
use Drupal\jaraba_andalucia_ei\Service\InscripcionSesionService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para InscripcionSesionService.
 *
 * Verifica inscripcion con deteccion de duplicados, conteo de inscripciones
 * activas, y cancelacion de inscripciones.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\InscripcionSesionService
 * @group jaraba_andalucia_ei
 */
class InscripcionSesionServiceTest extends UnitTestCase {

  /**
   * El servicio bajo test.
   */
  protected InscripcionSesionService $service;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock storage para inscripcion_sesion_ei.
   */
  protected EntityStorageInterface $inscripcionStorage;

  /**
   * Mock storage para sesion_programada_ei.
   */
  protected EntityStorageInterface $sesionStorage;

  /**
   * Mock logger.
   */
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->inscripcionStorage = $this->createMock(EntityStorageInterface::class);
    $this->sesionStorage = $this->createMock(EntityStorageInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $entityType) {
        return match ($entityType) {
          'inscripcion_sesion_ei' => $this->inscripcionStorage,
          'sesion_programada_ei' => $this->sesionStorage,
          default => $this->createMock(EntityStorageInterface::class),
        };
      });

    $this->service = new InscripcionSesionService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * Helper para crear un mock de QueryInterface con chaining fluido.
   *
   * @param mixed $executeResult
   *   Resultado que devolvera execute().
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   Mock del query.
   */
  private function createFluentQuery(mixed $executeResult): QueryInterface {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    $query->method('execute')->willReturn($executeResult);

    return $query;
  }

  /**
   * @covers ::inscribir
   */
  public function testInscribirDuplicateReturnsNull(): void {
    // Simular que ya existe una inscripcion activa (no cancelada).
    $query = $this->createFluentQuery([42]);
    $this->inscripcionStorage->method('getQuery')->willReturn($query);

    // Logger debe registrar el duplicado.
    $this->logger->expects($this->once())
      ->method('notice')
      ->with(
        $this->stringContains('Inscripción duplicada rechazada'),
        $this->anything()
      );

    $result = $this->service->inscribir(10, 20, 1, 100);

    $this->assertNull($result, 'inscribir() debe devolver NULL cuando ya existe inscripcion activa.');
  }

  /**
   * @covers ::contarInscripcionesActivas
   */
  public function testContarInscripcionesActivasReturnsZeroWhenEmpty(): void {
    // count()->execute() devuelve 0 (un entero, no un array).
    $query = $this->createFluentQuery(0);
    $this->inscripcionStorage->method('getQuery')->willReturn($query);

    $result = $this->service->contarInscripcionesActivas(999);

    $this->assertSame(0, $result, 'Debe devolver 0 cuando no hay inscripciones activas.');
  }

  /**
   * @covers ::contarInscripcionesActivas
   */
  public function testContarInscripcionesActivasReturnsCount(): void {
    $query = $this->createFluentQuery(5);
    $this->inscripcionStorage->method('getQuery')->willReturn($query);

    $result = $this->service->contarInscripcionesActivas(10);

    $this->assertSame(5, $result, 'Debe devolver el conteo correcto de inscripciones activas.');
  }

  /**
   * @covers ::cancelar
   */
  public function testCancelarSetsEstadoAndSaves(): void {
    $inscripcion = $this->createMock(InscripcionSesionEiInterface::class);
    $inscripcion->method('id')->willReturn(42);

    // Verificar que set() se llama con 'estado' => 'cancelado'
    // y con 'motivo_cancelacion'.
    $inscripcion->expects($this->exactly(2))
      ->method('set')
      ->willReturnCallback(function (string $field, mixed $value) use ($inscripcion) {
        match (TRUE) {
          $field === 'estado' => $this->assertSame('cancelado', $value),
          $field === 'motivo_cancelacion' => $this->assertSame('Motivo de prueba', $value),
          default => $this->fail("Campo inesperado: $field"),
        };
        return $inscripcion;
      });

    $inscripcion->expects($this->once())->method('save');

    $result = $this->service->cancelar($inscripcion, 'Motivo de prueba');

    $this->assertSame($inscripcion, $result, 'cancelar() debe devolver la inscripcion actualizada.');
  }

  /**
   * @covers ::cancelar
   */
  public function testCancelarSinMotivoNoSetMotivoCancelacion(): void {
    $inscripcion = $this->createMock(InscripcionSesionEiInterface::class);
    $inscripcion->method('id')->willReturn(7);

    // Solo debe llamar set una vez (estado), NO motivo_cancelacion.
    $inscripcion->expects($this->once())
      ->method('set')
      ->with('estado', 'cancelado')
      ->willReturn($inscripcion);

    $inscripcion->expects($this->once())->method('save');

    $result = $this->service->cancelar($inscripcion, '');

    $this->assertSame($inscripcion, $result);
  }

  /**
   * @covers ::existeInscripcion
   */
  public function testExisteInscripcionReturnsTrueWhenFound(): void {
    $query = $this->createFluentQuery([42]);
    $this->inscripcionStorage->method('getQuery')->willReturn($query);

    $this->assertTrue($this->service->existeInscripcion(10, 20));
  }

  /**
   * @covers ::existeInscripcion
   */
  public function testExisteInscripcionReturnsFalseWhenEmpty(): void {
    $query = $this->createFluentQuery([]);
    $this->inscripcionStorage->method('getQuery')->willReturn($query);

    $this->assertFalse($this->service->existeInscripcion(10, 20));
  }

  /**
   * @covers ::inscribir
   */
  public function testInscribirReturnsNullWhenSesionNotFound(): void {
    // No existe inscripcion previa.
    $queryNoExiste = $this->createFluentQuery([]);
    $this->inscripcionStorage->method('getQuery')->willReturn($queryNoExiste);

    // Sesion no existe.
    $this->sesionStorage->method('load')
      ->with(99)
      ->willReturn(NULL);

    $result = $this->service->inscribir(99, 20, 1, 100);

    $this->assertNull($result, 'Debe devolver NULL si la sesion no existe.');
  }

  /**
   * @covers ::inscribir
   */
  public function testInscribirReturnsNullWhenNoPlazas(): void {
    // No existe inscripcion previa.
    $queryNoExiste = $this->createFluentQuery([]);
    $this->inscripcionStorage->method('getQuery')->willReturn($queryNoExiste);

    // Sesion existe pero sin plazas.
    $sesion = $this->createMock(SesionProgramadaEiInterface::class);
    $sesion->method('hayPlazasDisponibles')->willReturn(FALSE);

    $this->sesionStorage->method('load')
      ->with(10)
      ->willReturn($sesion);

    $this->logger->expects($this->once())
      ->method('notice')
      ->with(
        $this->stringContains('falta de plazas'),
        $this->anything()
      );

    $result = $this->service->inscribir(10, 20, 1, 100);

    $this->assertNull($result, 'Debe devolver NULL cuando no hay plazas disponibles.');
  }

  /**
   * @covers ::getInscripcionesPorParticipante
   */
  public function testGetInscripcionesPorParticipanteEmpty(): void {
    $query = $this->createFluentQuery([]);
    $this->inscripcionStorage->method('getQuery')->willReturn($query);

    $result = $this->service->getInscripcionesPorParticipante(999);

    $this->assertSame([], $result, 'Debe devolver array vacio cuando no hay inscripciones.');
  }

}
