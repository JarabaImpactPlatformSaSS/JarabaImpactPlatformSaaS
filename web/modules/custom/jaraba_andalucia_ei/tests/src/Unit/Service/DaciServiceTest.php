<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\jaraba_andalucia_ei\Service\DaciService;
use Drupal\jaraba_andalucia_ei\Service\ExpedienteService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para DaciService.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\DaciService
 * @group jaraba_andalucia_ei
 */
class DaciServiceTest extends UnitTestCase {

  /**
   * El servicio bajo test.
   */
  protected DaciService $service;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock storage.
   */
  protected EntityStorageInterface $storage;

  /**
   * Stub ExpedienteService (clase anonima, no mock).
   *
   * El metodo createDocument() no existe aun en ExpedienteService,
   * por tanto createMock() no puede configurarlo.
   * MOCK-METHOD-001: Usamos clase anonima con typed properties.
   */
  protected object $expedienteService;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->storage = $this->createMock(EntityStorageInterface::class);
    $logger = $this->createMock(LoggerInterface::class);

    // Stub ExpedienteService con createDocument configurable.
    $this->expedienteService = $this->createExpedienteServiceStub(42);

    $this->entityTypeManager->method('getStorage')
      ->with('programa_participante_ei')
      ->willReturn($this->storage);

    $this->service = new DaciService(
      $this->entityTypeManager,
      $this->expedienteService,
      NULL,
      $logger,
    );
  }

  /**
   * Crea un stub de ExpedienteService con createDocument configurable.
   */
  protected function createExpedienteServiceStub(?int $returnDocId): object {
    return new class($returnDocId) extends ExpedienteService {
      private ?int $returnDocId;

      public function __construct(?int $returnDocId) {
        // No llamamos parent — es un stub.
        $this->returnDocId = $returnDocId;
      }

      public function createDocument(int $participanteId, string $tipo, string $titulo, ?string $content = NULL, ?int $tenantId = NULL): ?int {
        return $this->returnDocId;
      }
    };
  }

  /**
   * @covers ::generarDaci
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function generarDaciParticipanteNoExisteDevuelveNull(): void {
    $this->storage->method('load')->with(999)->willReturn(NULL);

    $this->assertNull($this->service->generarDaci(999));
  }

  /**
   * @covers ::generarDaci
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function generarDaciParticipanteYaFirmadoDevuelveNull(): void {
    $participante = $this->createParticipanteMock(1, [
      'isDaciFirmado' => TRUE,
      'dni_nie' => '12345678A',
      'provincia_participacion' => 'Sevilla',
      'colectivo' => 'joven',
      'carril' => 'carril_a',
      'tenant_id_target' => 10,
    ]);
    $this->storage->method('load')->with(1)->willReturn($participante);

    $this->assertNull($this->service->generarDaci(1));
  }

  /**
   * @covers ::generarDaci
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function generarDaciExitosoDevuelveDocumentoId(): void {
    $participante = $this->createParticipanteMock(1, [
      'isDaciFirmado' => FALSE,
      'dni_nie' => '12345678A',
      'provincia_participacion' => 'Sevilla',
      'colectivo' => 'joven',
      'carril' => 'carril_a',
      'tenant_id_target' => 10,
    ]);
    $this->storage->method('load')->with(1)->willReturn($participante);

    // expedienteService stub ya devuelve 42 por defecto.
    $result = $this->service->generarDaci(1);
    $this->assertEquals(42, $result);
  }

  /**
   * @covers ::firmarDaci
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function firmarDaciParticipanteNoExisteDevuelveFalse(): void {
    $this->storage->method('load')->with(999)->willReturn(NULL);

    $this->assertFalse($this->service->firmarDaci(999));
  }

  /**
   * @covers ::firmarDaci
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function firmarDaciExitosoMarcaCampos(): void {
    $participante = $this->createParticipanteMock(1, []);
    $this->storage->method('load')->with(1)->willReturn($participante);

    $result = $this->service->firmarDaci(1);

    $this->assertTrue($result);
    // Verificar que se establecieron los campos daci_firmado y daci_fecha_firma.
    $this->assertTrue($participante->getSetValue('daci_firmado'));
    $this->assertNotNull($participante->getSetValue('daci_fecha_firma'));
    // Verificar que la fecha tiene formato Y-m-d.
    $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $participante->getSetValue('daci_fecha_firma'));
    $this->assertTrue($participante->wasSaved());
  }

  /**
   * @covers ::generarYFirmarDaci
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function generarYFirmarDaciExitoso(): void {
    $participante = $this->createParticipanteMock(1, [
      'isDaciFirmado' => FALSE,
      'dni_nie' => '12345678A',
      'provincia_participacion' => 'Sevilla',
      'colectivo' => 'joven',
      'carril' => 'carril_a',
      'tenant_id_target' => 10,
    ]);

    // El storage se llama dos veces: generarDaci + firmarDaci.
    $this->storage->method('load')->with(1)->willReturn($participante);

    // expedienteService stub ya devuelve 42 por defecto.
    $result = $this->service->generarYFirmarDaci(1);

    $this->assertTrue($result['success']);
    $this->assertEquals(42, $result['documento_id']);
    $this->assertStringContainsString('firmado correctamente', $result['message']);
  }

  /**
   * @covers ::generarYFirmarDaci
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function generarYFirmarDaciFallaEnGeneracion(): void {
    $this->storage->method('load')->with(999)->willReturn(NULL);

    $result = $this->service->generarYFirmarDaci(999);

    $this->assertFalse($result['success']);
    $this->assertNull($result['documento_id']);
    $this->assertStringContainsString('No se pudo generar', $result['message']);
  }

  /**
   * @covers ::generarYFirmarDaci
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function generarYFirmarDaciGeneraPeroNoFirma(): void {
    // Primera llamada a load (generarDaci): participante con isDaciFirmado=FALSE.
    // Segunda llamada a load (firmarDaci): devuelve NULL para simular fallo.
    $participante = $this->createParticipanteMock(1, [
      'isDaciFirmado' => FALSE,
      'dni_nie' => '12345678A',
      'provincia_participacion' => 'Sevilla',
      'colectivo' => 'joven',
      'carril' => 'carril_a',
      'tenant_id_target' => 10,
    ]);

    $callCount = 0;
    $this->storage->method('load')->with(1)->willReturnCallback(
      function () use ($participante, &$callCount) {
        $callCount++;
        // Primera llamada (generarDaci): devuelve participante.
        // Segunda llamada (firmarDaci): devuelve NULL para simular fallo.
        return $callCount === 1 ? $participante : NULL;
      }
    );

    // expedienteService stub ya devuelve 42 por defecto.
    $result = $this->service->generarYFirmarDaci(1);

    $this->assertFalse($result['success']);
    $this->assertEquals(42, $result['documento_id']);
    $this->assertStringContainsString('no se pudo marcar como firmado', $result['message']);
  }

  /**
   * @covers ::generarDaci
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function generarDaciConBrandedPdfService(): void {
    $pdfService = new class {
      public bool $called = FALSE;

      public function generateReport(string $template, array $data, array $options): string {
        $this->called = TRUE;
        return 'PDF_CONTENT';
      }
    };

    $participante = $this->createParticipanteMock(1, [
      'isDaciFirmado' => FALSE,
      'dni_nie' => '12345678A',
      'provincia_participacion' => 'Sevilla',
      'colectivo' => 'joven',
      'carril' => 'carril_a',
      'tenant_id_target' => 10,
    ]);
    $this->storage->method('load')->with(1)->willReturn($participante);

    // Recrear servicio con brandedPdfService activo y stub que devuelve 55.
    $expedienteStub55 = $this->createExpedienteServiceStub(55);
    $serviceWithPdf = new DaciService(
      $this->entityTypeManager,
      $expedienteStub55,
      $pdfService,
      $this->createMock(LoggerInterface::class),
    );

    $result = $serviceWithPdf->generarDaci(1);

    $this->assertEquals(55, $result);
    $this->assertTrue($pdfService->called);
  }

  /**
   * Crea mock de participante para DaciService.
   *
   * MOCK-DYNPROP-001: Clase anonima con typed properties.
   * TEST-CACHE-001: Cache metadata.
   */
  protected function createParticipanteMock(int $id, array $options): object {
    return new class($id, $options) {
      /** @var array<string, mixed> */
      private array $setValues = [];
      private bool $saved = FALSE;

      public function __construct(
        private readonly int $id,
        private readonly array $options,
      ) {}

      public function id(): int {
        return $this->id;
      }

      public function isDaciFirmado(): bool {
        return $this->options['isDaciFirmado'] ?? FALSE;
      }

      public function getOwner(): ?AccountInterface {
        return NULL;
      }

      public function get(string $fieldName): object {
        // Manejar tenant_id con target_id.
        if ($fieldName === 'tenant_id') {
          $targetId = $this->options['tenant_id_target'] ?? NULL;
          return new class($targetId) {
            public mixed $target_id;

            public function __construct(mixed $targetId) {
              $this->target_id = $targetId;
            }
          };
        }

        $value = $this->options[$fieldName] ?? NULL;
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
        return ["programa_participante_ei:{$this->id}"];
      }

      public function getCacheMaxAge(): int {
        return -1;
      }
    };
  }

}
