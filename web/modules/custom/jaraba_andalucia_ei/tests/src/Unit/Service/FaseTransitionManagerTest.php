<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Service\FaseTransitionManager;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Tests para FaseTransitionManager.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\FaseTransitionManager
 * @group jaraba_andalucia_ei
 */
class FaseTransitionManagerTest extends UnitTestCase {

  /**
   * El servicio bajo test.
   */
  protected FaseTransitionManager $service;

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
    $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->with('programa_participante_ei')
      ->willReturn($this->storage);

    $this->service = new FaseTransitionManager(
      $this->entityTypeManager,
      $eventDispatcher,
      $this->logger,
    );
  }

  /**
   * @covers ::getTransicionesValidas
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function fasesConstantesTiene6Fases(): void {
    $this->assertCount(6, FaseTransitionManager::FASES);
    $this->assertEquals([
      'acogida',
      'diagnostico',
      'atencion',
      'insercion',
      'seguimiento',
      'baja',
    ], FaseTransitionManager::FASES);
  }

  /**
   * @covers ::getTransicionesValidas
   * @dataProvider transicionesValidasProvider
   */
  #[\PHPUnit\Framework\Attributes\Test]
  #[\PHPUnit\Framework\Attributes\DataProvider('transicionesValidasProvider')]
  public function transicionesValidasDesdeUnFase(string $fase, array $expected): void {
    $this->assertEquals($expected, $this->service->getTransicionesValidas($fase));
  }

  /**
   * Data provider para transiciones validas.
   */
  public static function transicionesValidasProvider(): array {
    return [
      'acogida permite diagnostico y baja' => ['acogida', ['diagnostico', 'baja']],
      'diagnostico permite atencion y baja' => ['diagnostico', ['atencion', 'baja']],
      'atencion permite insercion y baja' => ['atencion', ['insercion', 'baja']],
      'insercion permite seguimiento y baja' => ['insercion', ['seguimiento', 'baja']],
      'seguimiento solo permite baja' => ['seguimiento', ['baja']],
      'baja es absorbente' => ['baja', []],
      'fase inexistente devuelve vacio' => ['inexistente', []],
    ];
  }

  /**
   * @covers ::transitarFase
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function transitarFaseParticipanteNoEncontrado(): void {
    $this->storage->method('load')->with(999)->willReturn(NULL);

    $result = $this->service->transitarFase(999, 'diagnostico');

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('no encontrado', $result['message']->getUntranslatedString());
  }

  /**
   * @covers ::transitarFase
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function transitarFaseTransicionNoPermitida(): void {
    $participante = $this->createParticipanteMock('acogida');
    $this->storage->method('load')->with(1)->willReturn($participante);

    // Intentar saltar de acogida a insercion (no permitido).
    $result = $this->service->transitarFase(1, 'insercion');

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('no permitida', $result['message']->getUntranslatedString());
  }

  /**
   * @covers ::transitarFase
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function transitarFaseDesdeBajaNoPermitida(): void {
    $participante = $this->createParticipanteMock('baja');
    $this->storage->method('load')->with(1)->willReturn($participante);

    $result = $this->service->transitarFase(1, 'acogida');

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('no permitida', $result['message']->getUntranslatedString());
  }

  /**
   * Prerrequisito DACI: acogida -> diagnostico sin DACI firmado.
   *
   * @covers ::transitarFase
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function transitarADiagnosticoSinDaciFirmadoFalla(): void {
    $participante = $this->createParticipanteMock('acogida', [
      'isDaciFirmado' => FALSE,
    ]);
    $this->storage->method('load')->with(1)->willReturn($participante);

    $result = $this->service->transitarFase(1, 'diagnostico');

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('DACI', $result['message']->getUntranslatedString());
  }

  /**
   * Prerrequisito DACI: acogida -> diagnostico con DACI firmado pasa.
   *
   * @covers ::transitarFase
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function transitarADiagnosticoConDaciFirmadoExito(): void {
    $participante = $this->createParticipanteMock('acogida', [
      'isDaciFirmado' => TRUE,
    ]);
    $this->storage->method('load')->with(1)->willReturn($participante);

    $result = $this->service->transitarFase(1, 'diagnostico');

    $this->assertTrue($result['success']);
    $this->assertStringContainsString('completada', $result['message']->getUntranslatedString());
  }

  /**
   * Prerrequisito carril: diagnostico -> atencion sin carril falla.
   *
   * @covers ::transitarFase
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function transitarAAtencionSinCarrilFalla(): void {
    $participante = $this->createParticipanteMock('diagnostico', [
      'isDaciFirmado' => TRUE,
      'fields' => ['carril' => ''],
    ]);
    $this->storage->method('load')->with(1)->willReturn($participante);

    $result = $this->service->transitarFase(1, 'atencion');

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('carril', $result['message']->getUntranslatedString());
  }

  /**
   * Prerrequisito carril: diagnostico -> atencion con carril exito.
   *
   * @covers ::transitarFase
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function transitarAAtencionConCarrilExito(): void {
    $participante = $this->createParticipanteMock('diagnostico', [
      'isDaciFirmado' => TRUE,
      'fields' => ['carril' => 'carril_a'],
    ]);
    $this->storage->method('load')->with(1)->willReturn($participante);

    $result = $this->service->transitarFase(1, 'atencion');

    $this->assertTrue($result['success']);
  }

  /**
   * Prerrequisito horas: atencion -> insercion sin horas suficientes.
   *
   * @covers ::transitarFase
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function transitarAInsercionSinHorasFalla(): void {
    $participante = $this->createParticipanteMock('atencion', [
      'canTransitToInsercion' => FALSE,
    ]);
    $this->storage->method('load')->with(1)->willReturn($participante);

    $result = $this->service->transitarFase(1, 'insercion', ['tipo_insercion' => 'cuenta_ajena']);

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('10h', $result['message']->getUntranslatedString());
  }

  /**
   * Prerrequisito horas + tipo: atencion -> insercion sin tipo_insercion.
   *
   * @covers ::transitarFase
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function transitarAInsercionSinTipoInsercionFalla(): void {
    $participante = $this->createParticipanteMock('atencion', [
      'canTransitToInsercion' => TRUE,
    ]);
    $this->storage->method('load')->with(1)->willReturn($participante);

    $result = $this->service->transitarFase(1, 'insercion', []);

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('tipo de inserci', $result['message']->getUntranslatedString());
  }

  /**
   * Exito: atencion -> insercion con horas y tipo.
   *
   * @covers ::transitarFase
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function transitarAInsercionConRequisitosCumplidosExito(): void {
    $participante = $this->createParticipanteMock('atencion', [
      'canTransitToInsercion' => TRUE,
    ]);
    $this->storage->method('load')->with(1)->willReturn($participante);

    $result = $this->service->transitarFase(1, 'insercion', [
      'tipo_insercion' => 'cuenta_ajena',
    ]);

    $this->assertTrue($result['success']);
  }

  /**
   * Prerrequisito FSE+ salida: insercion -> seguimiento sin FSE+ completado.
   *
   * @covers ::transitarFase
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function transitarASeguimientoSinFseSalidaFalla(): void {
    $participante = $this->createParticipanteMock('insercion', [
      'fields' => ['fse_salida_completado' => FALSE],
    ]);
    $this->storage->method('load')->with(1)->willReturn($participante);

    $result = $this->service->transitarFase(1, 'seguimiento');

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('FSE+', $result['message']->getUntranslatedString());
  }

  /**
   * Exito: insercion -> seguimiento con FSE+ completado.
   *
   * @covers ::transitarFase
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function transitarASeguimientoConFseSalidaExito(): void {
    $participante = $this->createParticipanteMock('insercion', [
      'fields' => ['fse_salida_completado' => TRUE],
    ]);
    $this->storage->method('load')->with(1)->willReturn($participante);

    $result = $this->service->transitarFase(1, 'seguimiento');

    $this->assertTrue($result['success']);
  }

  /**
   * Prerrequisito baja: transitar a baja sin motivo falla.
   *
   * @covers ::transitarFase
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function transitarABajaSinMotivoFalla(): void {
    $participante = $this->createParticipanteMock('acogida');
    $this->storage->method('load')->with(1)->willReturn($participante);

    $result = $this->service->transitarFase(1, 'baja', []);

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('motivo de baja', $result['message']->getUntranslatedString());
  }

  /**
   * Exito: transitar a baja con motivo desde cualquier fase activa.
   *
   * @covers ::transitarFase
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function transitarABajaConMotivoExito(): void {
    $participante = $this->createParticipanteMock('atencion');
    $this->storage->method('load')->with(1)->willReturn($participante);

    $result = $this->service->transitarFase(1, 'baja', [
      'motivo_baja' => 'empleo_encontrado',
    ]);

    $this->assertTrue($result['success']);
  }

  /**
   * @covers ::canTransitToInsercion
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function canTransitToInsercionConHorasSuficientes(): void {
    // Usar stub SIN canTransitToInsercion() para forzar el fallback del servicio.
    $participante = $this->createFieldOnlyParticipanteMock([
      'horas_orientacion_ind' => 5.0,
      'horas_orientacion_grup' => 3.0,
      'horas_mentoria_ia' => 1.0,
      'horas_mentoria_humana' => 2.0,
      'horas_formacion' => 55.0,
    ]);

    $result = $this->service->canTransitToInsercion($participante);
    $this->assertTrue($result);
  }

  /**
   * @covers ::canTransitToInsercion
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function canTransitToInsercionConHorasInsuficientes(): void {
    $participante = $this->createFieldOnlyParticipanteMock([
      'horas_orientacion_ind' => 2.0,
      'horas_orientacion_grup' => 1.0,
      'horas_mentoria_ia' => 0.0,
      'horas_mentoria_humana' => 0.0,
      'horas_formacion' => 20.0,
    ]);

    $result = $this->service->canTransitToInsercion($participante);
    $this->assertFalse($result);
  }

  /**
   * @covers ::canTransitToInsercion
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function canTransitToInsercionConFormacionInsuficiente(): void {
    $participante = $this->createFieldOnlyParticipanteMock([
      'horas_orientacion_ind' => 10.0,
      'horas_orientacion_grup' => 5.0,
      'horas_mentoria_ia' => 0.0,
      'horas_mentoria_humana' => 0.0,
      'horas_formacion' => 40.0,
    ]);

    $result = $this->service->canTransitToInsercion($participante);
    $this->assertFalse($result);
  }

  /**
   * @covers ::canTransitToInsercion
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function canTransitToInsercionDelegaAlEntity(): void {
    // Participante CON canTransitToInsercion() -> delega al entity.
    $participante = $this->createParticipanteMock('atencion', [
      'canTransitToInsercion' => TRUE,
    ]);

    $result = $this->service->canTransitToInsercion($participante);
    $this->assertTrue($result);
  }

  /**
   * Crea un mock de participante con las opciones necesarias.
   *
   * Usa ContentEntityInterface para soportar hasField, get, set, save.
   * MOCK-METHOD-001: ContentEntityInterface proporciona get/set/save.
   * TEST-CACHE-001: Cache metadata implementado.
   *
   * @param string $faseActual
   *   Fase actual del participante.
   * @param array $options
   *   Opciones: isDaciFirmado, canTransitToInsercion, fields.
   */
  protected function createParticipanteMock(string $faseActual, array $options = []): object {
    $fields = $options['fields'] ?? [];

    // MOCK-DYNPROP-001: Clase anonima con typed properties.
    // Soporta getFaseActual, isDaciFirmado, canTransitToInsercion, get, set, save.
    $participante = new class($faseActual, $options, $fields) {
      /** @var array<string, mixed> */
      private array $fieldValues;

      /** @var array<string, mixed> */
      private array $setValues = [];

      public function __construct(
        private readonly string $faseActual,
        private readonly array $options,
        array $fields,
      ) {
        $this->fieldValues = $fields;
      }

      public function getFaseActual(): string {
        return $this->faseActual;
      }

      public function setFaseActual(string $fase): void {
        // No-op para test.
      }

      public function isDaciFirmado(): bool {
        return $this->options['isDaciFirmado'] ?? FALSE;
      }

      public function canTransitToInsercion(): bool {
        return $this->options['canTransitToInsercion'] ?? FALSE;
      }

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
        return 1;
      }

      public function id(): int {
        return 1;
      }

      /**
       * TEST-CACHE-001.
       */
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

    return $participante;
  }

  /**
   * Crea un stub que solo expone get() para campos.
   *
   * NO tiene canTransitToInsercion() para forzar el fallback
   * de calculo manual en FaseTransitionManager::canTransitToInsercion().
   */
  protected function createFieldOnlyParticipanteMock(array $fields): object {
    return new class($fields) {
      public function __construct(private readonly array $fieldValues) {}

      public function get(string $fieldName): object {
        $value = $this->fieldValues[$fieldName] ?? NULL;
        return new class($value) {
          public function __construct(public readonly mixed $value) {}
        };
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

}
