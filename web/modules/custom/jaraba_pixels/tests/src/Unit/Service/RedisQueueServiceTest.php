<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_pixels\Unit\Service;

use Drupal\jaraba_pixels\Service\RedisQueueService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para RedisQueueService.
 *
 * Verifica la logica de encolar, desencolar y consultar estado
 * de la cola Redis para el procesamiento de eventos de pixel.
 *
 * @covers \Drupal\jaraba_pixels\Service\RedisQueueService
 * @group jaraba_pixels
 */
class RedisQueueServiceTest extends UnitTestCase {

  /**
   * Servicio bajo test.
   */
  protected RedisQueueService $service;

  /**
   * Mock de la factoria Redis.
   */
  protected $redisFactory;

  /**
   * Mock del logger.
   */
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->redisFactory = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['getClient'])
      ->getMock();

    $this->logger = $this->createMock(LoggerInterface::class);

    $loggerFactory = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['get'])
      ->getMock();
    $loggerFactory->method('get')
      ->with('jaraba_pixels.queue')
      ->willReturn($this->logger);

    $this->service = new RedisQueueService(
      $this->redisFactory,
      $loggerFactory,
    );
  }

  /**
   * Tests que isAvailable devuelve FALSE cuando Redis no esta disponible.
   */
  public function testIsAvailableReturnsFalseWhenRedisUnavailable(): void {
    $this->redisFactory->method('getClient')
      ->willThrowException(new \Exception('Connection refused'));

    $result = $this->service->isAvailable();

    $this->assertFalse($result);
  }

  /**
   * Tests que enqueue devuelve FALSE cuando Redis no esta disponible.
   */
  public function testEnqueueReturnsFalseWhenUnavailable(): void {
    $this->redisFactory->method('getClient')
      ->willThrowException(new \Exception('Connection refused'));

    $result = $this->service->enqueue([
      'event_type' => 'page_view',
      'tenant_id' => 1,
    ]);

    $this->assertFalse($result);
  }

  /**
   * Tests que dequeue devuelve array vacio cuando Redis no esta disponible.
   */
  public function testDequeueReturnsEmptyWhenUnavailable(): void {
    $this->redisFactory->method('getClient')
      ->willThrowException(new \Exception('Connection refused'));

    $result = $this->service->dequeue(100);

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * Tests que getQueueLength devuelve 0 cuando Redis no esta disponible.
   */
  public function testGetQueueLengthReturnsZeroWhenUnavailable(): void {
    $this->redisFactory->method('getClient')
      ->willThrowException(new \Exception('Connection refused'));

    $result = $this->service->getQueueLength();

    $this->assertSame(0, $result);
  }

  /**
   * Tests que getStats devuelve estructura correcta.
   */
  public function testGetStatsReturnsStructure(): void {
    $this->redisFactory->method('getClient')
      ->willThrowException(new \Exception('Connection refused'));

    $stats = $this->service->getStats();

    $this->assertIsArray($stats);
  }

}
