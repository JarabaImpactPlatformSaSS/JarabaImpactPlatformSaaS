<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_heatmap\Unit\Plugin\QueueWorker;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Insert;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\jaraba_heatmap\Plugin\QueueWorker\HeatmapEventProcessor;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for HeatmapEventProcessor QueueWorker.
 *
 * @group jaraba_heatmap
 * @coversDefaultClass \Drupal\jaraba_heatmap\Plugin\QueueWorker\HeatmapEventProcessor
 */
class HeatmapEventProcessorTest extends TestCase {

  /**
   * The QueueWorker under test.
   */
  protected HeatmapEventProcessor $worker;

  /**
   * Mocked database connection.
   */
  protected $database;

  /**
   * Mocked logger.
   */
  protected $logger;

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

    // Set up the insert query mock.
    $this->insertQuery = $this->createMock(Insert::class);
    $this->insertQuery->method('fields')->willReturnSelf();
    $this->insertQuery->method('execute')->willReturn(1);

    $this->database->method('insert')
      ->with('heatmap_events')
      ->willReturn($this->insertQuery);

    // Mock del contenedor para \Drupal::time().
    $time = $this->createMock(TimeInterface::class);
    $time->method('getRequestTime')->willReturn(1700000000);

    $container = new ContainerBuilder();
    $container->set('datetime.time', $time);
    \Drupal::setContainer($container);

    $this->worker = new HeatmapEventProcessor(
      [],
      'jaraba_heatmap_events',
      ['cron' => ['time' => 30]],
      $this->database,
      $this->logger,
    );
  }

  /**
   * Tests processItem with a valid complete event.
   *
   * @covers ::processItem
   */
  public function testProcessItemValidEvent(): void {
    $this->insertQuery->expects($this->once())->method('fields');
    $this->insertQuery->expects($this->once())->method('execute');

    $data = [
      'tenant_id' => 1,
      'session_id' => 'abc123',
      'page_path' => '/productos/tomates',
      'event_type' => 'click',
      'x_percent' => 45.5,
      'y_pixel' => 320,
      'viewport_width' => 1280,
      'viewport_height' => 900,
      'scroll_depth' => NULL,
      'element_selector' => '.btn-comprar',
      'element_text' => 'Comprar',
      'device_type' => 'desktop',
      'created_at' => 1700000000,
    ];

    // No debe lanzar excepción.
    $this->worker->processItem($data);
  }

  /**
   * Tests processItem with missing fields uses defaults.
   *
   * @covers ::processItem
   */
  public function testProcessItemMissingFields(): void {
    $capturedFields = [];
    $insertQuery = $this->createMock(Insert::class);
    $insertQuery->method('fields')->willReturnCallback(
      function (array $fields) use ($insertQuery, &$capturedFields) {
        $capturedFields = $fields;
        return $insertQuery;
      }
    );
    $insertQuery->method('execute')->willReturn(1);

    $database = $this->createMock(Connection::class);
    $database->method('insert')->willReturn($insertQuery);

    $worker = new HeatmapEventProcessor(
      [], 'jaraba_heatmap_events', ['cron' => ['time' => 30]],
      $database, $this->logger,
    );

    // Evento con campos mínimos (sin opcionales).
    $data = [
      'tenant_id' => 1,
      'session_id' => 'ses1',
      'page_path' => '/test',
      'event_type' => 'move',
    ];

    $worker->processItem($data);

    // Verificar que los campos opcionales usan defaults.
    $this->assertNull($capturedFields['x_percent']);
    $this->assertNull($capturedFields['y_pixel']);
    $this->assertSame(0, $capturedFields['viewport_width']);
    $this->assertSame(0, $capturedFields['viewport_height']);
    $this->assertNull($capturedFields['scroll_depth']);
    $this->assertNull($capturedFields['element_selector']);
    $this->assertNull($capturedFields['element_text']);
    $this->assertSame('desktop', $capturedFields['device_type']);
    // created_at uses \Drupal::time()->getRequestTime() fallback.
    $this->assertSame(1700000000, $capturedFields['created_at']);
  }

  /**
   * Tests that database exception is caught and logged, not re-thrown.
   *
   * @covers ::processItem
   */
  public function testProcessItemDatabaseException(): void {
    $insertQuery = $this->createMock(Insert::class);
    $insertQuery->method('fields')->willReturnSelf();
    $insertQuery->method('execute')
      ->willThrowException(new \Exception('DB connection lost'));

    $database = $this->createMock(Connection::class);
    $database->method('insert')->willReturn($insertQuery);

    $logger = $this->createMock(LoggerInterface::class);
    // Debe registrar el error.
    $logger->expects($this->once())
      ->method('error')
      ->with(
        'Failed to process heatmap event: @message',
        $this->callback(function ($context) {
          return $context['@message'] === 'DB connection lost';
        })
      );

    $worker = new HeatmapEventProcessor(
      [], 'jaraba_heatmap_events', ['cron' => ['time' => 30]],
      $database, $logger,
    );

    $data = [
      'tenant_id' => 1,
      'session_id' => 'ses1',
      'page_path' => '/test',
      'event_type' => 'click',
      'created_at' => 1700000000,
    ];

    // NO debe lanzar excepción (diseño deliberado).
    $worker->processItem($data);
  }

  /**
   * Tests processItem with null values handles them correctly.
   *
   * @covers ::processItem
   */
  public function testProcessItemNullValues(): void {
    $capturedFields = [];
    $insertQuery = $this->createMock(Insert::class);
    $insertQuery->method('fields')->willReturnCallback(
      function (array $fields) use ($insertQuery, &$capturedFields) {
        $capturedFields = $fields;
        return $insertQuery;
      }
    );
    $insertQuery->method('execute')->willReturn(1);

    $database = $this->createMock(Connection::class);
    $database->method('insert')->willReturn($insertQuery);

    $worker = new HeatmapEventProcessor(
      [], 'jaraba_heatmap_events', ['cron' => ['time' => 30]],
      $database, $this->logger,
    );

    // Evento scroll con valores null explícitos.
    $data = [
      'tenant_id' => 2,
      'session_id' => 'ses2',
      'page_path' => '/about',
      'event_type' => 'scroll',
      'x_percent' => NULL,
      'y_pixel' => NULL,
      'viewport_width' => 768,
      'viewport_height' => 1024,
      'scroll_depth' => 75.5,
      'element_selector' => NULL,
      'element_text' => NULL,
      'device_type' => 'tablet',
      'created_at' => 1700000100,
    ];

    $worker->processItem($data);

    $this->assertNull($capturedFields['x_percent']);
    $this->assertNull($capturedFields['y_pixel']);
    $this->assertNull($capturedFields['element_selector']);
    $this->assertNull($capturedFields['element_text']);
    $this->assertSame(768, $capturedFields['viewport_width']);
    $this->assertSame('tablet', $capturedFields['device_type']);
  }

}
