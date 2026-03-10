<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_andalucia_ei\Service\JustificacionEconomicaService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para JustificacionEconomicaService.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\JustificacionEconomicaService
 * @group jaraba_andalucia_ei
 */
class JustificacionEconomicaServiceTest extends UnitTestCase {

  /**
   * El servicio bajo test.
   */
  protected JustificacionEconomicaService $service;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock storage.
   */
  protected EntityStorageInterface $storage;

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
    $this->storage = $this->createMock(EntityStorageInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->with('programa_participante_ei')
      ->willReturn($this->storage);

    $this->service = new JustificacionEconomicaService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * @covers ::calcularModuloEconomico
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function calcularModuloEconomicoPersonaAtendidaDevuelve3500(): void {
    $participante = $this->createParticipanteMock(1, [
      'horas_orientacion' => 12.0,
      'horas_formacion' => 55.0,
      'tipo_insercion' => '',
      'es_persona_insertada' => FALSE,
    ]);
    $this->storage->method('load')->with(1)->willReturn($participante);

    $result = $this->service->calcularModuloEconomico(1);

    $this->assertSame(3500, $result['persona_atendida']);
    $this->assertSame(0, $result['persona_insertada']);
    $this->assertSame(3500, $result['total']);
  }

  /**
   * @covers ::calcularModuloEconomico
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function calcularModuloEconomicoPersonaInsertadaDevuelve2500Adicional(): void {
    $participante = $this->createParticipanteMock(2, [
      'horas_orientacion' => 15.0,
      'horas_formacion' => 60.0,
      'tipo_insercion' => 'cuenta_ajena',
      'es_persona_insertada' => TRUE,
    ]);
    $this->storage->method('load')->with(2)->willReturn($participante);

    $result = $this->service->calcularModuloEconomico(2);

    $this->assertSame(3500, $result['persona_atendida']);
    $this->assertSame(2500, $result['persona_insertada']);
    $this->assertSame(6000, $result['total']);
  }

  /**
   * @covers ::calcularModuloEconomico
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function calcularModuloEconomicoSinHorasMinimasDevuelve0(): void {
    $participante = $this->createParticipanteMock(3, [
      'horas_orientacion' => 5.0,
      'horas_formacion' => 20.0,
      'tipo_insercion' => '',
      'es_persona_insertada' => FALSE,
    ]);
    $this->storage->method('load')->with(3)->willReturn($participante);

    $result = $this->service->calcularModuloEconomico(3);

    $this->assertSame(0, $result['persona_atendida']);
    $this->assertSame(0, $result['persona_insertada']);
    $this->assertSame(0, $result['total']);
  }

  /**
   * @covers ::isPersonaAtendida
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function isPersonaAtendidaTrueConHorasSuficientes(): void {
    $participante = $this->createParticipanteMock(10, [
      'horas_orientacion' => 10.0,
      'horas_formacion' => 50.0,
    ]);
    $this->storage->method('load')->with(10)->willReturn($participante);

    $this->assertTrue($this->service->isPersonaAtendida(10));
  }

  /**
   * @covers ::isPersonaAtendida
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function isPersonaAtendidaFalseConOrientacionInsuficiente(): void {
    $participante = $this->createParticipanteMock(11, [
      'horas_orientacion' => 9.0,
      'horas_formacion' => 50.0,
    ]);
    $this->storage->method('load')->with(11)->willReturn($participante);

    $this->assertFalse($this->service->isPersonaAtendida(11));
  }

  /**
   * @covers ::isPersonaAtendida
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function isPersonaAtendidaFalseConFormacionInsuficiente(): void {
    $participante = $this->createParticipanteMock(12, [
      'horas_orientacion' => 10.0,
      'horas_formacion' => 49.0,
    ]);
    $this->storage->method('load')->with(12)->willReturn($participante);

    $this->assertFalse($this->service->isPersonaAtendida(12));
  }

  /**
   * @covers ::isPersonaInsertada
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function isPersonaInsertadaTrueConTipoYFlag(): void {
    $participante = $this->createParticipanteMock(20, [
      'tipo_insercion' => 'cuenta_ajena',
      'es_persona_insertada' => TRUE,
    ]);
    $this->storage->method('load')->with(20)->willReturn($participante);

    $this->assertTrue($this->service->isPersonaInsertada(20));
  }

  /**
   * @covers ::isPersonaInsertada
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function isPersonaInsertadaFalseSinTipoInsercion(): void {
    $participante = $this->createParticipanteMock(21, [
      'tipo_insercion' => '',
      'es_persona_insertada' => TRUE,
    ]);
    $this->storage->method('load')->with(21)->willReturn($participante);

    $this->assertFalse($this->service->isPersonaInsertada(21));
  }

  /**
   * @covers ::isPersonaInsertada
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function isPersonaInsertadaFalseSinFlag(): void {
    $participante = $this->createParticipanteMock(22, [
      'tipo_insercion' => 'cuenta_propia',
      'es_persona_insertada' => FALSE,
    ]);
    $this->storage->method('load')->with(22)->willReturn($participante);

    $this->assertFalse($this->service->isPersonaInsertada(22));
  }

  /**
   * @covers ::getResumenJustificacion
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getResumenJustificacionConParticipantesMixtos(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([1 => 1, 2 => 2, 3 => 3]);

    $this->storage->method('getQuery')->willReturn($query);

    // Participante 1: atendido + insertado.
    $p1 = $this->createParticipanteMock(1, [
      'horas_orientacion' => 15.0,
      'horas_formacion' => 60.0,
      'tipo_insercion' => 'cuenta_ajena',
      'es_persona_insertada' => TRUE,
    ]);

    // Participante 2: solo atendido.
    $p2 = $this->createParticipanteMock(2, [
      'horas_orientacion' => 12.0,
      'horas_formacion' => 55.0,
      'tipo_insercion' => '',
      'es_persona_insertada' => FALSE,
    ]);

    // Participante 3: ni atendido ni insertado.
    $p3 = $this->createParticipanteMock(3, [
      'horas_orientacion' => 5.0,
      'horas_formacion' => 20.0,
      'tipo_insercion' => '',
      'es_persona_insertada' => FALSE,
    ]);

    $this->storage->method('loadMultiple')
      ->with([1 => 1, 2 => 2, 3 => 3])
      ->willReturn([1 => $p1, 2 => $p2, 3 => $p3]);

    // load() para isPersonaAtendida/isPersonaInsertada.
    $this->storage->method('load')
      ->willReturnCallback(static fn(int $id) => match ($id) {
        1 => $p1,
        2 => $p2,
        3 => $p3,
        default => NULL,
      });

    $result = $this->service->getResumenJustificacion(5);

    $this->assertSame(202500, $result['total_presupuesto']);
    // 2 atendidos (p1+p2) x 3500 = 7000, 1 insertado (p1) x 2500 = 2500 -> total = 9500.
    $this->assertSame(9500, $result['total_justificado']);
    $this->assertSame(2, $result['desglose_por_modulo']['atendidos']['count']);
    $this->assertSame(7000, $result['desglose_por_modulo']['atendidos']['importe']);
    $this->assertSame(1, $result['desglose_por_modulo']['insertados']['count']);
    $this->assertSame(2500, $result['desglose_por_modulo']['insertados']['importe']);
  }

  /**
   * @covers ::getResumenJustificacion
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getResumenJustificacionSinParticipantesDevuelveCeros(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->storage->method('getQuery')->willReturn($query);

    $result = $this->service->getResumenJustificacion(1);

    $this->assertSame(202500, $result['total_presupuesto']);
    $this->assertSame(0, $result['total_justificado']);
    $this->assertSame(0.0, $result['porcentaje']);
    $this->assertSame(0, $result['desglose_por_modulo']['atendidos']['count']);
    $this->assertSame(0, $result['desglose_por_modulo']['insertados']['count']);
  }

  /**
   * @covers ::isPersonaAtendida
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function participanteNoEncontradoDevuelveFalse(): void {
    $this->storage->method('load')->with(999)->willReturn(NULL);

    $this->assertFalse($this->service->isPersonaAtendida(999));
    $this->assertFalse($this->service->isPersonaInsertada(999));
  }

  /**
   * Crea un mock de participante con las opciones necesarias.
   *
   * MOCK-DYNPROP-001: Clase anonima con typed properties.
   * TEST-CACHE-001: Cache metadata implementado.
   *
   * @param int $id
   *   ID del participante.
   * @param array $fieldValues
   *   Valores de campos.
   */
  protected function createParticipanteMock(int $id, array $fieldValues): object {
    return new class($id, $fieldValues) {
      public function __construct(
        private readonly int $id,
        private readonly array $fieldValues,
      ) {}

      public function id(): int {
        return $this->id;
      }

      public function label(): ?string {
        return "Test #{$this->id}";
      }

      public function get(string $fieldName): object {
        if ($fieldName === 'tenant_id') {
          $targetId = $this->fieldValues['tenant_id_target'] ?? NULL;
          return new class($targetId) {
            public mixed $target_id;

            public function __construct(mixed $t) {
              $this->target_id = $t;
            }
          };
        }
        $value = $this->fieldValues[$fieldName] ?? NULL;
        return new class($value) {
          public function __construct(public readonly mixed $value) {}
        };
      }

      public function set(string $fieldName, mixed $value): static {
        return $this;
      }

      public function save(): int {
        return 1;
      }

      public function getOwner(): ?object {
        return NULL;
      }

      public function getCacheContexts(): array {
        return [];
      }

      public function getCacheTags(): array {
        return ["programa_participante_ei:{$this->id}"];
      }

      public function getCacheMaxAge(): int {
        return -1;
      }
    };
  }

}
