<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_sla\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_sla\Service\UptimeMonitorService;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for UptimeMonitorService.
 *
 * @coversDefaultClass \Drupal\jaraba_sla\Service\UptimeMonitorService
 * @group jaraba_sla
 */
class UptimeMonitorServiceTest extends UnitTestCase {

  /**
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The mocked tenant context service.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected TenantContextService $tenantContext;

  /**
   * The mocked HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected ClientInterface $httpClient;

  /**
   * The mocked config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The mocked logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected LoggerInterface $logger;

  /**
   * The service under test.
   *
   * @var \Drupal\jaraba_sla\Service\UptimeMonitorService
   */
  protected UptimeMonitorService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->tenantContext = $this->createMock(TenantContextService::class);
    $this->httpClient = $this->createMock(ClientInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    // Default config mock.
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturnCallback(function (string $key) {
      $values = [
        'monitoring.web_app' => ['url' => '/health', 'interval_seconds' => 30, 'timeout_ms' => 2000],
        'monitoring.api' => ['url' => '/api/v1/health', 'interval_seconds' => 30, 'timeout_ms' => 2000],
        'monitoring.database' => ['interval_seconds' => 15],
        'monitoring.redis' => ['interval_seconds' => 15],
        'monitoring.email' => ['interval_seconds' => 60],
        'monitoring.ai_copilot' => ['interval_seconds' => 60],
        'monitoring.payment' => ['interval_seconds' => 60],
      ];
      return $values[$key] ?? NULL;
    });
    $this->configFactory->method('get')->with('jaraba_sla.settings')->willReturn($config);

    $this->service = new UptimeMonitorService(
      $this->entityTypeManager,
      $this->tenantContext,
      $this->httpClient,
      $this->configFactory,
      $this->logger,
    );
  }

  /**
   * Tests checkComponent returns a valid status structure.
   *
   * @covers ::checkComponent
   */
  public function testCheckComponentReturnsStatus(): void {
    // For database component, mock a successful entity query.
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([1]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('user')
      ->willReturn($storage);

    $result = $this->service->checkComponent('database');

    $this->assertArrayHasKey('status', $result);
    $this->assertArrayHasKey('response_time_ms', $result);
    $this->assertArrayHasKey('checked_at', $result);
    $this->assertContains($result['status'], ['up', 'down', 'degraded']);
    $this->assertIsInt($result['response_time_ms']);
    $this->assertNotEmpty($result['checked_at']);
  }

  /**
   * Tests getStatusPageData includes all 7 components.
   *
   * @covers ::getStatusPageData
   * @covers ::checkAllComponents
   */
  public function testGetStatusPageDataIncludesAllComponents(): void {
    // Mock the user storage for database checks.
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([1]);

    $userStorage = $this->createMock(EntityStorageInterface::class);
    $userStorage->method('getQuery')->willReturn($query);

    // Mock the incident storage for recent incidents.
    $incidentQuery = $this->createMock(QueryInterface::class);
    $incidentQuery->method('accessCheck')->willReturnSelf();
    $incidentQuery->method('condition')->willReturnSelf();
    $incidentQuery->method('sort')->willReturnSelf();
    $incidentQuery->method('range')->willReturnSelf();
    $incidentQuery->method('execute')->willReturn([]);

    $incidentStorage = $this->createMock(EntityStorageInterface::class);
    $incidentStorage->method('getQuery')->willReturn($incidentQuery);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $type) use ($userStorage, $incidentStorage) {
        return $type === 'user' ? $userStorage : $incidentStorage;
      });

    $result = $this->service->getStatusPageData();

    $this->assertArrayHasKey('overall_status', $result);
    $this->assertArrayHasKey('components', $result);
    $this->assertArrayHasKey('recent_incidents', $result);
    $this->assertArrayHasKey('last_updated', $result);

    // Verify all 7 components are present.
    $expectedComponents = ['web_app', 'api', 'database', 'redis', 'email', 'ai_copilot', 'payment'];
    foreach ($expectedComponents as $component) {
      $this->assertArrayHasKey($component, $result['components'], "Missing component: $component");
    }

    $this->assertCount(7, $result['components']);
    $this->assertContains($result['overall_status'], ['operational', 'degraded', 'major_outage']);
  }

}
