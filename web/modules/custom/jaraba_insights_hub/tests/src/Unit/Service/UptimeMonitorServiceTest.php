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
   * @covers ::calculateUptime
   */
  public function testCalculateUptimeAllUp(): void {
    // Total checks query.
    $totalQuery = $this->createMock(QueryInterface::class);
    $totalQuery->method('accessCheck')->willReturnSelf();
    $totalQuery->method('condition')->willReturnSelf();
    $totalQuery->method('count')->willReturnSelf();
    $totalQuery->method('execute')->willReturn(100);

    // Up checks query.
    $upQuery = $this->createMock(QueryInterface::class);
    $upQuery->method('accessCheck')->willReturnSelf();
    $upQuery->method('condition')->willReturnSelf();
    $upQuery->method('count')->willReturnSelf();
    $upQuery->method('execute')->willReturn(100);

    $this->checkStorage
      ->method('getQuery')
      ->willReturnOnConsecutiveCalls($totalQuery, $upQuery);

    $uptime = $this->service->calculateUptime(1, 'https://example.com');

    $this->assertIsFloat($uptime);
    $this->assertEquals(100.0, $uptime);
  }

  /**
   * Verifica que calculateUptime devuelve 0.0 cuando no hay checks.
   *
   * @covers ::calculateUptime
   */
  public function testCalculateUptimeNoChecks(): void {
    $totalQuery = $this->createMock(QueryInterface::class);
    $totalQuery->method('accessCheck')->willReturnSelf();
    $totalQuery->method('condition')->willReturnSelf();
    $totalQuery->method('count')->willReturnSelf();
    $totalQuery->method('execute')->willReturn(0);

    $upQuery = $this->createMock(QueryInterface::class);
    $upQuery->method('accessCheck')->willReturnSelf();
    $upQuery->method('condition')->willReturnSelf();
    $upQuery->method('count')->willReturnSelf();
    $upQuery->method('execute')->willReturn(0);

    $this->checkStorage
      ->method('getQuery')
      ->willReturnOnConsecutiveCalls($totalQuery, $upQuery);

    $uptime = $this->service->calculateUptime(1, 'https://example.com');

    $this->assertIsFloat($uptime);
    $this->assertEquals(0.0, $uptime);
  }

  /**
   * Verifica que calculateUptime devuelve porcentaje correcto con downtime.
   *
   * @covers ::calculateUptime
   */
  public function testCalculateUptimePartialDowntime(): void {
    // 95 of 100 checks are up = 95% uptime.
    $totalQuery = $this->createMock(QueryInterface::class);
    $totalQuery->method('accessCheck')->willReturnSelf();
    $totalQuery->method('condition')->willReturnSelf();
    $totalQuery->method('count')->willReturnSelf();
    $totalQuery->method('execute')->willReturn(100);

    $upQuery = $this->createMock(QueryInterface::class);
    $upQuery->method('accessCheck')->willReturnSelf();
    $upQuery->method('condition')->willReturnSelf();
    $upQuery->method('count')->willReturnSelf();
    $upQuery->method('execute')->willReturn(95);

    $this->checkStorage
      ->method('getQuery')
      ->willReturnOnConsecutiveCalls($totalQuery, $upQuery);

    $uptime = $this->service->calculateUptime(1, 'https://example.com');

    $this->assertIsFloat($uptime);
    $this->assertEquals(95.0, $uptime);
  }

  /**
   * Verifica que calculateUptime devuelve 0.0 cuando todos los checks estan down.
   *
   * @covers ::calculateUptime
   */
  public function testCalculateUptimeAllDown(): void {
    $totalQuery = $this->createMock(QueryInterface::class);
    $totalQuery->method('accessCheck')->willReturnSelf();
    $totalQuery->method('condition')->willReturnSelf();
    $totalQuery->method('count')->willReturnSelf();
    $totalQuery->method('execute')->willReturn(50);

    $upQuery = $this->createMock(QueryInterface::class);
    $upQuery->method('accessCheck')->willReturnSelf();
    $upQuery->method('condition')->willReturnSelf();
    $upQuery->method('count')->willReturnSelf();
    $upQuery->method('execute')->willReturn(0);

    $this->checkStorage
      ->method('getQuery')
      ->willReturnOnConsecutiveCalls($totalQuery, $upQuery);

    $uptime = $this->service->calculateUptime(1, 'https://example.com');

    $this->assertIsFloat($uptime);
    $this->assertEquals(0.0, $uptime);
  }

  /**
   * Verifica que getEndpointStatus devuelve array vacio sin checks.
   *
   * @covers ::getEndpointStatus
   */
  public function testGetEndpointStatusEmpty(): void {
    $this->setupQuery($this->checkStorage, []);

    $result = $this->service->getEndpointStatus(1);

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

  /**
   * Verifica que getUptimeSummary devuelve estructura correcta sin datos.
   *
   * @covers ::getUptimeSummary
   */
  public function testGetUptimeSummaryEmpty(): void {
    // First query: get all check IDs to find unique endpoints.
    $endpointQuery = $this->createMock(QueryInterface::class);
    $endpointQuery->method('accessCheck')->willReturnSelf();
    $endpointQuery->method('condition')->willReturnSelf();
    $endpointQuery->method('sort')->willReturnSelf();
    $endpointQuery->method('execute')->willReturn([]);

    // Incident query for active incidents count.
    $incidentQuery = $this->createMock(QueryInterface::class);
    $incidentQuery->method('accessCheck')->willReturnSelf();
    $incidentQuery->method('condition')->willReturnSelf();
    $incidentQuery->method('count')->willReturnSelf();
    $incidentQuery->method('execute')->willReturn(0);

    $this->checkStorage
      ->method('getQuery')
      ->willReturn($endpointQuery);

    $this->incidentStorage
      ->method('getQuery')
      ->willReturn($incidentQuery);

    $summary = $this->service->getUptimeSummary(1);

    $this->assertIsArray($summary);
    $this->assertArrayHasKey('endpoints', $summary);
    $this->assertArrayHasKey('active_incidents', $summary);
    $this->assertEquals(0, $summary['active_incidents']);
  }

}
