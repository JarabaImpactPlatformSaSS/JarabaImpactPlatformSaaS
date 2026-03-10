<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_andalucia_ei\Service\ProspeccionService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para ProspeccionService.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\ProspeccionService
 * @group jaraba_andalucia_ei
 */
class ProspeccionServiceTest extends UnitTestCase {

  /**
   * El servicio bajo test.
   */
  protected ProspeccionService $service;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock storage para prospeccion_empresarial.
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
      ->with('prospeccion_empresarial')
      ->willReturn($this->storage);

    $this->service = new ProspeccionService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * @covers ::getProspeccionesByEstado
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getProspeccionesByEstadoDevuelveResultadosFiltrados(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();

    // Primer execute() del count query devuelve total, segundo devuelve ids.
    $query->method('execute')
      ->willReturnOnConsecutiveCalls(1, [5 => 5]);

    $this->storage->method('getQuery')->willReturn($query);

    $prospeccion = $this->createProspeccionMock(5, [
      'empresa_nombre' => 'Empresa Test',
      'sector' => 'tecnologia',
      'estado' => 'activa',
      'contacto_nombre' => 'Juan',
      'contacto_email' => 'juan@test.com',
      'puestos_ofertados' => 3,
      'puestos_cubiertos' => 1,
      'ultimo_contacto' => '2026-03-01T10:00:00',
      'changed' => '1709280000',
    ]);

    $this->storage->method('loadMultiple')
      ->with([5 => 5])
      ->willReturn([5 => $prospeccion]);

    $result = $this->service->getProspeccionesByEstado(1, 'activa');

    $this->assertSame(1, $result['total']);
    $this->assertCount(1, $result['items']);
    $this->assertSame(5, $result['items'][0]['id']);
    $this->assertSame('Empresa Test', $result['items'][0]['empresa_nombre']);
    $this->assertSame('activa', $result['items'][0]['estado']);
  }

  /**
   * @covers ::getProspeccionesByEstado
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getProspeccionesByEstadoVacioDevuelveTodos(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')
      ->willReturnOnConsecutiveCalls(2, [1 => 1, 2 => 2]);

    $this->storage->method('getQuery')->willReturn($query);

    $p1 = $this->createProspeccionMock(1, [
      'empresa_nombre' => 'Empresa A',
      'sector' => 'comercio',
      'estado' => 'activa',
    ]);
    $p2 = $this->createProspeccionMock(2, [
      'empresa_nombre' => 'Empresa B',
      'sector' => 'hosteleria',
      'estado' => 'contactada',
    ]);

    $this->storage->method('loadMultiple')
      ->willReturn([1 => $p1, 2 => $p2]);

    $result = $this->service->getProspeccionesByEstado(1, '');

    $this->assertSame(2, $result['total']);
    $this->assertCount(2, $result['items']);
  }

  /**
   * @covers ::getEstadisticas
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getEstadisticasDevuelveConteosCorrectos(): void {
    $queryIndex = 0;
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('count')->willReturnSelf();

    // Total(10), activa(3), contactada(4), colaborando(2), descartada(1), then ids for loadMultiple.
    $query->method('execute')
      ->willReturnOnConsecutiveCalls(10, 3, 4, 2, 1, [1 => 1, 2 => 2]);

    $this->storage->method('getQuery')->willReturn($query);

    $p1 = $this->createProspeccionMock(1, [
      'puestos_ofertados' => 5,
      'puestos_cubiertos' => 2,
    ]);
    $p2 = $this->createProspeccionMock(2, [
      'puestos_ofertados' => 3,
      'puestos_cubiertos' => 1,
    ]);

    $this->storage->method('loadMultiple')->willReturn([1 => $p1, 2 => $p2]);

    $stats = $this->service->getEstadisticas(1);

    $this->assertSame(10, $stats['total']);
    $this->assertSame(3, $stats['por_estado']['activa']);
    $this->assertSame(4, $stats['por_estado']['contactada']);
    $this->assertSame(2, $stats['por_estado']['colaborando']);
    $this->assertSame(1, $stats['por_estado']['descartada']);
    $this->assertSame(8, $stats['puestos_ofertados']);
    $this->assertSame(3, $stats['puestos_cubiertos']);
  }

  /**
   * @covers ::getEstadisticas
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getEstadisticasSinDatosDevuelveCeros(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    // Total(0), activa(0), contactada(0), colaborando(0), descartada(0), ids=[].
    $query->method('execute')
      ->willReturnOnConsecutiveCalls(0, 0, 0, 0, 0, []);

    $this->storage->method('getQuery')->willReturn($query);
    $this->storage->method('loadMultiple')->willReturn([]);

    $stats = $this->service->getEstadisticas(1);

    $this->assertSame(0, $stats['total']);
    $this->assertSame(0, $stats['por_estado']['activa']);
    $this->assertSame(0, $stats['puestos_ofertados']);
    $this->assertSame(0, $stats['puestos_cubiertos']);
  }

  /**
   * @covers ::registrarContacto
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function registrarContactoActualizaEntidad(): void {
    $setFields = [];
    $prospeccion = $this->createProspeccionMock(10, [
      'estado' => 'activa',
    ], $setFields);

    $this->storage->method('load')->with(10)->willReturn($prospeccion);

    $result = $this->service->registrarContacto(10, 'Interesados', 'Llamar de nuevo en 2 semanas');

    $this->assertTrue($result['success']);
    $this->assertStringContainsString('correctamente', $result['message']);
  }

  /**
   * @covers ::registrarContacto
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function registrarContactoEntidadNoEncontradaDevuelveFalse(): void {
    $this->storage->method('load')->with(999)->willReturn(NULL);

    $result = $this->service->registrarContacto(999, 'Resultado', 'Notas');

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('no encontrada', $result['message']);
  }

  /**
   * Crea un mock de prospeccion empresarial.
   *
   * MOCK-DYNPROP-001: Clase anonima con typed properties.
   * TEST-CACHE-001: Cache metadata implementado.
   */
  protected function createProspeccionMock(int $id, array $fieldValues, array &$setTracker = []): object {
    return new class($id, $fieldValues, $setTracker) {
      public function __construct(
        private readonly int $id,
        private readonly array $fieldValues,
        private array &$setTracker,
      ) {}

      public function id(): int {
        return $this->id;
      }

      public function label(): ?string {
        return $this->fieldValues['empresa_nombre'] ?? "Prospeccion #{$this->id}";
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
        $this->setTracker[$fieldName] = $value;
        return $this;
      }

      public function save(): int {
        return 1;
      }

      public function getCacheContexts(): array {
        return [];
      }

      public function getCacheTags(): array {
        return ["prospeccion_empresarial:{$this->id}"];
      }

      public function getCacheMaxAge(): int {
        return -1;
      }
    };
  }

}
