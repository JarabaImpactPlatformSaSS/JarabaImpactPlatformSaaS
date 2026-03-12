<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_andalucia_ei\Entity\ProgramaParticipanteEiInterface;
use Drupal\jaraba_andalucia_ei\Service\StoBidireccionalService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para StoBidireccionalService.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\StoBidireccionalService
 * @group jaraba_andalucia_ei
 */
class StoBidireccionalServiceTest extends UnitTestCase {

  protected StoBidireccionalService $service;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected EntityStorageInterface $participanteStorage;
  protected LoggerInterface $logger;
  protected ConfigFactoryInterface $configFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->participanteStorage = $this->createMock(EntityStorageInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->with('programa_participante_ei')
      ->willReturn($this->participanteStorage);

    $config = $this->createMock(Config::class);
    $config->method('get')->with('sto_api_endpoint')->willReturn('');
    $this->configFactory->method('get')
      ->with('jaraba_andalucia_ei.settings')
      ->willReturn($config);

    // Sin StoExportService (parámetro opcional).
    $this->service = new StoBidireccionalService(
      $this->entityTypeManager,
      $this->logger,
      $this->configFactory,
      NULL,
    );
  }

  /**
   * getResumenSync devuelve las 5 claves y los totales son aritméticament
   * coherentes (sin_estado = total - pending - synced - error).
   *
   * @covers ::getResumenSync
   */
  public function testGetResumenSyncDevuelveEstructuraCoherente(): void {
    // total=10, pending=3, synced=5, error=1 → sin_estado=1.
    $queryTotal = $this->createCountQueryMock(10);
    $queryPending = $this->createCountQueryMock(3);
    $querySynced = $this->createCountQueryMock(5);
    $queryError = $this->createCountQueryMock(1);

    $this->participanteStorage->method('getQuery')
      ->willReturnOnConsecutiveCalls(
        $queryTotal,
        $queryPending,
        $querySynced,
        $queryError,
      );

    $resumen = $this->service->getResumenSync(42);

    $this->assertArrayHasKey('total', $resumen);
    $this->assertArrayHasKey('pending', $resumen);
    $this->assertArrayHasKey('synced', $resumen);
    $this->assertArrayHasKey('error', $resumen);
    $this->assertArrayHasKey('sin_estado', $resumen);

    $this->assertEquals(10, $resumen['total']);
    $this->assertEquals(3, $resumen['pending']);
    $this->assertEquals(5, $resumen['synced']);
    $this->assertEquals(1, $resumen['error']);
    $this->assertEquals(1, $resumen['sin_estado']);
    // Invariante: total = pending + synced + error + sin_estado.
    $this->assertEquals(
      $resumen['total'],
      $resumen['pending'] + $resumen['synced'] + $resumen['error'] + $resumen['sin_estado'],
    );
  }

  /**
   * getResumenSync devuelve ceros cuando no hay participantes.
   *
   * @covers ::getResumenSync
   */
  public function testGetResumenSyncSinParticipantesDevuelveCeros(): void {
    $this->participanteStorage->method('getQuery')
      ->willReturnCallback(fn() => $this->createCountQueryMock(0));

    $resumen = $this->service->getResumenSync(99);

    $this->assertEquals(0, $resumen['total']);
    $this->assertEquals(0, $resumen['pending']);
    $this->assertEquals(0, $resumen['synced']);
    $this->assertEquals(0, $resumen['error']);
    $this->assertEquals(0, $resumen['sin_estado']);
  }

  /**
   * reconciliar detecta participantes modificados después de la última sync
   * y los marca 'pending'.
   *
   * @covers ::reconciliar
   */
  public function testReconciliarDetectaCambiosPostSync(): void {
    $lastSync = strtotime('2026-03-01 10:00:00');
    $changed = strtotime('2026-03-05 12:00:00'); // Posterior a lastSync.

    $participante = $this->createParticipanteMockConSync(
      id: 5,
      syncStatus: 'synced',
      lastSync: $lastSync,
      changed: $changed,
    );

    // Primera llamada: getQuery para obtener IDs.
    $queryIds = $this->createFilterQueryMock([5]);
    $this->participanteStorage->method('getQuery')->willReturn($queryIds);
    $this->participanteStorage->method('loadMultiple')
      ->with([5])
      ->willReturn([5 => $participante]);

    // El participante tiene el campo sto_sync_status y debe ser guardado.
    $participante->expects($this->once())->method('set')
      ->with('sto_sync_status', 'pending');
    $participante->expects($this->once())->method('save');

    $result = $this->service->reconciliar(7);

    $this->assertEquals(1, $result['total']);
    $this->assertEquals(1, $result['necesitan_resync']);
    $this->assertCount(1, $result['discrepancias']);
    $this->assertEquals('datos_modificados_post_sync', $result['discrepancias'][0]['tipo']);
    $this->assertEquals(5, $result['discrepancias'][0]['id']);
  }

  /**
   * reconciliar no genera discrepancias si changed <= lastSync.
   *
   * @covers ::reconciliar
   */
  public function testReconciliarSinCambiosNoCreaDiscrepancias(): void {
    $lastSync = strtotime('2026-03-10 10:00:00');
    $changed = strtotime('2026-03-08 09:00:00'); // Anterior a lastSync.

    $participante = $this->createParticipanteMockConSync(
      id: 6,
      syncStatus: 'synced',
      lastSync: $lastSync,
      changed: $changed,
    );

    $queryIds = $this->createFilterQueryMock([6]);
    $this->participanteStorage->method('getQuery')->willReturn($queryIds);
    $this->participanteStorage->method('loadMultiple')
      ->with([6])
      ->willReturn([6 => $participante]);

    // No debe guardarse porque no hay discrepancia.
    $participante->expects($this->never())->method('save');

    $result = $this->service->reconciliar(8);

    $this->assertEquals(1, $result['total']);
    $this->assertEquals(0, $result['necesitan_resync']);
    $this->assertCount(0, $result['discrepancias']);
  }

  /**
   * reconciliar con lista vacía retorna estructura vacía sin errores.
   *
   * @covers ::reconciliar
   */
  public function testReconciliarSinParticipantesRetornaEstructuraVacia(): void {
    $query = $this->createFilterQueryMock([]);
    $this->participanteStorage->method('getQuery')->willReturn($query);

    $result = $this->service->reconciliar(1);

    $this->assertEquals(0, $result['total']);
    $this->assertEquals(0, $result['necesitan_resync']);
    $this->assertEmpty($result['discrepancias']);
  }

  /**
   * pushPendientes devuelve fallo cuando no hay StoExportService.
   *
   * @covers ::pushPendientes
   */
  public function testPushPendientesSinStoExportServiceDevuelveFallo(): void {
    $result = $this->service->pushPendientes(1);

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('StoExportService', $result['message']);
  }

  /**
   * Crea un mock de participante con campos sto_sync_status, sto_last_sync y
   * getChangedTime. Usa ProgramaParticipanteEiInterface (MOCK-METHOD-001) para
   * tener acceso a getChangedTime(). Clases anónimas con typed props
   * para los FieldItemList (MOCK-DYNPROP-001).
   */
  protected function createParticipanteMockConSync(
    int $id,
    string $syncStatus,
    int $lastSync,
    int $changed,
  ): ProgramaParticipanteEiInterface {
    $participante = $this->createMock(ProgramaParticipanteEiInterface::class);
    $participante->method('id')->willReturn($id);
    $participante->method('label')->willReturn('Participante ' . $id);
    $participante->method('getChangedTime')->willReturn($changed);

    // TEST-CACHE-001.
    $participante->method('getCacheContexts')->willReturn([]);
    $participante->method('getCacheTags')->willReturn(['programa_participante_ei:' . $id]);
    $participante->method('getCacheMaxAge')->willReturn(-1);

    // Usar clase anónima simple con typed property (MOCK-DYNPROP-001).
    // No implementar FieldItemListInterface: PHP 8.4 exige Iterator/IteratorAggregate
    // para Traversable, y el servicio solo lee ->value e isEmpty().
    $syncField = new class($syncStatus) {
      public string $value;

      public function __construct(string $v) {
        $this->value = $v;
      }

      public function isEmpty(): bool {
        return FALSE;
      }
    };

    $lastSyncField = new class($lastSync) {
      public int $value;

      public function __construct(int $v) {
        $this->value = $v;
      }

      public function isEmpty(): bool {
        return $this->value === 0;
      }
    };

    $participante->method('hasField')
      ->willReturnMap([
        ['sto_sync_status', TRUE],
        ['sto_last_sync', TRUE],
      ]);

    $participante->method('get')
      ->willReturnMap([
        ['sto_sync_status', $syncField],
        ['sto_last_sync', $lastSyncField],
      ]);

    return $participante;
  }

  /**
   * Crea un mock de query de conteo.
   */
  protected function createCountQueryMock(int $result): QueryInterface {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    $query->method('execute')->willReturn($result);
    return $query;
  }

  /**
   * Crea un mock de query que devuelve array de IDs.
   */
  protected function createFilterQueryMock(array $ids): QueryInterface {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn($ids);
    return $query;
  }

}
