<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_pwa\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_pwa\Entity\PushSubscription;
use Drupal\jaraba_pwa\Service\PlatformPushService;
use GuzzleHttp\ClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for PlatformPushService.
 *
 * @coversDefaultClass \Drupal\jaraba_pwa\Service\PlatformPushService
 * @group jaraba_pwa
 */
class PlatformPushServiceTest extends TestCase {

  /**
   * The service under test.
   */
  protected PlatformPushService $service;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock HTTP client.
   */
  protected ClientInterface $httpClient;

  /**
   * Mock logger.
   */
  protected LoggerInterface $logger;

  /**
   * Mock config factory.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Mock entity storage.
   */
  protected EntityStorageInterface $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->httpClient = $this->createMock(ClientInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->storage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('push_subscription')
      ->willReturn($this->storage);

    $this->service = new PlatformPushService(
      $this->entityTypeManager,
      $this->httpClient,
      $this->logger,
      $this->configFactory,
    );
  }

  /**
   * Tests subscribe() creates a new subscription when none exists.
   *
   * @covers ::subscribe
   */
  public function testSubscribeCreatesNewSubscription(): void {
    $data = [
      'user_id' => 42,
      'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint',
      'keys' => [
        'auth' => 'test-auth-key',
        'p256dh' => 'test-p256dh-key',
      ],
      'user_agent' => 'Test Browser/1.0',
    ];

    // No existing subscriptions.
    $this->storage
      ->expects($this->once())
      ->method('loadByProperties')
      ->with([
        'user_id' => 42,
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint',
      ])
      ->willReturn([]);

    // Create new entity.
    $subscription = $this->createMock(PushSubscription::class);
    $subscription->method('id')->willReturn(123);
    $subscription->expects($this->once())->method('save');

    $this->storage
      ->expects($this->once())
      ->method('create')
      ->willReturn($subscription);

    $result = $this->service->subscribe($data);

    $this->assertSame(123, $result);
  }

  /**
   * Tests subscribe() reactivates an existing subscription.
   *
   * @covers ::subscribe
   */
  public function testSubscribeReactivatesExisting(): void {
    $data = [
      'user_id' => 42,
      'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint',
      'keys' => [
        'auth' => 'new-auth-key',
        'p256dh' => 'new-p256dh-key',
      ],
    ];

    $existing = $this->createMock(PushSubscription::class);
    $existing->method('id')->willReturn(99);
    $existing->expects($this->atLeastOnce())->method('set');
    $existing->expects($this->once())->method('save');

    $this->storage
      ->expects($this->once())
      ->method('loadByProperties')
      ->willReturn([$existing]);

    $result = $this->service->subscribe($data);

    $this->assertSame(99, $result);
  }

  /**
   * Tests subscribe() returns NULL on exception.
   *
   * @covers ::subscribe
   */
  public function testSubscribeReturnsNullOnError(): void {
    $this->storage
      ->method('loadByProperties')
      ->willThrowException(new \RuntimeException('Storage error'));

    $this->logger
      ->expects($this->once())
      ->method('error');

    $result = $this->service->subscribe([
      'user_id' => 1,
      'endpoint' => 'https://example.com/push',
      'keys' => ['auth' => 'a', 'p256dh' => 'b'],
    ]);

    $this->assertNull($result);
  }

  /**
   * Tests unsubscribe() expires matching subscriptions.
   *
   * @covers ::unsubscribe
   */
  public function testUnsubscribeExpiresSubscription(): void {
    $subscription = $this->createMock(PushSubscription::class);
    $subscription->expects($this->once())->method('expire')->willReturnSelf();
    $subscription->expects($this->once())->method('save');

    $this->storage
      ->expects($this->once())
      ->method('loadByProperties')
      ->with(['endpoint' => 'https://example.com/push'])
      ->willReturn([$subscription]);

    $result = $this->service->unsubscribe('https://example.com/push');

    $this->assertTrue($result);
  }

  /**
   * Tests unsubscribe() returns FALSE when no subscription found.
   *
   * @covers ::unsubscribe
   */
  public function testUnsubscribeReturnsFalseWhenNotFound(): void {
    $this->storage
      ->expects($this->once())
      ->method('loadByProperties')
      ->willReturn([]);

    $result = $this->service->unsubscribe('https://example.com/nonexistent');

    $this->assertFalse($result);
  }

  /**
   * Tests getVapidPublicKey() returns configured key.
   *
   * @covers ::getVapidPublicKey
   */
  public function testGetVapidPublicKeyReturnsConfiguredKey(): void {
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')
      ->with('vapid_public_key')
      ->willReturn('BIxSExy-test-vapid-public-key');

    $this->configFactory
      ->method('get')
      ->with('jaraba_pwa.settings')
      ->willReturn($config);

    $result = $this->service->getVapidPublicKey();

    $this->assertSame('BIxSExy-test-vapid-public-key', $result);
  }

  /**
   * Tests getVapidPublicKey() returns empty string when not configured.
   *
   * @covers ::getVapidPublicKey
   */
  public function testGetVapidPublicKeyReturnsEmptyWhenUnconfigured(): void {
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')
      ->with('vapid_public_key')
      ->willReturn(NULL);

    $this->configFactory
      ->method('get')
      ->with('jaraba_pwa.settings')
      ->willReturn($config);

    $result = $this->service->getVapidPublicKey();

    $this->assertSame('', $result);
  }

  /**
   * Tests sendNotification() returns FALSE when no subscriptions exist.
   *
   * @covers ::sendNotification
   */
  public function testSendNotificationReturnsFalseNoSubscriptions(): void {
    $this->storage
      ->method('loadByProperties')
      ->willReturn([]);

    $result = $this->service->sendNotification(999, 'Test', 'Body');

    $this->assertFalse($result);
  }

  /**
   * Tests sendToTopic() returns 0 when no matching subscriptions.
   *
   * @covers ::sendToTopic
   */
  public function testSendToTopicReturnsZeroNoSubscriptions(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->storage
      ->method('getQuery')
      ->willReturn($query);

    $result = $this->service->sendToTopic('nonexistent-topic', 'Test', 'Body');

    $this->assertSame(0, $result);
  }

  /**
   * Tests cleanupStaleSubscriptions() removes expired subscriptions.
   *
   * @covers ::cleanupStaleSubscriptions
   */
  public function testCleanupStaleSubscriptionsRemovesExpired(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([1, 2, 3]);

    $this->storage
      ->method('getQuery')
      ->willReturn($query);

    $sub1 = $this->createMock(PushSubscription::class);
    $sub2 = $this->createMock(PushSubscription::class);
    $sub3 = $this->createMock(PushSubscription::class);

    $this->storage
      ->method('loadMultiple')
      ->with([1, 2, 3])
      ->willReturn([$sub1, $sub2, $sub3]);

    $this->storage
      ->expects($this->once())
      ->method('delete')
      ->with([$sub1, $sub2, $sub3]);

    $result = $this->service->cleanupStaleSubscriptions();

    $this->assertSame(3, $result);
  }

  /**
   * Tests cleanupStaleSubscriptions() returns 0 when nothing to clean.
   *
   * @covers ::cleanupStaleSubscriptions
   */
  public function testCleanupStaleSubscriptionsReturnsZeroWhenClean(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->storage
      ->method('getQuery')
      ->willReturn($query);

    $result = $this->service->cleanupStaleSubscriptions();

    $this->assertSame(0, $result);
  }

}
