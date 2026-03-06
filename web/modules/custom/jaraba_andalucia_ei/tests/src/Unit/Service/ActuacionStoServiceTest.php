<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_andalucia_ei\Service\ActuacionStoService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para ActuacionStoService.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\ActuacionStoService
 * @group jaraba_andalucia_ei
 */
class ActuacionStoServiceTest extends UnitTestCase {

  /**
   * El servicio bajo test.
   */
  protected ActuacionStoService $service;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock storage para participantes.
   */
  protected EntityStorageInterface $participanteStorage;

  /**
   * Mock storage para actuaciones.
   */
  protected EntityStorageInterface $actuacionStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->participanteStorage = $this->createMock(EntityStorageInterface::class);
    $this->actuacionStorage = $this->createMock(EntityStorageInterface::class);
    $logger = $this->createMock(LoggerInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $type) {
        if ($type === 'programa_participante_ei') {
          return $this->participanteStorage;
        }
        if ($type === 'actuacion_sto') {
          return $this->actuacionStorage;
        }
        return $this->createMock(EntityStorageInterface::class);
      });

    $this->service = new ActuacionStoService(
      $this->entityTypeManager,
      $logger,
    );
  }

  /**
   * @covers ::calcularDuracionMinutos
   * @dataProvider duracionMinutosProvider
   */
  #[\PHPUnit\Framework\Attributes\Test]
  #[\PHPUnit\Framework\Attributes\DataProvider('duracionMinutosProvider')]
  public function calcularDuracionMinutos(string $inicio, string $fin, int $expected): void {
    $this->assertEquals($expected, $this->service->calcularDuracionMinutos($inicio, $fin));
  }

  /**
   * Data provider para calcularDuracionMinutos.
   */
  public static function duracionMinutosProvider(): array {
    return [
      'una hora exacta' => ['09:00', '10:00', 60],
      'media hora' => ['10:00', '10:30', 30],
      'dos horas y media' => ['08:30', '11:00', 150],
      'mismo horario devuelve cero' => ['10:00', '10:00', 0],
      'hora fin antes que inicio devuelve cero' => ['14:00', '10:00', 0],
      'sesion de 15 minutos' => ['09:00', '09:15', 15],
      'jornada completa' => ['08:00', '16:00', 480],
      'hora con minutos' => ['09:45', '11:15', 90],
    ];
  }

  /**
   * @covers ::calcularDuracionMinutos
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function calcularDuracionMinutosFormatoInvalidoDevuelveCero(): void {
    $this->assertEquals(0, $this->service->calcularDuracionMinutos('invalido', '10:00'));
    $this->assertEquals(0, $this->service->calcularDuracionMinutos('09:00', 'invalido'));
    $this->assertEquals(0, $this->service->calcularDuracionMinutos('', ''));
  }

  /**
   * @covers ::incrementarHorasParticipante
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function incrementarHorasOrientacionIndividual(): void {
    $participante = $this->createParticipanteMock([
      'horas_orientacion_ind' => 5.0,
    ]);
    $this->participanteStorage->method('load')->with(1)->willReturn($participante);

    $this->service->incrementarHorasParticipante(1, 'orientacion_individual', 2.5);

    // El participante deberia tener 7.5 horas ahora.
    $this->assertEquals(7.5, $participante->getSetValue('horas_orientacion_ind'));
  }

  /**
   * @covers ::incrementarHorasParticipante
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function incrementarHorasFormacion(): void {
    $participante = $this->createParticipanteMock([
      'horas_formacion' => 20.0,
    ]);
    $this->participanteStorage->method('load')->with(1)->willReturn($participante);

    $this->service->incrementarHorasParticipante(1, 'formacion', 10.0);

    $this->assertEquals(30.0, $participante->getSetValue('horas_formacion'));
  }

  /**
   * @covers ::incrementarHorasParticipante
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function incrementarHorasTutoria(): void {
    $participante = $this->createParticipanteMock([
      'horas_mentoria_humana' => 1.0,
    ]);
    $this->participanteStorage->method('load')->with(1)->willReturn($participante);

    $this->service->incrementarHorasParticipante(1, 'tutoria', 0.5);

    $this->assertEquals(1.5, $participante->getSetValue('horas_mentoria_humana'));
  }

  /**
   * @covers ::incrementarHorasParticipante
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function incrementarHorasProspeccionNoIncrementa(): void {
    $participante = $this->createParticipanteMock([]);
    $this->participanteStorage->method('load')->with(1)->willReturn($participante);

    $this->service->incrementarHorasParticipante(1, 'prospeccion', 5.0);

    // prospeccion no mapea a ningun campo, no debe llamar set/save.
    $this->assertFalse($participante->wasSaved());
  }

  /**
   * @covers ::incrementarHorasParticipante
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function incrementarHorasIntermediacionNoIncrementa(): void {
    $participante = $this->createParticipanteMock([]);
    $this->participanteStorage->method('load')->with(1)->willReturn($participante);

    $this->service->incrementarHorasParticipante(1, 'intermediacion', 3.0);

    $this->assertFalse($participante->wasSaved());
  }

  /**
   * @covers ::incrementarHorasParticipante
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function incrementarHorasCeroNoIncrementa(): void {
    $participante = $this->createParticipanteMock([
      'horas_orientacion_ind' => 5.0,
    ]);
    $this->participanteStorage->method('load')->with(1)->willReturn($participante);

    $this->service->incrementarHorasParticipante(1, 'orientacion_individual', 0.0);

    $this->assertFalse($participante->wasSaved());
  }

  /**
   * @covers ::incrementarHorasParticipante
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function incrementarHorasNegativasNoIncrementa(): void {
    $participante = $this->createParticipanteMock([
      'horas_orientacion_ind' => 5.0,
    ]);
    $this->participanteStorage->method('load')->with(1)->willReturn($participante);

    $this->service->incrementarHorasParticipante(1, 'orientacion_individual', -2.0);

    $this->assertFalse($participante->wasSaved());
  }

  /**
   * @covers ::incrementarHorasParticipante
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function incrementarHorasParticipanteNoExiste(): void {
    $this->participanteStorage->method('load')->with(999)->willReturn(NULL);

    // No debe lanzar excepcion.
    $this->service->incrementarHorasParticipante(999, 'orientacion_individual', 2.0);
    $this->assertTrue(TRUE);
  }

  /**
   * @covers ::getResumenHoras
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getResumenHorasConActuaciones(): void {
    $actuaciones = [
      $this->createActuacionMock('orientacion_individual', 120),
      $this->createActuacionMock('orientacion_grupal', 60),
      $this->createActuacionMock('formacion', 180),
      $this->createActuacionMock('tutoria', 45),
      $this->createActuacionMock('prospeccion', 90),
    ];

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([1, 2, 3, 4, 5]);

    $this->actuacionStorage->method('getQuery')->willReturn($query);
    $this->actuacionStorage->method('loadMultiple')->willReturn($actuaciones);

    $resumen = $this->service->getResumenHoras(1);

    $this->assertEquals(2.0, $resumen['orientacion_individual']);
    $this->assertEquals(1.0, $resumen['orientacion_grupal']);
    $this->assertEquals(3.0, $resumen['formacion']);
    $this->assertEquals(0.75, $resumen['tutoria']);
    $this->assertEquals(1.5, $resumen['prospeccion']);
    $this->assertEquals(0.0, $resumen['intermediacion']);
  }

  /**
   * @covers ::getResumenHoras
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getResumenHorasSinActuacionesDevuelveCeros(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->actuacionStorage->method('getQuery')->willReturn($query);
    $this->actuacionStorage->method('loadMultiple')->willReturn([]);

    $resumen = $this->service->getResumenHoras(1);

    foreach ($resumen as $tipo => $horas) {
      $this->assertEquals(0.0, $horas, "Tipo $tipo deberia ser 0.0");
    }

    $this->assertCount(6, $resumen);
  }

  /**
   * Crea mock de participante con seguimiento de set/save.
   *
   * MOCK-DYNPROP-001: Clase anonima con typed properties.
   */
  protected function createParticipanteMock(array $fieldValues): object {
    return new class($fieldValues) {
      /** @var array<string, mixed> */
      private array $setValues = [];
      private bool $saved = FALSE;

      public function __construct(private readonly array $fieldValues) {}

      public function get(string $fieldName): object {
        $value = $this->fieldValues[$fieldName] ?? NULL;
        return new class($value) {
          public function __construct(public readonly mixed $value) {}
        };
      }

      public function set(string $fieldName, mixed $value): static {
        $this->setValues[$fieldName] = $value;
        return $this;
      }

      public function save(): int {
        $this->saved = TRUE;
        return 1;
      }

      public function wasSaved(): bool {
        return $this->saved;
      }

      public function getSetValue(string $fieldName): mixed {
        return $this->setValues[$fieldName] ?? NULL;
      }

      public function getCacheContexts(): array {
        return [];
      }

      public function getCacheTags(): array {
        return ['programa_participante_ei:1'];
      }

      public function getCacheMaxAge(): int {
        return -1;
      }
    };
  }

  /**
   * Crea mock de actuacion STO.
   *
   * MOCK-DYNPROP-001: Clase anonima.
   */
  protected function createActuacionMock(string $tipo, int $duracionMinutos): object {
    return new class($tipo, $duracionMinutos) {
      public function __construct(
        private readonly string $tipo,
        private readonly int $duracionMinutos,
      ) {}

      public function get(string $fieldName): object {
        $map = [
          'tipo_actuacion' => $this->tipo,
          'duracion_minutos' => $this->duracionMinutos,
        ];
        $value = $map[$fieldName] ?? NULL;
        return new class($value) {
          public function __construct(public readonly mixed $value) {}
        };
      }

      public function getCacheContexts(): array {
        return [];
      }

      public function getCacheTags(): array {
        return ['actuacion_sto:1'];
      }

      public function getCacheMaxAge(): int {
        return -1;
      }
    };
  }

}
