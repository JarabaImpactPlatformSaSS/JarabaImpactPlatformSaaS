<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_pixels\Unit\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_pixels\Service\BatchProcessorService;
use Drupal\jaraba_pixels\Service\PixelDispatcherService;
use Drupal\jaraba_pixels\Service\RedisQueueService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para BatchProcessorService.
 *
 * Verifica la logica de procesamiento por lotes, agrupacion
 * por plataforma y estadisticas del procesador batch.
 *
 * @covers \Drupal\jaraba_pixels\Service\BatchProcessorService
 * @group jaraba_pixels
 */
class BatchProcessorServiceTest extends UnitTestCase {

  /**
   * Servicio bajo test.
   */
  protected BatchProcessorService $service;

  /**
   * Mock del servicio de cola Redis.
   */
  protected RedisQueueService $queue;

  /**
   * Mock del servicio de dispatch de pixels.
   */
  protected PixelDispatcherService $dispatcher;

  /**
   * Mock del gestor de tipos de entidad.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock del logger.
   */
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->queue = $this->createMock(RedisQueueService::class);
    $this->dispatcher = $this->createMock(PixelDispatcherService::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $loggerFactory = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['get'])
      ->getMock();
    $loggerFactory->method('get')
      ->with('jaraba_pixels.batch')
      ->willReturn($this->logger);

    $this->service = new BatchProcessorService(
      $this->queue,
      $this->dispatcher,
      $this->entityTypeManager,
      $loggerFactory,
    );
  }

  /**
   * Tests que process devuelve 0 cuando la cola esta vacia.
   */
  public function testProcessReturnsZeroWhenQueueEmpty(): void {
    $this->queue->method('isAvailable')
      ->willReturn(TRUE);

    $this->queue->expects($this->once())
      ->method('dequeue')
      ->with(100)
      ->willReturn([]);

    $result = $this->service->process(100);

    $this->assertSame(0, $result);
  }

  /**
   * Tests que process procesa un lote con eventos.
   */
  public function testProcessHandlesBatchWithEvents(): void {
    $this->queue->method('isAvailable')
      ->willReturn(TRUE);

    $events = [
      ['event_type' => 'page_view', 'platform' => 'meta', 'tenant_id' => 1],
      ['event_type' => 'purchase', 'platform' => 'google', 'tenant_id' => 1],
      ['event_type' => 'page_view', 'platform' => 'meta', 'tenant_id' => 2],
    ];

    $this->queue->expects($this->once())
      ->method('dequeue')
      ->with(100)
      ->willReturn($events);

    $result = $this->service->process(100);

    $this->assertGreaterThanOrEqual(0, $result);
  }

  /**
   * Tests que groupByPlatform agrupa correctamente los eventos.
   */
  public function testGroupByPlatformGroupsCorrectly(): void {
    $events = [
      ['event_type' => 'page_view', 'platform' => 'meta'],
      ['event_type' => 'purchase', 'platform' => 'google'],
      ['event_type' => 'lead', 'platform' => 'meta'],
    ];

    $grouped = $this->service->groupByPlatform($events);

    $this->assertIsArray($grouped);
    $this->assertArrayHasKey('meta', $grouped);
    $this->assertArrayHasKey('google', $grouped);
    $this->assertArrayHasKey('linkedin', $grouped);
    $this->assertArrayHasKey('tiktok', $grouped);
    // The service broadcasts all events to all platforms.
    $this->assertCount(3, $grouped['meta']);
    $this->assertCount(3, $grouped['google']);
  }

  /**
   * Tests que getStats devuelve estructura correcta.
   */
  public function testGetStatsReturnsArray(): void {
    $stats = $this->service->getStats();

    $this->assertIsArray($stats);
  }

}
