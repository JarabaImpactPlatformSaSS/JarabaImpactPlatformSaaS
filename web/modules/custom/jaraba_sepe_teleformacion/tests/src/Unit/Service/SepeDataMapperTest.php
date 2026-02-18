<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_sepe_teleformacion\Unit\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\jaraba_sepe_teleformacion\Service\SepeDataMapper;
use Drupal\jaraba_sepe_teleformacion\Service\SepeSeguimientoCalculator;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for SepeDataMapper service.
 *
 * Tests the mapping logic from Drupal entities to SEPE data format
 * for centros, acciones formativas, and participante seguimiento data.
 *
 * @coversDefaultClass \Drupal\jaraba_sepe_teleformacion\Service\SepeDataMapper
 * @group jaraba_sepe_teleformacion
 */
class SepeDataMapperTest extends UnitTestCase {

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock seguimiento calculator.
   */
  protected SepeSeguimientoCalculator $seguimientoCalculator;

  /**
   * The service under test.
   */
  protected SepeDataMapper $dataMapper;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->seguimientoCalculator = $this->createMock(SepeSeguimientoCalculator::class);

    $this->dataMapper = new SepeDataMapper(
      $this->entityTypeManager,
      $this->seguimientoCalculator,
    );
  }

  /**
   * Creates a mock entity field that returns the given value.
   *
   * @param mixed $value
   *   The value to return from the field.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface
   *   A mock field item list.
   */
  protected function createFieldMock(mixed $value): FieldItemListInterface {
    $field = $this->createMock(FieldItemListInterface::class);
    $field->method('__get')
      ->willReturnCallback(function (string $name) use ($value) {
        if ($name === 'value' || $name === 'target_id') {
          return $value;
        }
        return NULL;
      });
    $field->method('__isset')
      ->willReturnCallback(function (string $name) use ($value) {
        return ($name === 'value' || $name === 'target_id') && $value !== NULL;
      });
    return $field;
  }

  /**
   * Creates a mock content entity with configurable field values.
   *
   * @param array $fields
   *   Associative array of field_name => value pairs.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   A mock content entity.
   */
  protected function createEntityMock(array $fields): ContentEntityInterface {
    $entity = $this->createMock(ContentEntityInterface::class);

    $fieldMocks = [];
    foreach ($fields as $fieldName => $value) {
      $fieldMocks[$fieldName] = $this->createFieldMock($value);
    }

    $entity->method('get')
      ->willReturnCallback(function (string $name) use ($fieldMocks) {
        return $fieldMocks[$name] ?? $this->createFieldMock(NULL);
      });

    if (isset($fields['_id'])) {
      $entity->method('id')->willReturn($fields['_id']);
    }

    return $entity;
  }

  /**
   * Tests mapearDatosCentro returns empty array when centro not found.
   *
   * @covers ::mapearDatosCentro
   */
  public function testMapearDatosCentroReturnsEmptyWhenNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(999)->willReturn(NULL);

    $this->entityTypeManager->method('getStorage')
      ->with('sepe_centro')
      ->willReturn($storage);

    $result = $this->dataMapper->mapearDatosCentro(999);

    $this->assertSame([], $result);
  }

  /**
   * Tests mapearDatosCentro maps all required SEPE fields.
   *
   * @covers ::mapearDatosCentro
   */
  public function testMapearDatosCentroMapsAllFields(): void {
    $centro = $this->createEntityMock([
      'cif' => 'B12345678',
      'razon_social' => 'Centro Formativo SL',
      'codigo_sepe' => 'CF001',
      'direccion' => 'Calle Principal 1',
      'codigo_postal' => '28001',
      'municipio' => 'Madrid',
      'provincia' => 'Madrid',
      'telefono' => '912345678',
      'email' => 'centro@example.com',
      'url_plataforma' => 'https://centro.example.com',
    ]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(1)->willReturn($centro);

    $this->entityTypeManager->method('getStorage')
      ->with('sepe_centro')
      ->willReturn($storage);

    $result = $this->dataMapper->mapearDatosCentro(1);

    $this->assertSame('B12345678', $result['CIF']);
    $this->assertSame('Centro Formativo SL', $result['RazonSocial']);
    $this->assertSame('CF001', $result['CodigoCentro']);
    $this->assertSame('Calle Principal 1', $result['Direccion']);
    $this->assertSame('28001', $result['CodigoPostal']);
    $this->assertSame('Madrid', $result['Municipio']);
    $this->assertSame('Madrid', $result['Provincia']);
    $this->assertSame('912345678', $result['Telefono']);
    $this->assertSame('centro@example.com', $result['Email']);
    $this->assertSame('https://centro.example.com', $result['URLPlataforma']);
  }

  /**
   * Tests mapearDatosAccion returns empty array when accion not found.
   *
   * @covers ::mapearDatosAccion
   */
  public function testMapearDatosAccionReturnsEmptyWhenNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(999)->willReturn(NULL);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $entityType) use ($storage) {
        return $storage;
      });

    $result = $this->dataMapper->mapearDatosAccion(999);

    $this->assertSame([], $result);
  }

  /**
   * Tests mapearDatosAccion maps all fields and counts participants.
   *
   * @covers ::mapearDatosAccion
   */
  public function testMapearDatosAccionMapsFieldsAndCountsParticipants(): void {
    $accion = $this->createEntityMock([
      'id_accion_sepe' => 'AF2026-001',
      'codigo_especialidad' => 'IFCT0310',
      'denominacion' => 'Administracion de bases de datos',
      'modalidad' => 'T',
      'numero_horas' => '60',
      'fecha_inicio' => '2026-01-15',
      'fecha_fin' => '2026-03-15',
      'estado' => 'en_curso',
    ]);

    $accionStorage = $this->createMock(EntityStorageInterface::class);
    $accionStorage->method('load')->with(1)->willReturn($accion);

    // Three participants for this accion.
    $participanteStorage = $this->createMock(EntityStorageInterface::class);
    $participanteStorage->method('loadByProperties')
      ->with(['accion_id' => 1])
      ->willReturn([
        $this->createEntityMock([]),
        $this->createEntityMock([]),
        $this->createEntityMock([]),
      ]);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $entityType) use ($accionStorage, $participanteStorage) {
        return match ($entityType) {
          'sepe_accion_formativa' => $accionStorage,
          'sepe_participante' => $participanteStorage,
          default => $this->createMock(EntityStorageInterface::class),
        };
      });

    $result = $this->dataMapper->mapearDatosAccion(1);

    $this->assertSame('AF2026-001', $result['IdAccion']);
    $this->assertSame('IFCT0310', $result['CodigoEspecialidad']);
    $this->assertSame('Administracion de bases de datos', $result['Denominacion']);
    $this->assertSame('T', $result['Modalidad']);
    $this->assertSame(60, $result['NumeroHoras']);
    $this->assertSame('2026-01-15', $result['FechaInicio']);
    $this->assertSame('2026-03-15', $result['FechaFin']);
    $this->assertSame(3, $result['NumParticipantes']);
    $this->assertSame('E', $result['Estado']);
  }

  /**
   * Tests mapearDatosAccion maps estado values correctly.
   *
   * @covers ::mapearDatosAccion
   * @dataProvider estadoAccionProvider
   */
  public function testMapearDatosAccionMapsEstadoCorrectly(string $internalEstado, string $expectedSepeEstado): void {
    $accion = $this->createEntityMock([
      'id_accion_sepe' => 'AF001',
      'codigo_especialidad' => 'CODE',
      'denominacion' => 'Test',
      'modalidad' => 'T',
      'numero_horas' => '40',
      'fecha_inicio' => '2026-01-01',
      'fecha_fin' => '2026-02-01',
      'estado' => $internalEstado,
    ]);

    $accionStorage = $this->createMock(EntityStorageInterface::class);
    $accionStorage->method('load')->willReturn($accion);

    $participanteStorage = $this->createMock(EntityStorageInterface::class);
    $participanteStorage->method('loadByProperties')->willReturn([]);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $entityType) use ($accionStorage, $participanteStorage) {
        return match ($entityType) {
          'sepe_accion_formativa' => $accionStorage,
          'sepe_participante' => $participanteStorage,
          default => $this->createMock(EntityStorageInterface::class),
        };
      });

    $result = $this->dataMapper->mapearDatosAccion(1);

    $this->assertSame($expectedSepeEstado, $result['Estado']);
  }

  /**
   * Data provider for accion estado mapping.
   *
   * @return array
   *   Internal estado and expected SEPE estado code.
   */
  public static function estadoAccionProvider(): array {
    return [
      'pendiente maps to P' => ['pendiente', 'P'],
      'autorizada maps to P' => ['autorizada', 'P'],
      'en_curso maps to E' => ['en_curso', 'E'],
      'finalizada maps to F' => ['finalizada', 'F'],
      'cancelada maps to C' => ['cancelada', 'C'],
    ];
  }

  /**
   * Tests mapearDatosSeguimiento returns empty when participante not found.
   *
   * When actualizar is TRUE but participante does not exist after
   * the calculator runs, we expect an empty array.
   *
   * @covers ::mapearDatosSeguimiento
   */
  public function testMapearDatosSeguimientoReturnsEmptyWhenNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(999)->willReturn(NULL);

    $this->entityTypeManager->method('getStorage')
      ->with('sepe_participante')
      ->willReturn($storage);

    // Even with actualizar=TRUE, it should handle gracefully.
    $this->seguimientoCalculator->expects($this->once())
      ->method('actualizarSeguimientoParticipante')
      ->with(999);

    $result = $this->dataMapper->mapearDatosSeguimiento(999, TRUE);

    $this->assertSame([], $result);
  }

  /**
   * Tests mapearDatosSeguimiento skips calculator when actualizar is FALSE.
   *
   * @covers ::mapearDatosSeguimiento
   */
  public function testMapearDatosSeguimientoSkipsCalculatorWhenNotActualizar(): void {
    $participante = $this->createEntityMock([
      'dni' => '12345678A',
      'nombre' => 'Juan',
      'apellidos' => 'Garcia Lopez',
      'fecha_alta' => '2026-01-10',
      'fecha_baja' => NULL,
      'horas_conectado' => '15.5',
      'porcentaje_progreso' => '75',
      'num_actividades' => '12',
      'nota_media' => '8.5',
      'estado' => 'activo',
      'ultima_conexion' => '2026-02-15T10:30:00+01:00',
    ]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(1)->willReturn($participante);

    $this->entityTypeManager->method('getStorage')
      ->with('sepe_participante')
      ->willReturn($storage);

    $this->seguimientoCalculator->expects($this->never())
      ->method('actualizarSeguimientoParticipante');

    $result = $this->dataMapper->mapearDatosSeguimiento(1, FALSE);

    $this->assertSame('12345678A', $result['DNI']);
    $this->assertSame('Juan', $result['Nombre']);
    $this->assertSame('Garcia Lopez', $result['Apellidos']);
    $this->assertSame('2026-01-10', $result['FechaAlta']);
    $this->assertSame('', $result['FechaBaja']);
    $this->assertSame(15.5, $result['HorasConectado']);
    $this->assertSame(75, $result['PorcentajeProgreso']);
    $this->assertSame(12, $result['NumActividadesRealizadas']);
    $this->assertSame('A', $result['Estado']);
  }

  /**
   * Tests mapearDatosSeguimiento maps participante estado values correctly.
   *
   * @covers ::mapearDatosSeguimiento
   * @dataProvider estadoParticipanteProvider
   */
  public function testMapearDatosSeguimientoMapsEstadoCorrectly(string $internalEstado, string $expectedSepeEstado): void {
    $participante = $this->createEntityMock([
      'dni' => '12345678A',
      'nombre' => 'Test',
      'apellidos' => 'User',
      'fecha_alta' => '2026-01-01',
      'fecha_baja' => NULL,
      'horas_conectado' => '0',
      'porcentaje_progreso' => '0',
      'num_actividades' => '0',
      'nota_media' => NULL,
      'estado' => $internalEstado,
      'ultima_conexion' => NULL,
    ]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->willReturn($participante);

    $this->entityTypeManager->method('getStorage')
      ->with('sepe_participante')
      ->willReturn($storage);

    $result = $this->dataMapper->mapearDatosSeguimiento(1, FALSE);

    $this->assertSame($expectedSepeEstado, $result['Estado']);
  }

  /**
   * Data provider for participante estado mapping.
   *
   * @return array
   *   Internal estado and expected SEPE estado code.
   */
  public static function estadoParticipanteProvider(): array {
    return [
      'activo maps to A' => ['activo', 'A'],
      'baja maps to B' => ['baja', 'B'],
      'finalizado maps to F' => ['finalizado', 'F'],
      'certificado maps to C' => ['certificado', 'C'],
    ];
  }

  /**
   * Tests obtenerListaAcciones returns SEPE IDs for a centro.
   *
   * @covers ::obtenerListaAcciones
   */
  public function testObtenerListaAccionesReturnsSEPEIds(): void {
    $accion1 = $this->createEntityMock(['id_accion_sepe' => 'AF2026-001']);
    $accion2 = $this->createEntityMock(['id_accion_sepe' => 'AF2026-002']);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')
      ->with(['centro_id' => 1])
      ->willReturn([$accion1, $accion2]);

    $this->entityTypeManager->method('getStorage')
      ->with('sepe_accion_formativa')
      ->willReturn($storage);

    $result = $this->dataMapper->obtenerListaAcciones(1);

    $this->assertCount(2, $result);
    $this->assertSame('AF2026-001', $result[0]);
    $this->assertSame('AF2026-002', $result[1]);
  }

  /**
   * Tests obtenerListaAcciones returns empty array when no acciones.
   *
   * @covers ::obtenerListaAcciones
   */
  public function testObtenerListaAccionesReturnsEmptyWhenNoAcciones(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')->willReturn([]);

    $this->entityTypeManager->method('getStorage')
      ->with('sepe_accion_formativa')
      ->willReturn($storage);

    $result = $this->dataMapper->obtenerListaAcciones(999);

    $this->assertSame([], $result);
  }

  /**
   * Tests obtenerParticipantesAccion returns empty when accion not found.
   *
   * @covers ::obtenerParticipantesAccion
   */
  public function testObtenerParticipantesAccionReturnsEmptyWhenAccionNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')
      ->with(['id_accion_sepe' => 'NONEXISTENT'])
      ->willReturn([]);

    $this->entityTypeManager->method('getStorage')
      ->with('sepe_accion_formativa')
      ->willReturn($storage);

    $result = $this->dataMapper->obtenerParticipantesAccion('NONEXISTENT');

    $this->assertSame([], $result);
  }

}
