<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_analytics\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_analytics\Entity\AnalyticsDashboard;
use Drupal\jaraba_analytics\Service\DashboardManagerService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for the DashboardManagerService.
 *
 * @group jaraba_analytics
 * @coversDefaultClass \Drupal\jaraba_analytics\Service\DashboardManagerService
 */
class DashboardManagerServiceTest extends TestCase {

  /**
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The mocked logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * The service under test.
   *
   * @var \Drupal\jaraba_analytics\Service\DashboardManagerService
   */
  protected $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new DashboardManagerService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * Helper to create a mock AnalyticsDashboard entity.
   *
   * @param array $options
   *   Overrides for entity values.
   *
   * @return \Drupal\jaraba_analytics\Entity\AnalyticsDashboard|\PHPUnit\Framework\MockObject\MockObject
   *   The mocked dashboard entity.
   */
  protected function createMockDashboard(array $options = []) {
    $dashboard = $this->createMock(AnalyticsDashboard::class);

    $dashboard->method('id')->willReturn($options['id'] ?? '1');
    $dashboard->method('getName')->willReturn($options['name'] ?? 'Test Dashboard');
    $dashboard->method('getDescription')->willReturn($options['description'] ?? 'A test dashboard.');
    $dashboard->method('getLayoutConfig')->willReturn($options['layout_config'] ?? ['columns' => 12]);
    $dashboard->method('isDefault')->willReturn($options['is_default'] ?? FALSE);
    $dashboard->method('isShared')->willReturn($options['is_shared'] ?? FALSE);
    $dashboard->method('getOwnerId')->willReturn($options['owner_id'] ?? 1);
    $dashboard->method('getTenantId')->willReturn($options['tenant_id'] ?? NULL);
    $dashboard->method('getDashboardStatus')->willReturn($options['dashboard_status'] ?? 'active');

    // Mock created/changed fields.
    $createdField = new \stdClass();
    $createdField->value = $options['created'] ?? time();
    $changedField = new \stdClass();
    $changedField->value = $options['changed'] ?? time();

    $dashboard->method('get')
      ->willReturnCallback(function ($field) use ($createdField, $changedField) {
        return match ($field) {
          'created' => $createdField,
          'changed' => $changedField,
          default => new \stdClass(),
        };
      });

    return $dashboard;
  }

  /**
   * Tests getDashboard returns serialized data for existing dashboard.
   *
   * @covers ::getDashboard
   */
  public function testGetDashboardReturnsDataForExistingDashboard(): void {
    $dashboard = $this->createMockDashboard([
      'id' => '5',
      'name' => 'Sales Dashboard',
      'description' => 'Sales metrics overview.',
      'is_default' => TRUE,
    ]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(5)->willReturn($dashboard);

    $this->entityTypeManager->method('getStorage')
      ->with('analytics_dashboard')
      ->willReturn($storage);

    $result = $this->service->getDashboard(5);

    $this->assertNotNull($result);
    $this->assertSame(5, $result['id']);
    $this->assertSame('Sales Dashboard', $result['name']);
    $this->assertSame('Sales metrics overview.', $result['description']);
    $this->assertTrue($result['is_default']);
    $this->assertArrayHasKey('layout_config', $result);
    $this->assertArrayHasKey('created', $result);
  }

  /**
   * Tests getDashboard returns null for non-existing dashboard.
   *
   * @covers ::getDashboard
   */
  public function testGetDashboardReturnsNullForMissingDashboard(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(999)->willReturn(NULL);

    $this->entityTypeManager->method('getStorage')
      ->with('analytics_dashboard')
      ->willReturn($storage);

    $result = $this->service->getDashboard(999);

    $this->assertNull($result);
  }

  /**
   * Tests getDashboard handles exceptions gracefully.
   *
   * @covers ::getDashboard
   */
  public function testGetDashboardHandlesException(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->willThrowException(new \RuntimeException('DB error'));

    $this->entityTypeManager->method('getStorage')
      ->with('analytics_dashboard')
      ->willReturn($storage);

    $this->logger->expects($this->once())
      ->method('error');

    $result = $this->service->getDashboard(1);

    $this->assertNull($result);
  }

  /**
   * Tests getUserDashboards returns owned and shared dashboards.
   *
   * @covers ::getUserDashboards
   */
  public function testGetUserDashboardsReturnsOwnedAndShared(): void {
    $ownedDashboard = $this->createMockDashboard([
      'id' => '1',
      'name' => 'My Dashboard',
      'owner_id' => 10,
    ]);

    $sharedDashboard = $this->createMockDashboard([
      'id' => '2',
      'name' => 'Shared Dashboard',
      'is_shared' => TRUE,
      'owner_id' => 20,
    ]);

    $storage = $this->createMock(EntityStorageInterface::class);

    // Mock queries for owned and shared.
    $ownedQuery = $this->createMock(QueryInterface::class);
    $ownedQuery->method('accessCheck')->willReturnSelf();
    $ownedQuery->method('condition')->willReturnSelf();
    $ownedQuery->method('sort')->willReturnSelf();
    $ownedQuery->method('execute')->willReturn([1 => '1']);

    $sharedQuery = $this->createMock(QueryInterface::class);
    $sharedQuery->method('accessCheck')->willReturnSelf();
    $sharedQuery->method('condition')->willReturnSelf();
    $sharedQuery->method('sort')->willReturnSelf();
    $sharedQuery->method('execute')->willReturn([2 => '2']);

    $queryCallCount = 0;
    $storage->method('getQuery')
      ->willReturnCallback(function () use (&$queryCallCount, $ownedQuery, $sharedQuery) {
        $queryCallCount++;
        return $queryCallCount === 1 ? $ownedQuery : $sharedQuery;
      });

    $storage->method('loadMultiple')
      ->willReturn([1 => $ownedDashboard, 2 => $sharedDashboard]);

    $this->entityTypeManager->method('getStorage')
      ->with('analytics_dashboard')
      ->willReturn($storage);

    $results = $this->service->getUserDashboards(10, 100);

    $this->assertCount(2, $results);
    $this->assertSame('My Dashboard', $results[0]['name']);
    $this->assertSame('Shared Dashboard', $results[1]['name']);
  }

  /**
   * Tests getDefaultDashboard returns the default when one exists.
   *
   * @covers ::getDefaultDashboard
   */
  public function testGetDefaultDashboardReturnsDefault(): void {
    $dashboard = $this->createMockDashboard([
      'id' => '3',
      'name' => 'Default Dashboard',
      'is_default' => TRUE,
    ]);

    $storage = $this->createMock(EntityStorageInterface::class);

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([3 => '3']);

    $storage->method('getQuery')->willReturn($query);
    $storage->method('load')->with(3)->willReturn($dashboard);

    $this->entityTypeManager->method('getStorage')
      ->with('analytics_dashboard')
      ->willReturn($storage);

    $result = $this->service->getDefaultDashboard(100);

    $this->assertNotNull($result);
    $this->assertSame(3, $result['id']);
    $this->assertSame('Default Dashboard', $result['name']);
  }

  /**
   * Tests getDefaultDashboard returns null when no default exists.
   *
   * @covers ::getDefaultDashboard
   */
  public function testGetDefaultDashboardReturnsNullWhenNone(): void {
    $storage = $this->createMock(EntityStorageInterface::class);

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('analytics_dashboard')
      ->willReturn($storage);

    $result = $this->service->getDefaultDashboard(100);

    $this->assertNull($result);
  }

  /**
   * Tests saveDashboardLayout saves layout successfully.
   *
   * @covers ::saveDashboardLayout
   */
  public function testSaveDashboardLayoutSuccess(): void {
    $dashboard = $this->createMockDashboard(['id' => '1']);
    $dashboard->expects($this->once())->method('set')->with('layout_config', $this->isType('string'));
    $dashboard->expects($this->once())->method('save');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(1)->willReturn($dashboard);

    $this->entityTypeManager->method('getStorage')
      ->with('analytics_dashboard')
      ->willReturn($storage);

    $result = $this->service->saveDashboardLayout(1, ['columns' => 12, 'gap' => 16]);

    $this->assertTrue($result);
  }

  /**
   * Tests saveDashboardLayout returns false when dashboard not found.
   *
   * @covers ::saveDashboardLayout
   */
  public function testSaveDashboardLayoutReturnsFalseWhenNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(999)->willReturn(NULL);

    $this->entityTypeManager->method('getStorage')
      ->with('analytics_dashboard')
      ->willReturn($storage);

    $result = $this->service->saveDashboardLayout(999, ['columns' => 12]);

    $this->assertFalse($result);
  }

}
