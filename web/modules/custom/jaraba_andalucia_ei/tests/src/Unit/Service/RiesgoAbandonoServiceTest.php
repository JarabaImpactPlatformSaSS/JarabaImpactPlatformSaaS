<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_andalucia_ei\Service\RiesgoAbandonoService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para RiesgoAbandonoService.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\RiesgoAbandonoService
 * @group jaraba_andalucia_ei
 */
class RiesgoAbandonoServiceTest extends UnitTestCase {

  /**
   * El servicio bajo test.
   */
  protected RiesgoAbandonoService $service;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock storage para programa_participante_ei.
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
      ->willReturnCallback(fn(string $type) => match ($type) {
        'programa_participante_ei' => $this->storage,
        default => $this->createMock(EntityStorageInterface::class),
      });

    // actuacion_sto no existe en this unit context.
    $this->entityTypeManager->method('hasDefinition')
      ->willReturnCallback(static fn(string $type) => match ($type) {
        'actuacion_sto' => FALSE,
        default => TRUE,
      });

    $this->service = new RiesgoAbandonoService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * @covers ::evaluarRiesgo
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function evaluarRiesgoSinFactoresDevuelveBajo(): void {
    $participante = $this->createParticipanteMock(1, [
      'fase_actual' => 'atencion',
      'semana_actual' => 10,
      'horas_orientacion' => 8.0,
      'horas_formacion' => 20.0,
      'asistencia_porcentaje' => 85.0,
    ]);
    $this->storage->method('load')->with(1)->willReturn($participante);

    $result = $this->service->evaluarRiesgo(1);

    $this->assertSame('bajo', $result['nivel']);
    $this->assertSame(0, $result['score']);
    $this->assertSame([], $result['factores']);
  }

  /**
   * @covers ::evaluarRiesgo
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function evaluarRiesgoEstancamientoAcogidaDevuelveCritico(): void {
    // >20 semanas en acogida = 30 puntos + baja asistencia = 20 puntos
    // + horas insuficientes para semana 25 = 15+15 puntos = 80 -> critico.
    $participante = $this->createParticipanteMock(2, [
      'fase_actual' => 'acogida',
      'semana_actual' => 25,
      'horas_orientacion' => 2.0,
      'horas_formacion' => 5.0,
      'asistencia_porcentaje' => 50.0,
    ]);
    $this->storage->method('load')->with(2)->willReturn($participante);

    $result = $this->service->evaluarRiesgo(2);

    $this->assertSame('critico', $result['nivel']);
    $this->assertGreaterThanOrEqual(60, $result['score']);

    $codigos = array_column($result['factores'], 'codigo');
    $this->assertContains('estancamiento_acogida', $codigos);
    $this->assertContains('baja_asistencia', $codigos);
  }

  /**
   * @covers ::evaluarRiesgo
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function evaluarRiesgoBajaAsistenciaAumentaRiesgo(): void {
    $participante = $this->createParticipanteMock(3, [
      'fase_actual' => 'diagnostico',
      'semana_actual' => 5,
      'horas_orientacion' => 3.0,
      'horas_formacion' => 10.0,
      'asistencia_porcentaje' => 40.0,
    ]);
    $this->storage->method('load')->with(3)->willReturn($participante);

    $result = $this->service->evaluarRiesgo(3);

    $codigos = array_column($result['factores'], 'codigo');
    $this->assertContains('baja_asistencia', $codigos);
    $this->assertGreaterThanOrEqual(20, $result['score']);
  }

  /**
   * @covers ::evaluarRiesgo
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function evaluarRiesgoHorasInsuficientesAnadePuntos(): void {
    // Semana 15: expected orient = round((15/30)*10, 1) = 5.0, expected form = round((15/30)*50, 1) = 25.0.
    $participante = $this->createParticipanteMock(4, [
      'fase_actual' => 'atencion',
      'semana_actual' => 15,
      'horas_orientacion' => 2.0,
      'horas_formacion' => 10.0,
      'asistencia_porcentaje' => 80.0,
    ]);
    $this->storage->method('load')->with(4)->willReturn($participante);

    $result = $this->service->evaluarRiesgo(4);

    $codigos = array_column($result['factores'], 'codigo');
    $this->assertContains('orientacion_insuficiente', $codigos);
    $this->assertContains('formacion_insuficiente', $codigos);
    // 15 + 15 = 30 -> medio.
    $this->assertSame(30, $result['score']);
    $this->assertSame('medio', $result['nivel']);
  }

  /**
   * @covers ::evaluarRiesgo
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function evaluarRiesgoParticipanteNoEncontradoDevuelveNeutral(): void {
    $this->storage->method('load')->with(999)->willReturn(NULL);

    $result = $this->service->evaluarRiesgo(999);

    $this->assertSame('bajo', $result['nivel']);
    $this->assertSame(0, $result['score']);
    $this->assertSame([], $result['factores']);
  }

  /**
   * @covers ::getParticipantesEnRiesgo
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getParticipantesEnRiesgoFiltraPorNivelMinimo(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([1 => 1, 2 => 2]);

    $this->storage->method('getQuery')->willReturn($query);

    // Participante 1: riesgo alto (estancamiento acogida + baja asistencia).
    $p1 = $this->createParticipanteMock(1, [
      'fase_actual' => 'acogida',
      'semana_actual' => 25,
      'horas_orientacion' => 1.0,
      'horas_formacion' => 5.0,
      'asistencia_porcentaje' => 40.0,
    ]);

    // Participante 2: sin riesgo.
    $p2 = $this->createParticipanteMock(2, [
      'fase_actual' => 'atencion',
      'semana_actual' => 10,
      'horas_orientacion' => 8.0,
      'horas_formacion' => 20.0,
      'asistencia_porcentaje' => 90.0,
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

    // Filtrar nivel minimo 'medio' (score >= 20).
    $result = $this->service->getParticipantesEnRiesgo(5, 'medio');

    // Solo p1 deberia pasar (tiene score >= 20).
    $this->assertCount(1, $result);
    $this->assertSame(1, $result[0]['participante_id']);
  }

  /**
   * @covers ::getParticipantesEnRiesgo
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getParticipantesEnRiesgoSinTenantDevuelveVacio(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->storage->method('getQuery')->willReturn($query);

    $result = $this->service->getParticipantesEnRiesgo(NULL, 'medio');

    $this->assertSame([], $result);
  }

  /**
   * @covers ::evaluarRiesgo
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function scoreThresholdsAplicadosCorrectamente(): void {
    // Score 0-19 -> bajo.
    $pBajo = $this->createParticipanteMock(10, [
      'fase_actual' => 'atencion',
      'semana_actual' => 5,
      'horas_orientacion' => 5.0,
      'horas_formacion' => 12.0,
      'asistencia_porcentaje' => 90.0,
    ]);
    $this->storage->method('load')
      ->willReturnCallback(fn(int $id) => match ($id) {
        10 => $pBajo,
        default => NULL,
      });

    $resultBajo = $this->service->evaluarRiesgo(10);
    $this->assertSame('bajo', $resultBajo['nivel']);
    $this->assertLessThan(20, $resultBajo['score']);
  }

  /**
   * Score critico: multiple factors stack to 60+.
   *
   * @covers ::evaluarRiesgo
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function scoreThresholdCriticoConMultiplesFactores(): void {
    // estancamiento_acogida (30) + baja_asistencia (20) + orientacion_insuficiente (15) = 65 -> critico.
    $pCritico = $this->createParticipanteMock(50, [
      'fase_actual' => 'acogida',
      'semana_actual' => 25,
      'horas_orientacion' => 0.0,
      'horas_formacion' => 0.0,
      'asistencia_porcentaje' => 30.0,
    ]);
    $this->storage->method('load')->with(50)->willReturn($pCritico);

    $resultCritico = $this->service->evaluarRiesgo(50);
    $this->assertSame('critico', $resultCritico['nivel']);
    $this->assertGreaterThanOrEqual(60, $resultCritico['score']);
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
