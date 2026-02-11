<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_heatmap\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Insert;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\jaraba_heatmap\Service\HeatmapCollectorService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for HeatmapCollectorService.
 *
 * @group jaraba_heatmap
 * @coversDefaultClass \Drupal\jaraba_heatmap\Service\HeatmapCollectorService
 */
class HeatmapCollectorServiceTest extends TestCase {

  /**
   * The service under test.
   *
   * @var \Drupal\jaraba_heatmap\Service\HeatmapCollectorService
   */
  protected HeatmapCollectorService $service;

  /**
   * Mocked database connection.
   *
   * @var \Drupal\Core\Database\Connection|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $database;

  /**
   * Mocked logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * Mocked state service.
   *
   * @var \Drupal\Core\State\StateInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $state;

  /**
   * Mocked insert query.
   *
   * @var \Drupal\Core\Database\Query\Insert|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $insertQuery;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->database = $this->createMock(Connection::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->state = $this->createMock(StateInterface::class);

    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $loggerFactory->method('get')
      ->with('jaraba_heatmap')
      ->willReturn($this->logger);

    // Set up the insert query mock.
    $this->insertQuery = $this->createMock(Insert::class);
    $this->insertQuery->method('fields')->willReturnSelf();
    $this->insertQuery->method('values')->willReturnSelf();
    $this->insertQuery->method('execute')->willReturn(1);

    $this->database->method('insert')
      ->with('heatmap_events')
      ->willReturn($this->insertQuery);

    $this->service = new HeatmapCollectorService(
      $this->database,
      $loggerFactory,
      $this->state,
    );
  }

  /**
   * Tests that an empty events array returns 0.
   *
   * @covers ::processEvents
   */
  public function testProcessEventsReturnsZeroForEmptyPayload(): void {
    $payload = [
      'tenant_id' => 1,
      'session_id' => 'abc123',
      'page' => '/test',
      'viewport' => ['w' => 1280, 'h' => 900],
      'device' => 'desktop',
      'events' => [],
    ];

    $result = $this->service->processEvents($payload);
    $this->assertSame(0, $result);
  }

  /**
   * Tests that a payload with 3 valid events returns 3.
   *
   * @covers ::processEvents
   */
  public function testProcessEventsCountsValidEvents(): void {
    $this->state->method('get')
      ->with('jaraba_heatmap.total_events', 0)
      ->willReturn(0);

    $payload = [
      'tenant_id' => 1,
      'session_id' => 'abc123',
      'page' => '/test',
      'viewport' => ['w' => 1280, 'h' => 900],
      'device' => 'desktop',
      'events' => [
        ['t' => 'click', 'x' => 100, 'y' => 200, 'el' => '.btn', 'txt' => 'Click'],
        ['t' => 'move', 'x' => 300, 'y' => 400],
        ['t' => 'scroll', 'x' => 0, 'y' => 500, 'd' => 75],
      ],
    ];

    $result = $this->service->processEvents($payload);
    $this->assertSame(3, $result);
  }

  /**
   * Tests that an invalid event type is skipped.
   *
   * @covers ::processEvents
   */
  public function testProcessEventsSkipsInvalidEventTypes(): void {
    $payload = [
      'tenant_id' => 1,
      'session_id' => 'abc123',
      'page' => '/test',
      'viewport' => ['w' => 1280, 'h' => 900],
      'device' => 'desktop',
      'events' => [
        ['t' => 'invalid', 'x' => 100, 'y' => 200],
      ],
    ];

    $result = $this->service->processEvents($payload);
    $this->assertSame(0, $result);
  }

  /**
   * Tests that x_percent is calculated correctly.
   *
   * x=640 with viewport_width=1280 should yield x_percent = 50.00.
   *
   * @covers ::processEvents
   */
  public function testProcessEventsCalculatesXPercent(): void {
    $capturedValues = [];
    $insertQuery = $this->createMock(Insert::class);
    $insertQuery->method('fields')->willReturnSelf();
    $insertQuery->method('values')->willReturnCallback(
      function (array $values) use ($insertQuery, &$capturedValues) {
        $capturedValues[] = $values;
        return $insertQuery;
      }
    );
    $insertQuery->method('execute')->willReturn(1);

    $database = $this->createMock(Connection::class);
    $database->method('insert')
      ->with('heatmap_events')
      ->willReturn($insertQuery);

    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $loggerFactory->method('get')->willReturn($this->logger);

    $state = $this->createMock(StateInterface::class);
    $state->method('get')->willReturn(0);

    $service = new HeatmapCollectorService($database, $loggerFactory, $state);

    $payload = [
      'tenant_id' => 1,
      'session_id' => 'session1',
      'page' => '/test',
      'viewport' => ['w' => 1280, 'h' => 900],
      'device' => 'desktop',
      'events' => [
        ['t' => 'click', 'x' => 640, 'y' => 200, 'el' => '.btn', 'txt' => 'OK'],
      ],
    ];

    $service->processEvents($payload);

    $this->assertNotEmpty($capturedValues);
    $this->assertSame(50.0, $capturedValues[0]['x_percent']);
  }

  /**
   * Tests that x_percent is clamped to 100 when x > viewport_width.
   *
   * @covers ::processEvents
   */
  public function testProcessEventsClampXPercent(): void {
    $capturedValues = [];
    $insertQuery = $this->createMock(Insert::class);
    $insertQuery->method('fields')->willReturnSelf();
    $insertQuery->method('values')->willReturnCallback(
      function (array $values) use ($insertQuery, &$capturedValues) {
        $capturedValues[] = $values;
        return $insertQuery;
      }
    );
    $insertQuery->method('execute')->willReturn(1);

    $database = $this->createMock(Connection::class);
    $database->method('insert')
      ->with('heatmap_events')
      ->willReturn($insertQuery);

    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $loggerFactory->method('get')->willReturn($this->logger);

    $state = $this->createMock(StateInterface::class);
    $state->method('get')->willReturn(0);

    $service = new HeatmapCollectorService($database, $loggerFactory, $state);

    $payload = [
      'tenant_id' => 1,
      'session_id' => 'session1',
      'page' => '/test',
      'viewport' => ['w' => 1280, 'h' => 900],
      'device' => 'desktop',
      'events' => [
        ['t' => 'move', 'x' => 2000, 'y' => 100],
      ],
    ];

    $service->processEvents($payload);

    $this->assertNotEmpty($capturedValues);
    $this->assertEquals(100.0, $capturedValues[0]['x_percent']);
  }

  /**
   * Tests normalizeDevice returns 'desktop' for an unknown device.
   *
   * Uses reflection to access the protected method.
   *
   * @covers ::normalizeDevice
   */
  public function testNormalizeDeviceReturnsDesktopForUnknown(): void {
    $reflection = new \ReflectionMethod($this->service, 'normalizeDevice');
    $reflection->setAccessible(TRUE);

    $result = $reflection->invoke($this->service, 'unknown_device');
    $this->assertSame('desktop', $result);
  }

  /**
   * Tests normalizeDevice accepts a valid device type.
   *
   * @covers ::normalizeDevice
   */
  public function testNormalizeDeviceAcceptsValid(): void {
    $reflection = new \ReflectionMethod($this->service, 'normalizeDevice');
    $reflection->setAccessible(TRUE);

    $result = $reflection->invoke($this->service, 'tablet');
    $this->assertSame('tablet', $result);
  }

  /**
   * Tests sanitizeString truncates a string exceeding max_length.
   *
   * Uses reflection to access the protected method.
   *
   * @covers ::sanitizeString
   */
  public function testSanitizeStringTruncates(): void {
    $reflection = new \ReflectionMethod($this->service, 'sanitizeString');
    $reflection->setAccessible(TRUE);

    $longString = str_repeat('a', 200);
    $result = $reflection->invoke($this->service, $longString, 50);

    $this->assertSame(50, mb_strlen($result));
    $this->assertSame(str_repeat('a', 50), $result);
  }

}
