<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_email\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_email\Entity\EmailSubscriber;
use Drupal\jaraba_email\Service\SubscriberService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para SubscriberService.
 *
 * @covers \Drupal\jaraba_email\Service\SubscriberService
 * @group jaraba_email
 */
class SubscriberServiceTest extends UnitTestCase {

  protected SubscriberService $service;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new SubscriberService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * Tests buscar suscriptor por email inexistente retorna NULL.
   */
  public function testFindByEmailReturnsNullWhenNotFound(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('condition')->willReturnSelf();
    $query->method('accessCheck')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('email_subscriber')
      ->willReturn($storage);

    $result = $this->service->findByEmail('noexiste@example.com');

    $this->assertNull($result);
  }

  /**
   * Tests buscar suscriptor por email existente retorna entidad.
   */
  public function testFindByEmailReturnsSubscriber(): void {
    $subscriber = $this->createMock(EmailSubscriber::class);

    $query = $this->createMock(QueryInterface::class);
    $query->method('condition')->willReturnSelf();
    $query->method('accessCheck')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([42]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);
    $storage->method('load')->with(42)->willReturn($subscriber);

    $this->entityTypeManager->method('getStorage')
      ->with('email_subscriber')
      ->willReturn($storage);

    $result = $this->service->findByEmail('existe@example.com');

    $this->assertSame($subscriber, $result);
  }

  /**
   * Tests dar de baja suscriptor inexistente retorna FALSE.
   */
  public function testUnsubscribeNonExistentReturnsFalse(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('condition')->willReturnSelf();
    $query->method('accessCheck')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('email_subscriber')
      ->willReturn($storage);

    $result = $this->service->unsubscribe('noexiste@example.com');

    $this->assertFalse($result);
  }

  /**
   * Tests contar suscriptores activos de una lista.
   */
  public function testCountActiveSubscribers(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('condition')->willReturnSelf();
    $query->method('accessCheck')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    $query->method('execute')->willReturn(25);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('email_subscriber')
      ->willReturn($storage);

    $count = $this->service->countActiveSubscribers(1);

    $this->assertEquals(25, $count);
  }

  /**
   * Tests obtener suscriptores activos de una lista.
   */
  public function testGetActiveSubscribersReturnsList(): void {
    $subscriber1 = $this->createMock(EmailSubscriber::class);
    $subscriber2 = $this->createMock(EmailSubscriber::class);

    $query = $this->createMock(QueryInterface::class);
    $query->method('condition')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('accessCheck')->willReturnSelf();
    $query->method('execute')->willReturn([10, 20]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);
    $storage->method('loadMultiple')
      ->with([10, 20])
      ->willReturn([$subscriber1, $subscriber2]);

    $this->entityTypeManager->method('getStorage')
      ->with('email_subscriber')
      ->willReturn($storage);

    $result = $this->service->getActiveSubscribers(1, 100, 0);

    $this->assertCount(2, $result);
    $this->assertSame($subscriber1, $result[0]);
    $this->assertSame($subscriber2, $result[1]);
  }

}
