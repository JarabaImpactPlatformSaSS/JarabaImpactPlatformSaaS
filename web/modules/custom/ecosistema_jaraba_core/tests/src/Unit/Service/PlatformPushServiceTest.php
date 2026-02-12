<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\ecosistema_jaraba_core\Entity\PushSubscription;
use Drupal\ecosistema_jaraba_core\Service\PlatformPushService;
use GuzzleHttp\ClientInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for PlatformPushService.
 *
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\PlatformPushService
 * @group ecosistema_jaraba_core
 */
class PlatformPushServiceTest extends TestCase {

  /**
   * The service under test.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\PlatformPushService
   */
  protected PlatformPushService $service;

  /**
   * Mock entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityTypeManagerInterface|MockObject $entityTypeManager;

  /**
   * Mock HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected ClientInterface|MockObject $httpClient;

  /**
   * Mock logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected LoggerInterface|MockObject $logger;

  /**
   * Mock config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected ConfigFactoryInterface|MockObject $configFactory;

  /**
   * Mock entity storage for push_subscription.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityStorageInterface|MockObject $storage;

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
   * Tests that subscribe() creates a new PushSubscription entity.
   *
   * @covers ::subscribe
   */
  public function testSubscribeCreatesEntity(): void {
    $userId = 42;
    $subscriptionData = [
      'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
      'keys' => [
        'auth' => 'test-auth-key',
        'p256dh' => 'test-p256dh-key',
      ],
      'user_agent' => 'Mozilla/5.0',
      'tenant_id' => 7,
    ];

    // No existing subscription found.
    $this->storage
      ->expects($this->once())
      ->method('loadByProperties')
      ->with([
        'user_id' => $userId,
        'endpoint' => $subscriptionData['endpoint'],
      ])
      ->willReturn([]);

    // Expect create() to be called with the correct values.
    $mockSubscription = $this->createMock(PushSubscription::class);
    $mockSubscription->expects($this->once())->method('save');

    $this->storage
      ->expects($this->once())
      ->method('create')
      ->with($this->callback(function (array $values) use ($userId, $subscriptionData) {
        return $values['user_id'] === $userId
          && $values['endpoint'] === $subscriptionData['endpoint']
          && $values['auth_key'] === 'test-auth-key'
          && $values['p256dh_key'] === 'test-p256dh-key'
          && $values['user_agent'] === 'Mozilla/5.0'
          && $values['active'] === TRUE
          && $values['tenant_id'] === 7;
      }))
      ->willReturn($mockSubscription);

    $result = $this->service->subscribe($userId, $subscriptionData);

    $this->assertSame($mockSubscription, $result);
  }

  /**
   * Tests that unsubscribe() deactivates a found subscription.
   *
   * @covers ::unsubscribe
   */
  public function testUnsubscribeDeactivatesSubscription(): void {
    $userId = 42;
    $endpoint = 'https://fcm.googleapis.com/fcm/send/abc123';

    $mockSubscription = $this->createMock(PushSubscription::class);
    $mockSubscription->expects($this->once())->method('deactivate');
    $mockSubscription->expects($this->once())->method('save');

    $this->storage
      ->expects($this->once())
      ->method('loadByProperties')
      ->with([
        'user_id' => $userId,
        'endpoint' => $endpoint,
      ])
      ->willReturn([$mockSubscription]);

    $result = $this->service->unsubscribe($userId, $endpoint);

    $this->assertTrue($result);
  }

  /**
   * Tests that unsubscribe() returns FALSE for an unknown endpoint.
   *
   * @covers ::unsubscribe
   */
  public function testUnsubscribeReturnsFalseForUnknown(): void {
    $userId = 42;
    $endpoint = 'https://push.example.com/unknown-endpoint';

    $this->storage
      ->expects($this->once())
      ->method('loadByProperties')
      ->with([
        'user_id' => $userId,
        'endpoint' => $endpoint,
      ])
      ->willReturn([]);

    $this->logger
      ->expects($this->once())
      ->method('warning');

    $result = $this->service->unsubscribe($userId, $endpoint);

    $this->assertFalse($result);
  }

  /**
   * Tests that sendToUser() returns 0 when the user has no active subscriptions.
   *
   * @covers ::sendToUser
   */
  public function testSendToUserReturnsZeroForNoSubscriptions(): void {
    $userId = 99;

    $this->storage
      ->expects($this->once())
      ->method('loadByProperties')
      ->with([
        'user_id' => $userId,
        'active' => TRUE,
      ])
      ->willReturn([]);

    $result = $this->service->sendToUser($userId, 'Test Title', 'Test Body');

    $this->assertSame(0, $result);
  }

  /**
   * Tests that getVapidKeys() reads keys from config.
   *
   * @covers ::getVapidKeys
   */
  public function testGetVapidKeysReadsFromConfig(): void {
    $mockConfig = $this->createMock(ImmutableConfig::class);
    $mockConfig
      ->method('get')
      ->willReturnMap([
        ['vapid_public_key', 'BPublicKeyBase64Encoded'],
        ['vapid_private_key', 'PrivateKeyBase64Encoded'],
        ['vapid_subject', 'mailto:test@example.com'],
      ]);

    $this->configFactory
      ->method('get')
      ->with('ecosistema_jaraba_core.push_settings')
      ->willReturn($mockConfig);

    $keys = $this->service->getVapidKeys();

    $this->assertSame('BPublicKeyBase64Encoded', $keys['public_key']);
    $this->assertSame('PrivateKeyBase64Encoded', $keys['private_key']);
    $this->assertSame('mailto:test@example.com', $keys['subject']);
  }

  /**
   * Tests that cleanupStaleSubscriptions() removes inactive subscriptions.
   *
   * @covers ::cleanupStaleSubscriptions
   */
  public function testCleanupStaleSubscriptionsRemovesInactive(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([10, 20, 30]);

    $this->storage
      ->method('getQuery')
      ->willReturn($query);

    $sub1 = $this->createMock(PushSubscription::class);
    $sub2 = $this->createMock(PushSubscription::class);
    $sub3 = $this->createMock(PushSubscription::class);

    $this->storage
      ->method('loadMultiple')
      ->with([10, 20, 30])
      ->willReturn([$sub1, $sub2, $sub3]);

    $this->storage
      ->expects($this->once())
      ->method('delete')
      ->with([$sub1, $sub2, $sub3]);

    $this->logger
      ->expects($this->once())
      ->method('info');

    $result = $this->service->cleanupStaleSubscriptions();

    $this->assertSame(3, $result);
  }

}
