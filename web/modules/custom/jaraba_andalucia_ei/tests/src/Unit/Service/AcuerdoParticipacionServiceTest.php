<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\jaraba_andalucia_ei\Service\AcuerdoParticipacionService;
use Drupal\jaraba_andalucia_ei\Service\ExpedienteService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para AcuerdoParticipacionService.
 *
 * Documento: Acuerdo_participacion_ICV25.odt (DISTINTO del DACI).
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\AcuerdoParticipacionService
 * @group jaraba_andalucia_ei
 */
class AcuerdoParticipacionServiceTest extends UnitTestCase {

  /**
   * El servicio bajo test.
   */
  protected AcuerdoParticipacionService $service;

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
   * MOCK-METHOD-001: createDocument() no existe en la interface mock.
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

    $this->expedienteService = $this->createExpedienteServiceStub(42);

    $this->entityTypeManager->method('getStorage')
      ->with('programa_participante_ei')
      ->willReturn($this->storage);

    $this->service = new AcuerdoParticipacionService(
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
        $this->returnDocId = $returnDocId;
      }

      public function createDocument(int $participanteId, string $tipo, string $titulo, ?string $content = NULL, ?int $tenantId = NULL): ?int {
        return $this->returnDocId;
      }
    };
  }

  /**
   * @covers ::generarAcuerdo
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function generarAcuerdoParticipanteNoExisteDevuelveNull(): void {
    $this->storage->method('load')->with(999)->willReturn(NULL);

    $this->assertNull($this->service->generarAcuerdo(999));
  }

  /**
   * @covers ::generarAcuerdo
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function generarAcuerdoParticipanteYaFirmadoDevuelveNull(): void {
    $participante = $this->createParticipanteMock(1, [
      'isAcuerdoParticipacionFirmado' => TRUE,
      'dni_nie' => '12345678A',
      'provincia_participacion' => 'Sevilla',
      'colectivo' => 'joven',
      'carril' => 'carril_a',
      'tenant_id_target' => 10,
    ]);
    $this->storage->method('load')->with(1)->willReturn($participante);

    $this->assertNull($this->service->generarAcuerdo(1));
  }

  /**
   * @covers ::generarAcuerdo
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function generarAcuerdoExitosoDevuelveDocumentoId(): void {
    $participante = $this->createParticipanteMock(1, [
      'isAcuerdoParticipacionFirmado' => FALSE,
      'dni_nie' => '12345678A',
      'provincia_participacion' => 'Sevilla',
      'colectivo' => 'joven',
      'carril' => 'carril_a',
      'tenant_id_target' => 10,
    ]);
    $this->storage->method('load')->with(1)->willReturn($participante);

    $result = $this->service->generarAcuerdo(1);
    $this->assertEquals(42, $result);
  }

  /**
   * @covers ::firmarAcuerdo
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function firmarAcuerdoParticipanteNoExisteDevuelveFalse(): void {
    $this->storage->method('load')->with(999)->willReturn(NULL);

    $this->assertFalse($this->service->firmarAcuerdo(999));
  }

  /**
   * @covers ::firmarAcuerdo
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function firmarAcuerdoExitosoMarcaCampos(): void {
    $participante = $this->createParticipanteMock(1, []);
    $this->storage->method('load')->with(1)->willReturn($participante);

    $result = $this->service->firmarAcuerdo(1);

    $this->assertTrue($result);
    $this->assertTrue($participante->getSetValue('acuerdo_participacion_firmado'));
    $this->assertNotNull($participante->getSetValue('acuerdo_participacion_fecha'));
    $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $participante->getSetValue('acuerdo_participacion_fecha'));
    $this->assertTrue($participante->wasSaved());
  }

  /**
   * @covers ::generarYFirmarAcuerdo
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function generarYFirmarAcuerdoExitoso(): void {
    $participante = $this->createParticipanteMock(1, [
      'isAcuerdoParticipacionFirmado' => FALSE,
      'dni_nie' => '12345678A',
      'provincia_participacion' => 'Sevilla',
      'colectivo' => 'joven',
      'carril' => 'carril_a',
      'tenant_id_target' => 10,
    ]);
    $this->storage->method('load')->with(1)->willReturn($participante);

    $result = $this->service->generarYFirmarAcuerdo(1);

    $this->assertTrue($result['success']);
    $this->assertEquals(42, $result['documento_id']);
    $this->assertStringContainsString('firmado correctamente', $result['message']);
  }

  /**
   * @covers ::generarYFirmarAcuerdo
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function generarYFirmarAcuerdoFallaEnGeneracion(): void {
    $this->storage->method('load')->with(999)->willReturn(NULL);

    $result = $this->service->generarYFirmarAcuerdo(999);

    $this->assertFalse($result['success']);
    $this->assertNull($result['documento_id']);
    $this->assertStringContainsString('No se pudo generar', $result['message']);
  }

  /**
   * @covers ::generarYFirmarAcuerdo
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function generarYFirmarAcuerdoGeneraPeroNoFirma(): void {
    $participante = $this->createParticipanteMock(1, [
      'isAcuerdoParticipacionFirmado' => FALSE,
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
        return $callCount === 1 ? $participante : NULL;
      }
    );

    $result = $this->service->generarYFirmarAcuerdo(1);

    $this->assertFalse($result['success']);
    $this->assertEquals(42, $result['documento_id']);
    $this->assertStringContainsString('no se pudo marcar como firmado', $result['message']);
  }

  /**
   * @covers ::generarAcuerdo
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function generarAcuerdoConBrandedPdfService(): void {
    $pdfService = new class {
      public bool $called = FALSE;

      public function generateReport(string $template, array $data, array $options): string {
        $this->called = TRUE;
        return 'PDF_CONTENT';
      }
    };

    $participante = $this->createParticipanteMock(1, [
      'isAcuerdoParticipacionFirmado' => FALSE,
      'dni_nie' => '12345678A',
      'provincia_participacion' => 'Sevilla',
      'colectivo' => 'joven',
      'carril' => 'carril_a',
      'tenant_id_target' => 10,
    ]);
    $this->storage->method('load')->with(1)->willReturn($participante);

    $expedienteStub55 = $this->createExpedienteServiceStub(55);
    $serviceWithPdf = new AcuerdoParticipacionService(
      $this->entityTypeManager,
      $expedienteStub55,
      $pdfService,
      $this->createMock(LoggerInterface::class),
    );

    $result = $serviceWithPdf->generarAcuerdo(1);

    $this->assertEquals(55, $result);
    $this->assertTrue($pdfService->called);
  }

  /**
   * Crea mock de participante para AcuerdoParticipacionService.
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

      public function isAcuerdoParticipacionFirmado(): bool {
        return $this->options['isAcuerdoParticipacionFirmado'] ?? FALSE;
      }

      public function getOwner(): ?AccountInterface {
        return NULL;
      }

      public function get(string $fieldName): object {
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
