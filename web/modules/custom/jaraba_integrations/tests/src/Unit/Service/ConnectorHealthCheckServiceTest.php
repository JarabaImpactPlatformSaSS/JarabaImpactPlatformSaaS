<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_integrations\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_integrations\Entity\Connector;
use Drupal\jaraba_integrations\Entity\ConnectorInstallation;
use Drupal\jaraba_integrations\Service\ConnectorHealthCheckService;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Psr\Log\LoggerInterface;

/**
 * Tests for ConnectorHealthCheckService.
 *
 * @coversDefaultClass \Drupal\jaraba_integrations\Service\ConnectorHealthCheckService
 * @group jaraba_integrations
 */
class ConnectorHealthCheckServiceTest extends UnitTestCase {

  /**
   * The service under test.
   */
  protected ConnectorHealthCheckService $service;

  /**
   * Mocked entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mocked HTTP client.
   */
  protected ClientInterface $httpClient;

  /**
   * Mocked logger.
   */
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->httpClient = $this->createMock(ClientInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new ConnectorHealthCheckService(
      $this->entityTypeManager,
      $this->httpClient,
      $this->logger,
    );
  }

  /**
   * Creates a mock ConnectorInstallation.
   *
   * @param \Drupal\jaraba_integrations\Entity\Connector|null $connector
   *   The parent connector mock.
   * @param string $status
   *   Installation status.
   * @param int $id
   *   The installation entity ID.
   *
   * @return \Drupal\jaraba_integrations\Entity\ConnectorInstallation
   *   The mock installation.
   */
  protected function createInstallationMock(?Connector $connector, string $status = 'active', int $id = 1): ConnectorInstallation {
    $installation = $this->createMock(ConnectorInstallation::class);
    $installation->method('getConnector')->willReturn($connector);
    $installation->method('id')->willReturn($id);
    $installation->method('isActive')->willReturn($status === 'active');
    $installation->method('getInstallationStatus')->willReturn($status);
    $installation->method('set')->willReturnSelf();
    $installation->method('save')->willReturn(NULL);

    return $installation;
  }

  /**
   * Creates a mock Connector entity.
   *
   * @param string $apiUrl
   *   The API base URL.
   *
   * @return \Drupal\jaraba_integrations\Entity\Connector
   *   The mock connector.
   */
  protected function createConnectorMock(string $apiUrl = ''): Connector {
    $connector = $this->createMock(Connector::class);
    $connector->method('getApiBaseUrl')->willReturn($apiUrl);
    return $connector;
  }

  /**
   * Tests checkInstallation returns error when connector is missing.
   *
   * @covers ::checkInstallation
   */
  public function testCheckInstallationReturnsErrorWhenConnectorMissing(): void {
    $installation = $this->createInstallationMock(NULL);

    $result = $this->service->checkInstallation($installation);

    $this->assertSame('error', $result['status']);
    $this->assertSame(0, $result['latency_ms']);
    $this->assertStringContainsString('no encontrado', $result['message']);
  }

  /**
   * Tests checkInstallation returns ok when connector has no API URL.
   *
   * @covers ::checkInstallation
   */
  public function testCheckInstallationReturnsOkWhenNoApiUrl(): void {
    $connector = $this->createConnectorMock('');
    $installation = $this->createInstallationMock($connector);

    $result = $this->service->checkInstallation($installation);

    $this->assertSame('ok', $result['status']);
    $this->assertSame(0, $result['latency_ms']);
    $this->assertStringContainsString('Sin URL', $result['message']);
  }

  /**
   * Tests checkInstallation returns ok for 200 response.
   *
   * @covers ::checkInstallation
   */
  public function testCheckInstallationReturnsOkForSuccessfulResponse(): void {
    $connector = $this->createConnectorMock('https://api.example.com');
    $installation = $this->createInstallationMock($connector);

    $this->httpClient->method('request')
      ->with('HEAD', 'https://api.example.com', $this->anything())
      ->willReturn(new GuzzleResponse(200));

    // Verify save is called to persist health check result.
    $installation->expects($this->once())->method('save');

    $result = $this->service->checkInstallation($installation);

    $this->assertSame('ok', $result['status']);
    $this->assertArrayHasKey('latency_ms', $result);
    $this->assertSame(200, $result['http_code']);
    $this->assertArrayHasKey('checked_at', $result);
  }

  /**
   * Tests checkInstallation returns error for 500+ response codes.
   *
   * @covers ::checkInstallation
   */
  public function testCheckInstallationReturnsErrorForServerError(): void {
    $connector = $this->createConnectorMock('https://api.example.com');
    $installation = $this->createInstallationMock($connector);

    $this->httpClient->method('request')
      ->willReturn(new GuzzleResponse(503));

    $result = $this->service->checkInstallation($installation);

    $this->assertSame('error', $result['status']);
    $this->assertSame(503, $result['http_code']);
    $this->assertStringContainsString('Error HTTP 503', $result['message']);
  }

  /**
   * Tests checkInstallation treats 4xx as ok (not a server error).
   *
   * @covers ::checkInstallation
   */
  public function testCheckInstallationTreats4xxAsOk(): void {
    $connector = $this->createConnectorMock('https://api.example.com');
    $installation = $this->createInstallationMock($connector);

    $this->httpClient->method('request')
      ->willReturn(new GuzzleResponse(404));

    $result = $this->service->checkInstallation($installation);

    $this->assertSame('ok', $result['status']);
    $this->assertSame(404, $result['http_code']);
  }

  /**
   * Tests checkInstallation handles connection exception.
   *
   * @covers ::checkInstallation
   */
  public function testCheckInstallationHandlesConnectionException(): void {
    $connector = $this->createConnectorMock('https://api.example.com');
    $installation = $this->createInstallationMock($connector);

    $this->httpClient->method('request')
      ->willThrowException(new RequestException(
        'Connection timed out',
        new GuzzleRequest('HEAD', 'https://api.example.com')
      ));

    $result = $this->service->checkInstallation($installation);

    $this->assertSame('error', $result['status']);
    $this->assertStringContainsString('Connection error', $result['message']);
    $this->assertArrayHasKey('latency_ms', $result);
  }

  /**
   * Tests checkAllForTenant queries installations and checks each.
   *
   * @covers ::checkAllForTenant
   */
  public function testCheckAllForTenantProcessesAllInstallations(): void {
    // Mock storage query returns 2 installations.
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([1, 2]);

    // Both installations have no API URL (simplest ok case).
    $connector = $this->createConnectorMock('');
    $inst1 = $this->createInstallationMock($connector, 'active', 1);
    $inst2 = $this->createInstallationMock($connector, 'active', 2);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);
    $storage->method('loadMultiple')
      ->with([1, 2])
      ->willReturn([1 => $inst1, 2 => $inst2]);

    $this->entityTypeManager->method('getStorage')
      ->with('connector_installation')
      ->willReturn($storage);

    $results = $this->service->checkAllForTenant('tenant-1');

    $this->assertCount(2, $results);
    // Both should be ok (no API URL).
    $this->assertSame('ok', $results[1]['status']);
    $this->assertSame('ok', $results[2]['status']);
  }

  /**
   * Tests checkAllForTenant returns empty when no installations found.
   *
   * @covers ::checkAllForTenant
   */
  public function testCheckAllForTenantReturnsEmptyWhenNoInstallations(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('connector_installation')
      ->willReturn($storage);

    $results = $this->service->checkAllForTenant('tenant-1');

    $this->assertEmpty($results);
  }

}
