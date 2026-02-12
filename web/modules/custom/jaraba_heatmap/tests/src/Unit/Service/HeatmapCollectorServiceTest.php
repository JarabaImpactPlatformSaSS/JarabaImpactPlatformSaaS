<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_heatmap\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Insert;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
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
   */
  protected HeatmapCollectorService $service;

  /**
   * Mocked database connection.
   */
  protected $database;

  /**
   * Mocked logger.
   */
  protected $logger;

  /**
   * Mocked state service.
   */
  protected $state;

  /**
   * Mocked queue factory.
   */
  protected $queueFactory;

  /**
   * Mocked config factory.
   */
  protected $configFactory;

  /**
   * Mocked insert query.
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
    $this->queueFactory = $this->createMock(QueueFactory::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);

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

    // Config mock: use_queue = FALSE para tests de inserción directa.
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')
      ->with('use_queue')
      ->willReturn(FALSE);
    $this->configFactory->method('get')
      ->with('jaraba_heatmap.settings')
      ->willReturn($config);

    $this->service = new HeatmapCollectorService(
      $this->database,
      $loggerFactory,
      $this->state,
      $this->queueFactory,
      $this->configFactory,
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

    // Config: use_queue = FALSE para inserción directa.
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->with('use_queue')->willReturn(FALSE);
    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->method('get')->willReturn($config);

    $service = new HeatmapCollectorService(
      $database, $loggerFactory, $state,
      $this->queueFactory, $configFactory,
    );

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

    // Config: use_queue = FALSE para inserción directa.
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->with('use_queue')->willReturn(FALSE);
    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->method('get')->willReturn($config);

    $service = new HeatmapCollectorService(
      $database, $loggerFactory, $state,
      $this->queueFactory, $configFactory,
    );

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

  /**
   * Tests that events are enqueued when use_queue is TRUE.
   *
   * @covers ::processEvents
   */
  public function testProcessEventsEnqueuesWhenConfigEnabled(): void {
    // Config: use_queue = TRUE (default).
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->with('use_queue')->willReturn(TRUE);
    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->method('get')->willReturn($config);

    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $loggerFactory->method('get')->willReturn($this->logger);

    $state = $this->createMock(StateInterface::class);
    $state->method('get')->willReturn(0);

    // Mock de la cola: verificar que createItem se llama 2 veces.
    $queue = $this->createMock(QueueInterface::class);
    $queue->expects($this->exactly(2))->method('createItem');

    $queueFactory = $this->createMock(QueueFactory::class);
    $queueFactory->method('get')
      ->with('jaraba_heatmap_events')
      ->willReturn($queue);

    $service = new HeatmapCollectorService(
      $this->database, $loggerFactory, $state,
      $queueFactory, $configFactory,
    );

    $payload = [
      'tenant_id' => 1,
      'session_id' => 'abc123',
      'page' => '/test',
      'viewport' => ['w' => 1280, 'h' => 900],
      'device' => 'desktop',
      'events' => [
        ['t' => 'click', 'x' => 100, 'y' => 200, 'el' => '.btn', 'txt' => 'OK'],
        ['t' => 'scroll', 'x' => 0, 'y' => 0, 'd' => 50],
      ],
    ];

    $result = $service->processEvents($payload);
    $this->assertSame(2, $result);
  }

  /**
   * Tests that useQueue defaults to TRUE when config key is missing.
   *
   * @covers ::useQueue
   */
  public function testUseQueueDefaultsToTrue(): void {
    // Config: use_queue no definido (NULL).
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->with('use_queue')->willReturn(NULL);
    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->method('get')->willReturn($config);

    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $loggerFactory->method('get')->willReturn($this->logger);

    $state = $this->createMock(StateInterface::class);
    $state->method('get')->willReturn(0);

    // La cola DEBE ser llamada (default = TRUE).
    $queue = $this->createMock(QueueInterface::class);
    $queue->expects($this->once())->method('createItem');

    $queueFactory = $this->createMock(QueueFactory::class);
    $queueFactory->method('get')->willReturn($queue);

    $service = new HeatmapCollectorService(
      $this->database, $loggerFactory, $state,
      $queueFactory, $configFactory,
    );

    $payload = [
      'tenant_id' => 1,
      'session_id' => 's1',
      'page' => '/test',
      'viewport' => ['w' => 1280, 'h' => 900],
      'device' => 'desktop',
      'events' => [
        ['t' => 'click', 'x' => 10, 'y' => 20, 'el' => 'a', 'txt' => 'x'],
      ],
    ];

    $service->processEvents($payload);
  }

}
