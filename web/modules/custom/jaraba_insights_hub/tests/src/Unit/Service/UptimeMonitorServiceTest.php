<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_insights_hub\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\jaraba_insights_hub\Service\UptimeMonitorService;
use GuzzleHttp\ClientInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests unitarios para UptimeMonitorService.
 *
 * Verifica la logica de calculo de uptime, ejecucion de checks
 * individuales y obtencion del estado actual de endpoints.
 *
 * @coversDefaultClass \Drupal\jaraba_insights_hub\Service\UptimeMonitorService
 * @group jaraba_insights_hub
 */
class UptimeMonitorServiceTest extends TestCase {

  /**
   * El servicio bajo prueba.
   *
   * @var \Drupal\jaraba_insights_hub\Service\UptimeMonitorService
   */
  protected UptimeMonitorService $service;

  /**
   * Mock del cliente HTTP.
   *
   * @var \GuzzleHttp\ClientInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected ClientInterface|MockObject $httpClient;

  /**
   * Mock del gestor de tipos de entidad.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityTypeManagerInterface|MockObject $entityTypeManager;

  /**
   * Mock del servicio State.
   *
   * @var \Drupal\Core\State\StateInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected StateInterface|MockObject $state;

  /**
   * Mock del canal de log.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected LoggerInterface|MockObject $logger;

  /**
   * Mock del storage de checks de uptime.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityStorageInterface|MockObject $checkStorage;

  /**
   * Mock del storage de incidentes de uptime.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityStorageInterface|MockObject $incidentStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->httpClient = $this->createMock(ClientInterface::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->state = $this->createMock(StateInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->checkStorage = $this->createMock(EntityStorageInterface::class);
    $this->incidentStorage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager
      ->method('getStorage')
      ->willReturnMap([
        ['uptime_check', $this->checkStorage],
        ['uptime_incident', $this->incidentStorage],
      ]);

    $this->service = new UptimeMonitorService(
      $this->httpClient,
      $this->entityTypeManager,
      $this->state,
      $this->logger,
    );
  }

  /**
   * Configura un query mock que devuelve los IDs especificados.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject $storage
   *   El mock de storage al que asociar el query.
   * @param array $ids
   *   Los IDs que devolvera el query.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface|\PHPUnit\Framework\MockObject\MockObject
   *   El mock de query configurado.
   */
  protected function setupQuery(EntityStorageInterface|MockObject $storage, array $ids): QueryInterface|MockObject {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    $query->method('execute')->willReturn($ids);

    $storage
      ->method('getQuery')
      ->willReturn($query);

    return $query;
  }

  /**
   * Verifica que calculateUptime devuelve 100.0 cuando todos los checks estan up.
   *
   * The service signature is calculateUptime(int $tenantId, int $days = 30).
   * It uses count() on arrays returned by execute(), not count queries.
   *
   * @covers ::calculateUptime
   */
  public function testCalculateUptimeAllUp(): void {
    // Total checks query: returns array of 100 IDs.
    $totalIds = range(1, 100);
    $totalQuery = $this->createMock(QueryInterface::class);
    $totalQuery->method('accessCheck')->willReturnSelf();
    $totalQuery->method('condition')->willReturnSelf();
    $totalQuery->method('execute')->willReturn($totalIds);

    // Up checks query: returns array of 100 IDs (all up).
    $upQuery = $this->createMock(QueryInterface::class);
    $upQuery->method('accessCheck')->willReturnSelf();
    $upQuery->method('condition')->willReturnSelf();
    $upQuery->method('execute')->willReturn($totalIds);

    $this->checkStorage
      ->method('getQuery')
      ->willReturnOnConsecutiveCalls($totalQuery, $upQuery);

    $uptime = $this->service->calculateUptime(1, 30);

    $this->assertIsFloat($uptime);
    $this->assertEquals(100.0, $uptime);
  }

  /**
   * Verifica que calculateUptime devuelve 100.0 cuando no hay checks.
   *
   * The service returns 100.0 when there are 0 total checks (line 331-332).
   *
   * @covers ::calculateUptime
   */
  public function testCalculateUptimeNoChecks(): void {
    $totalQuery = $this->createMock(QueryInterface::class);
    $totalQuery->method('accessCheck')->willReturnSelf();
    $totalQuery->method('condition')->willReturnSelf();
    $totalQuery->method('execute')->willReturn([]);

    $this->checkStorage
      ->method('getQuery')
      ->willReturn($totalQuery);

    $uptime = $this->service->calculateUptime(1, 30);

    $this->assertIsFloat($uptime);
    // Service returns 100.0 when there are no checks (assumes up).
    $this->assertEquals(100.0, $uptime);
  }

  /**
   * Verifica que calculateUptime devuelve porcentaje correcto con downtime.
   *
   * @covers ::calculateUptime
   */
  public function testCalculateUptimePartialDowntime(): void {
    // 100 total checks.
    $totalIds = range(1, 100);
    $totalQuery = $this->createMock(QueryInterface::class);
    $totalQuery->method('accessCheck')->willReturnSelf();
    $totalQuery->method('condition')->willReturnSelf();
    $totalQuery->method('execute')->willReturn($totalIds);

    // 95 of 100 checks are up = 95% uptime.
    $upIds = range(1, 95);
    $upQuery = $this->createMock(QueryInterface::class);
    $upQuery->method('accessCheck')->willReturnSelf();
    $upQuery->method('condition')->willReturnSelf();
    $upQuery->method('execute')->willReturn($upIds);

    $this->checkStorage
      ->method('getQuery')
      ->willReturnOnConsecutiveCalls($totalQuery, $upQuery);

    $uptime = $this->service->calculateUptime(1, 30);

    $this->assertIsFloat($uptime);
    $this->assertEquals(95.0, $uptime);
  }

  /**
   * Verifica que calculateUptime devuelve 0.0 cuando todos los checks estan down.
   *
   * @covers ::calculateUptime
   */
  public function testCalculateUptimeAllDown(): void {
    // 50 total checks.
    $totalIds = range(1, 50);
    $totalQuery = $this->createMock(QueryInterface::class);
    $totalQuery->method('accessCheck')->willReturnSelf();
    $totalQuery->method('condition')->willReturnSelf();
    $totalQuery->method('execute')->willReturn($totalIds);

    // 0 up checks.
    $upQuery = $this->createMock(QueryInterface::class);
    $upQuery->method('accessCheck')->willReturnSelf();
    $upQuery->method('condition')->willReturnSelf();
    $upQuery->method('execute')->willReturn([]);

    $this->checkStorage
      ->method('getQuery')
      ->willReturnOnConsecutiveCalls($totalQuery, $upQuery);

    $uptime = $this->service->calculateUptime(1, 30);

    $this->assertIsFloat($uptime);
    $this->assertEquals(0.0, $uptime);
  }

  /**
   * Verifica que getChecksForTenant devuelve array vacio sin checks.
   *
   * @covers ::getChecksForTenant
   */
  public function testGetChecksForTenantEmpty(): void {
    $this->setupQuery($this->checkStorage, []);

    $result = $this->service->getChecksForTenant(1);

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * Verifica que getActiveIncidents devuelve array vacio sin incidentes.
   *
   * @covers ::getActiveIncidents
   */
  public function testGetActiveIncidentsEmpty(): void {
    $this->setupQuery($this->incidentStorage, []);

    $result = $this->service->getActiveIncidents(1);

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

}
