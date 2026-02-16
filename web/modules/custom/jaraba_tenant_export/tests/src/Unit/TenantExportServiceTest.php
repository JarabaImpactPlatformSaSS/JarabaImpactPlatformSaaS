<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_tenant_export\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\ecosistema_jaraba_core\Service\AuditLogService;
use Drupal\ecosistema_jaraba_core\Service\RateLimiterService;
use Drupal\jaraba_tenant_export\Service\TenantDataCollectorService;
use Drupal\jaraba_tenant_export\Service\TenantExportService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for TenantExportService.
 *
 * @group jaraba_tenant_export
 * @coversDefaultClass \Drupal\jaraba_tenant_export\Service\TenantExportService
 */
class TenantExportServiceTest extends UnitTestCase {

  /**
   * The service under test.
   */
  protected TenantExportService $service;

  /**
   * Mock dependencies.
   */
  protected EntityTypeManagerInterface $entityTypeManager;
  protected TenantDataCollectorService $dataCollector;
  protected RateLimiterService $rateLimiter;
  protected AuditLogService $auditLog;
  protected FileSystemInterface $fileSystem;
  protected ConfigFactoryInterface $configFactory;
  protected QueueFactory $queueFactory;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->dataCollector = $this->createMock(TenantDataCollectorService::class);
    $this->rateLimiter = $this->createMock(RateLimiterService::class);
    $this->auditLog = $this->createMock(AuditLogService::class);
    $this->fileSystem = $this->createMock(FileSystemInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->queueFactory = $this->createMock(QueueFactory::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    // Setup config mock.
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturnMap([
      ['export_expiration_hours', 48],
      ['rate_limit_per_day', 3],
      ['max_export_size_mb', 500],
      ['analytics_row_limit', 50000],
      ['default_sections', ['core', 'analytics', 'knowledge', 'operational', 'files']],
    ]);
    $this->configFactory->method('get')
      ->with('jaraba_tenant_export.settings')
      ->willReturn($config);

    $this->service = new TenantExportService(
      $this->entityTypeManager,
      $this->dataCollector,
      $this->rateLimiter,
      $this->auditLog,
      $this->fileSystem,
      $this->configFactory,
      $this->queueFactory,
      $this->logger,
    );
  }

  /**
   * @covers ::canRequestExport
   */
  public function testCanRequestExportAllowed(): void {
    $this->rateLimiter->method('check')
      ->willReturn([
        'allowed' => TRUE,
        'remaining' => 2,
        'limit' => 3,
        'reset_at' => time() + 3600,
        'retry_after' => 0,
      ]);

    $result = $this->service->canRequestExport(42);

    $this->assertTrue($result['allowed']);
    $this->assertEquals(0, $result['retry_after']);
  }

  /**
   * @covers ::canRequestExport
   */
  public function testCanRequestExportRateLimited(): void {
    $this->rateLimiter->method('check')
      ->willReturn([
        'allowed' => FALSE,
        'remaining' => 0,
        'limit' => 3,
        'reset_at' => time() + 7200,
        'retry_after' => 7200,
      ]);

    $result = $this->service->canRequestExport(42);

    $this->assertFalse($result['allowed']);
    $this->assertGreaterThan(0, $result['retry_after']);
    $this->assertNotEmpty($result['retry_after_formatted']);
  }

  /**
   * @covers ::cleanupExpiredExports
   */
  public function testCleanupExpiredExportsNone(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $query = $this->createMock(\Drupal\Core\Entity\Query\QueryInterface::class);

    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage->method('getQuery')->willReturn($query);
    $this->entityTypeManager->method('getStorage')
      ->with('tenant_export_record')
      ->willReturn($storage);

    $result = $this->service->cleanupExpiredExports();

    $this->assertEquals(0, $result);
  }

  /**
   * @covers ::getExportHistory
   */
  public function testGetExportHistoryEmpty(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $query = $this->createMock(\Drupal\Core\Entity\Query\QueryInterface::class);

    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage->method('getQuery')->willReturn($query);
    $this->entityTypeManager->method('getStorage')
      ->with('tenant_export_record')
      ->willReturn($storage);

    $result = $this->service->getExportHistory(42);

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

}
