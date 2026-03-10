<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_andalucia_ei\Service\PuntosImpactoEiService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para PuntosImpactoEiService.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\PuntosImpactoEiService
 * @group jaraba_andalucia_ei
 */
class PuntosImpactoEiServiceTest extends UnitTestCase {

  /**
   * El servicio bajo test.
   */
  protected PuntosImpactoEiService $service;

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

    $this->service = new PuntosImpactoEiService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * @covers ::calcularPuntosParticipante
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function calcularPuntosParticipanteConProgresionCompletaDevuelveMaximo(): void {
    // seguimiento(50) + orient 20h capped(20) + form 80h*0.5=40 capped(30) + insercion(25) + incentivo(5) = 130.
    $participante = $this->createParticipanteMock(1, [
      'fase_actual' => 'seguimiento',
      'horas_orientacion' => 25.0,
      'horas_formacion' => 80.0,
      'es_persona_insertada' => TRUE,
      'incentivo_recibido' => TRUE,
    ]);
    $this->storage->method('load')->with(1)->willReturn($participante);

    $result = $this->service->calcularPuntosParticipante(1);

    // fase(50) + orientacion(20, capped) + formacion(30, capped) + insercion(25) + incentivo(5) = 130.
    $this->assertSame(130, $result['total']);
    $this->assertNotEmpty($result['desglose']);

    $conceptos = array_column($result['desglose'], 'concepto');
    $this->assertContains('fase_progresion', $conceptos);
    $this->assertContains('horas_orientacion', $conceptos);
    $this->assertContains('horas_formacion', $conceptos);
    $this->assertContains('insercion_lograda', $conceptos);
    $this->assertContains('incentivo_recibido', $conceptos);
  }

  /**
   * @covers ::calcularPuntosParticipante
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function calcularPuntosParticipanteSinDatosDevuelve0(): void {
    $this->storage->method('load')->with(999)->willReturn(NULL);

    $result = $this->service->calcularPuntosParticipante(999);

    $this->assertSame(0, $result['total']);
    $this->assertSame([], $result['desglose']);
  }

  /**
   * @covers ::calcularPuntosParticipante
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function calcularPuntosParticipanteDesgloseCorrecto(): void {
    // acogida(10) + orient 5h*1=5 + form 10h*0.5=5 = 20. No insercion, no incentivo.
    $participante = $this->createParticipanteMock(2, [
      'fase_actual' => 'acogida',
      'horas_orientacion' => 5.0,
      'horas_formacion' => 10.0,
      'es_persona_insertada' => FALSE,
      'incentivo_recibido' => FALSE,
    ]);
    $this->storage->method('load')->with(2)->willReturn($participante);

    $result = $this->service->calcularPuntosParticipante(2);

    $this->assertSame(20, $result['total']);

    $puntosMap = [];
    foreach ($result['desglose'] as $item) {
      $puntosMap[$item['concepto']] = $item['puntos'];
    }

    $this->assertSame(10, $puntosMap['fase_progresion']);
    $this->assertSame(5, $puntosMap['horas_orientacion']);
    $this->assertSame(5, $puntosMap['horas_formacion']);
    $this->assertArrayNotHasKey('insercion_lograda', $puntosMap);
    $this->assertArrayNotHasKey('incentivo_recibido', $puntosMap);
  }

  /**
   * @covers ::calcularPuntosParticipante
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function calcularPuntosParticipanteAlumniIncluyeBonus(): void {
    // alumni no esta en PUNTOS_POR_FASE -> default 10.
    // alumni bonus: 10. Total: 10 + 10 = 20 (sin horas).
    $participante = $this->createParticipanteMock(3, [
      'fase_actual' => 'alumni',
      'horas_orientacion' => 0.0,
      'horas_formacion' => 0.0,
      'es_persona_insertada' => FALSE,
      'incentivo_recibido' => FALSE,
    ]);
    $this->storage->method('load')->with(3)->willReturn($participante);

    $result = $this->service->calcularPuntosParticipante(3);

    $conceptos = array_column($result['desglose'], 'concepto');
    $this->assertContains('alumni', $conceptos);
    // fase(10 default) + alumni(10) = 20.
    $this->assertSame(20, $result['total']);
  }

  /**
   * @covers ::getRankingParticipantes
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getRankingParticipantesDevuelveOrdenadoPorPuntos(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([1 => 1, 2 => 2]);

    $this->storage->method('getQuery')->willReturn($query);

    // p1: acogida(10) = 10 puntos.
    $p1 = $this->createParticipanteMock(1, [
      'fase_actual' => 'acogida',
      'horas_orientacion' => 0.0,
      'horas_formacion' => 0.0,
      'es_persona_insertada' => FALSE,
      'incentivo_recibido' => FALSE,
    ]);

    // p2: insercion(40) + orient 15h(15) + form 30h*0.5=15 + insercion(25) = 95.
    $p2 = $this->createParticipanteMock(2, [
      'fase_actual' => 'insercion',
      'horas_orientacion' => 15.0,
      'horas_formacion' => 30.0,
      'es_persona_insertada' => TRUE,
      'incentivo_recibido' => FALSE,
    ]);

    $this->storage->method('loadMultiple')
      ->with([1 => 1, 2 => 2])
      ->willReturn([1 => $p1, 2 => $p2]);

    $this->storage->method('load')
      ->willReturnCallback(static fn(int $id) => match ($id) {
        1 => $p1,
        2 => $p2,
        default => NULL,
      });

    $ranking = $this->service->getRankingParticipantes(5);

    $this->assertCount(2, $ranking);
    // p2 deberia estar primero (mas puntos).
    $this->assertSame(2, $ranking[0]['participante_id']);
    $this->assertSame(1, $ranking[1]['participante_id']);
    $this->assertGreaterThan($ranking[1]['puntos'], $ranking[0]['puntos']);
  }

  /**
   * @covers ::getImpactoGlobalPrograma
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getImpactoGlobalProgramaConDatosMixtos(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([1 => 1, 2 => 2]);

    $this->storage->method('getQuery')->willReturn($query);

    // p1: acogida(10) = 10 total.
    $p1 = $this->createParticipanteMock(1, [
      'fase_actual' => 'acogida',
      'horas_orientacion' => 0.0,
      'horas_formacion' => 0.0,
      'es_persona_insertada' => FALSE,
      'incentivo_recibido' => FALSE,
    ]);

    // p2: insercion(40) + orient 10h(10) + form 20h*0.5=10 + insercion(25) = 85.
    $p2 = $this->createParticipanteMock(2, [
      'fase_actual' => 'insercion',
      'horas_orientacion' => 10.0,
      'horas_formacion' => 20.0,
      'es_persona_insertada' => TRUE,
      'incentivo_recibido' => FALSE,
    ]);

    $this->storage->method('loadMultiple')
      ->with([1 => 1, 2 => 2])
      ->willReturn([1 => $p1, 2 => $p2]);

    $this->storage->method('load')
      ->willReturnCallback(static fn(int $id) => match ($id) {
        1 => $p1,
        2 => $p2,
        default => NULL,
      });

    $impacto = $this->service->getImpactoGlobalPrograma(5);

    $this->assertSame(2, $impacto['participantes_evaluados']);
    $this->assertSame(95, $impacto['total_puntos']); // 10 + 85.
    $this->assertSame(85, $impacto['max_puntos']);
    $this->assertSame(10, $impacto['min_puntos']);
    $this->assertSame(47.5, $impacto['media_puntos']);
  }

  /**
   * @covers ::getImpactoGlobalPrograma
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getImpactoGlobalProgramaSinParticipantesDevuelveCeros(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->storage->method('getQuery')->willReturn($query);

    $impacto = $this->service->getImpactoGlobalPrograma(1);

    $this->assertSame(0, $impacto['total_puntos']);
    $this->assertSame(0.0, $impacto['media_puntos']);
    $this->assertSame(0, $impacto['max_puntos']);
    $this->assertSame(0, $impacto['min_puntos']);
    $this->assertSame(0, $impacto['participantes_evaluados']);
  }

  /**
   * Crea un mock de participante con las opciones necesarias.
   *
   * MOCK-DYNPROP-001: Clase anonima con typed properties.
   * TEST-CACHE-001: Cache metadata implementado.
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
