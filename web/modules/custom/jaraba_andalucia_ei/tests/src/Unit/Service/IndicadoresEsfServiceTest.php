<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Select;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_andalucia_ei\Service\IndicadoresEsfService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para IndicadoresEsfService.
 *
 * Verifica la estructura de KPIs globales, constantes de indicadores ESF+,
 * y calculos basicos del servicio de indicadores.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\IndicadoresEsfService
 * @group jaraba_andalucia_ei
 */
class IndicadoresEsfServiceTest extends UnitTestCase {

  /**
   * El servicio bajo test.
   */
  protected IndicadoresEsfService $service;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock database connection.
   */
  protected Connection $database;

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
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->database = $this->createMock(Connection::class);

    $this->service = new IndicadoresEsfService(
      $this->entityTypeManager,
      $this->logger,
      $this->database,
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
   * Helper para crear un mock de Select con chaining fluido.
   *
   * @param mixed $fetchFieldResult
   *   Resultado que devolvera fetchField().
   *
   * @return \Drupal\Core\Database\Query\Select
   *   Mock del select.
   */
  private function createFluentSelect(mixed $fetchFieldResult): Select {
    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchField')->willReturn($fetchFieldResult);

    $select = $this->createMock(Select::class);
    $select->method('condition')->willReturnSelf();
    // QUERY-CHAIN-001: addExpression devuelve alias string, NO $this.
    $select->method('addExpression')->willReturn('expression_alias');
    $select->method('execute')->willReturn($statement);

    return $select;
  }

  /**
   * @covers ::getKpisGlobales
   */
  public function testGetKpisGlobalesReturnsExpectedStructure(): void {
    // Mock storage para programa_participante_ei.
    $participanteStorage = $this->createMock(EntityStorageInterface::class);

    // Todas las queries de conteo devuelven 0.
    $queryZero = $this->createFluentQuery(0);
    $participanteStorage->method('getQuery')->willReturn($queryZero);

    // Mock storage para insercion_laboral.
    $insercionStorage = $this->createMock(EntityStorageInterface::class);
    $insercionQuery = $this->createFluentQuery(0);
    $insercionStorage->method('getQuery')->willReturn($insercionQuery);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $entityType) use ($participanteStorage, $insercionStorage) {
        return match ($entityType) {
          'programa_participante_ei' => $participanteStorage,
          'insercion_laboral' => $insercionStorage,
          default => $this->createMock(EntityStorageInterface::class),
        };
      });

    // Mock database selects para tasa_asistencia y horas_totales.
    $selectZero = $this->createFluentSelect(0);
    $this->database->method('select')->willReturn($selectZero);

    $result = $this->service->getKpisGlobales();

    // Verificar estructura de las claves esperadas.
    $this->assertArrayHasKey('total_participantes', $result);
    $this->assertArrayHasKey('por_fase', $result);
    $this->assertArrayHasKey('tasa_insercion', $result);
    $this->assertArrayHasKey('tasa_asistencia', $result);
    $this->assertArrayHasKey('horas_totales', $result);

    // Verificar tipos.
    $this->assertIsInt($result['total_participantes']);
    $this->assertIsArray($result['por_fase']);
    $this->assertIsFloat($result['tasa_insercion']);
    $this->assertIsFloat($result['tasa_asistencia']);
    $this->assertIsFloat($result['horas_totales']);

    // Verificar que por_fase contiene las 7 fases del programa.
    $expectedFases = ['acogida', 'diagnostico', 'atencion', 'insercion', 'seguimiento', 'alumni', 'baja'];
    foreach ($expectedFases as $fase) {
      $this->assertArrayHasKey($fase, $result['por_fase'], "Falta la fase '$fase' en por_fase.");
    }
    $this->assertCount(7, $result['por_fase'], 'por_fase debe tener exactamente 7 fases.');
  }

  /**
   * @covers ::getKpisGlobales
   */
  public function testGetKpisGlobalesZeroParticipantesReturnsZeroRates(): void {
    $participanteStorage = $this->createMock(EntityStorageInterface::class);
    $queryZero = $this->createFluentQuery(0);
    $participanteStorage->method('getQuery')->willReturn($queryZero);

    $insercionStorage = $this->createMock(EntityStorageInterface::class);
    $insercionQuery = $this->createFluentQuery(0);
    $insercionStorage->method('getQuery')->willReturn($insercionQuery);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $entityType) use ($participanteStorage, $insercionStorage) {
        return match ($entityType) {
          'programa_participante_ei' => $participanteStorage,
          'insercion_laboral' => $insercionStorage,
          default => $this->createMock(EntityStorageInterface::class),
        };
      });

    $selectZero = $this->createFluentSelect(0);
    $this->database->method('select')->willReturn($selectZero);

    $result = $this->service->getKpisGlobales();

    $this->assertSame(0, $result['total_participantes']);
    $this->assertSame(0.0, $result['tasa_insercion']);
    $this->assertSame(0.0, $result['tasa_asistencia']);
  }

  /**
   * Verifica que OUTPUT_INDICATORS tiene exactamente 14 entradas (CO01-CO14).
   *
   * @covers \Drupal\jaraba_andalucia_ei\Service\IndicadoresEsfService
   */
  public function testOutputIndicatorsHas14Entries(): void {
    $reflection = new \ReflectionClass(IndicadoresEsfService::class);
    $constant = $reflection->getConstant('OUTPUT_INDICATORS');

    $this->assertIsArray($constant);
    $this->assertCount(14, $constant, 'OUTPUT_INDICATORS debe tener exactamente 14 indicadores ESF+.');

    // Verificar que contiene los codigos CO01 a CO14.
    for ($i = 1; $i <= 14; $i++) {
      $codigo = sprintf('CO%02d', $i);
      $this->assertArrayHasKey($codigo, $constant, "Falta el indicador $codigo en OUTPUT_INDICATORS.");
    }

    // Verificar que cada indicador tiene las claves requeridas.
    foreach ($constant as $codigo => $indicator) {
      $this->assertArrayHasKey('label', $indicator, "Indicador $codigo sin 'label'.");
      $this->assertArrayHasKey('campo', $indicator, "Indicador $codigo sin 'campo'.");
      $this->assertArrayHasKey('condicion', $indicator, "Indicador $codigo sin 'condicion'.");
    }
  }

  /**
   * Verifica que RESULT_INDICATORS tiene exactamente 6 entradas (CR01-CR06).
   *
   * @covers \Drupal\jaraba_andalucia_ei\Service\IndicadoresEsfService
   */
  public function testResultIndicatorsHas6Entries(): void {
    $reflection = new \ReflectionClass(IndicadoresEsfService::class);
    $constant = $reflection->getConstant('RESULT_INDICATORS');

    $this->assertIsArray($constant);
    $this->assertCount(6, $constant, 'RESULT_INDICATORS debe tener exactamente 6 indicadores ESF+.');

    // Verificar que contiene los codigos CR01 a CR06.
    for ($i = 1; $i <= 6; $i++) {
      $codigo = sprintf('CR%02d', $i);
      $this->assertArrayHasKey($codigo, $constant, "Falta el indicador $codigo en RESULT_INDICATORS.");
    }

    // Verificar que cada indicador tiene las claves requeridas.
    foreach ($constant as $codigo => $indicator) {
      $this->assertArrayHasKey('label', $indicator, "Indicador $codigo sin 'label'.");
      $this->assertArrayHasKey('tipo', $indicator, "Indicador $codigo sin 'tipo'.");
    }
  }

  /**
   * Verifica que los labels de OUTPUT_INDICATORS son strings no vacios.
   *
   * @covers \Drupal\jaraba_andalucia_ei\Service\IndicadoresEsfService
   */
  public function testOutputIndicatorsLabelsNotEmpty(): void {
    $reflection = new \ReflectionClass(IndicadoresEsfService::class);
    $constant = $reflection->getConstant('OUTPUT_INDICATORS');

    foreach ($constant as $codigo => $indicator) {
      $this->assertNotEmpty($indicator['label'], "Label vacio para indicador $codigo.");
      $this->assertIsString($indicator['label'], "Label no es string para indicador $codigo.");
    }
  }

  /**
   * @covers ::getIndicadoresOutput
   */
  public function testGetIndicadoresOutputReturnsZerosWhenNoParticipants(): void {
    $participanteStorage = $this->createMock(EntityStorageInterface::class);
    $queryZero = $this->createFluentQuery(0);
    $participanteStorage->method('getQuery')->willReturn($queryZero);

    $this->entityTypeManager->method('getStorage')
      ->willReturn($participanteStorage);

    $result = $this->service->getIndicadoresOutput();

    $this->assertCount(14, $result, 'Debe devolver 14 indicadores incluso con 0 participantes.');

    foreach ($result as $codigo => $data) {
      $this->assertArrayHasKey('label', $data);
      $this->assertArrayHasKey('total', $data);
      $this->assertArrayHasKey('porcentaje', $data);
      $this->assertSame(0, $data['total'], "Indicador $codigo debe tener total 0.");
      $this->assertSame(0.0, $data['porcentaje'], "Indicador $codigo debe tener porcentaje 0.0.");
    }
  }

  /**
   * @covers ::getIndicadoresResultado
   */
  public function testGetIndicadoresResultadoReturnsZerosWhenNoFinalizados(): void {
    $participanteStorage = $this->createMock(EntityStorageInterface::class);
    $queryZero = $this->createFluentQuery(0);
    $participanteStorage->method('getQuery')->willReturn($queryZero);

    $this->entityTypeManager->method('getStorage')
      ->willReturn($participanteStorage);

    $result = $this->service->getIndicadoresResultado();

    $this->assertCount(6, $result, 'Debe devolver 6 indicadores de resultado incluso con 0 finalizados.');

    foreach ($result as $codigo => $data) {
      $this->assertArrayHasKey('label', $data);
      $this->assertArrayHasKey('total', $data);
      $this->assertArrayHasKey('porcentaje', $data);
      $this->assertSame(0, $data['total']);
      $this->assertSame(0.0, $data['porcentaje']);
    }
  }

}
