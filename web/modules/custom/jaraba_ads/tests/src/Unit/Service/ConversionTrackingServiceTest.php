<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ads\Unit\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_ads\Service\ConversionTrackingService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para ConversionTrackingService.
 *
 * @covers \Drupal\jaraba_ads\Service\ConversionTrackingService
 * @group jaraba_ads
 */
class ConversionTrackingServiceTest extends UnitTestCase {

  protected ConversionTrackingService $service;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new ConversionTrackingService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * Tests registrar conversion devuelve estructura correcta.
   */
  public function testRecordConversionSuccess(): void {
    $event = $this->createMock(ContentEntityInterface::class);
    $event->method('id')->willReturn(42);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('create')->willReturn($event);

    $this->entityTypeManager->method('getStorage')
      ->with('ads_conversion_event')
      ->willReturn($storage);

    $result = $this->service->recordConversion(1, 'meta', [
      'event_name' => 'Purchase',
      'event_time' => 1234567890,
      'email_hash' => hash('sha256', 'test@example.com'),
      'conversion_value' => 99.99,
      'currency' => 'EUR',
      'order_id' => 'ORD-001',
      'account_id' => 1,
    ]);

    $this->assertTrue($result['success']);
    $this->assertEquals(42, $result['event_id']);
  }

  /**
   * Tests subir conversiones pendientes sin eventos.
   */
  public function testUploadPendingConversionsEmpty(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('ads_conversion_event')
      ->willReturn($storage);

    $result = $this->service->uploadPendingConversions(1);
    $this->assertEquals(0, $result['total_pending']);
    $this->assertEquals(0, $result['uploaded']);
    $this->assertEquals(0, $result['failed']);
  }

  /**
   * Tests estadisticas de conversion devuelve estructura correcta.
   */
  public function testGetConversionStatsEmptyTenant(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('ads_conversion_event')
      ->willReturn($storage);

    $result = $this->service->getConversionStats(1, '30d');
    $this->assertEquals(0, $result['total_events']);
    $this->assertEquals(0, $result['uploaded']);
    $this->assertEquals(0, $result['pending']);
    $this->assertEquals(0, $result['failed']);
    $this->assertEquals(0.0, $result['total_value']);
  }

  /**
   * Tests periodo invalido usa default 30 dias.
   */
  public function testGetConversionStatsInvalidPeriod(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('ads_conversion_event')
      ->willReturn($storage);

    $result = $this->service->getConversionStats(1, '0d');
    $this->assertArrayHasKey('total_events', $result);
  }

}
