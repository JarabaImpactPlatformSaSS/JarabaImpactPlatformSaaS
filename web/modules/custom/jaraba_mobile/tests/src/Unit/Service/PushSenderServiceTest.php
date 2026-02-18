<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_mobile\Unit\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_mobile\Entity\MobileDeviceInterface;
use Drupal\jaraba_mobile\Entity\PushNotificationInterface;
use Drupal\jaraba_mobile\Service\PushSenderService;
use GuzzleHttp\ClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for PushSenderService.
 *
 * @coversDefaultClass \Drupal\jaraba_mobile\Service\PushSenderService
 * @group jaraba_mobile
 */
class PushSenderServiceTest extends TestCase {

  /**
   * The service under test.
   */
  protected PushSenderService $service;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock tenant context service.
   */
  protected TenantContextService $tenantContext;

  /**
   * Mock HTTP client.
   */
  protected ClientInterface $httpClient;

  /**
   * Mock config factory.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Mock logger.
   */
  protected LoggerInterface $logger;

  /**
   * Mock notification entity storage.
   */
  protected EntityStorageInterface $notificationStorage;

  /**
   * Mock device entity storage.
   */
  protected EntityStorageInterface $deviceStorage;

  /**
   * Mock config.
   */
  protected ImmutableConfig $config;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->tenantContext = $this->createMock(TenantContextService::class);
    $this->httpClient = $this->createMock(ClientInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->notificationStorage = $this->createMock(EntityStorageInterface::class);
    $this->deviceStorage = $this->createMock(EntityStorageInterface::class);
    $this->config = $this->createMock(ImmutableConfig::class);

    // Storage routing by entity type.
    $this->entityTypeManager
      ->method('getStorage')
      ->willReturnCallback(function (string $entityType) {
        return match ($entityType) {
          'push_notification' => $this->notificationStorage,
          'mobile_device' => $this->deviceStorage,
          default => throw new \InvalidArgumentException("Unknown entity type: {$entityType}"),
        };
      });

    $this->tenantContext
      ->method('getCurrentTenantId')
      ->willReturn(7);

    $this->configFactory
      ->method('get')
      ->with('jaraba_mobile.settings')
      ->willReturn($this->config);

    // Set up the Drupal container with datetime.time service,
    // required by PushSenderService::send() via \Drupal::time().
    $time = $this->createMock(TimeInterface::class);
    $time->method('getRequestTime')->willReturn(1700000000);
    $container = new ContainerBuilder();
    $container->set('datetime.time', $time);
    \Drupal::setContainer($container);

    $this->service = new PushSenderService(
      $this->entityTypeManager,
      $this->tenantContext,
      $this->httpClient,
      $this->configFactory,
      $this->logger,
    );
  }

  /**
   * Tests send() creates a notification entity and calls FCM.
   *
   * @covers ::send
   */
  public function testSendCreatesNotificationAndCallsFcm(): void {
    // Channel config: general enabled, unlimited.
    $this->config
      ->method('get')
      ->willReturnCallback(function (string $key) {
        return match ($key) {
          'push_channels.general' => ['enabled' => TRUE, 'max_per_day' => 0],
          'fcm_project_id' => 'test-project-id',
          'fcm_server_key' => 'test-server-key',
          default => NULL,
        };
      });

    // Device query returns one device.
    $deviceQuery = $this->createMock(QueryInterface::class);
    $deviceQuery->method('accessCheck')->willReturnSelf();
    $deviceQuery->method('condition')->willReturnSelf();
    $deviceQuery->method('execute')->willReturn([100]);

    $this->deviceStorage
      ->method('getQuery')
      ->willReturn($deviceQuery);

    $deviceEntity = $this->createMock(MobileDeviceInterface::class);
    $deviceTokenField = new \stdClass();
    $deviceTokenField->value = 'fcm-device-token-xyz';
    $deviceEntity->method('get')
      ->with('device_token')
      ->willReturn($deviceTokenField);

    $this->deviceStorage
      ->method('loadMultiple')
      ->with([100])
      ->willReturn([$deviceEntity]);

    // Mock FCM HTTP response.
    $responseBody = $this->createMock(StreamInterface::class);
    $responseBody->method('__toString')->willReturn('{"name":"projects/test/messages/123"}');
    $response = $this->createMock(ResponseInterface::class);
    $response->method('getStatusCode')->willReturn(200);
    $response->method('getBody')->willReturn($responseBody);

    $this->httpClient
      ->expects($this->once())
      ->method('request')
      ->with(
        'POST',
        'https://fcm.googleapis.com/v1/projects/test-project-id/messages:send',
        $this->isType('array')
      )
      ->willReturn($response);

    // Notification entity creation.
    $notification = $this->createMock(PushNotificationInterface::class);
    $notification->method('id')->willReturn(50);
    $statusField = new \stdClass();
    $statusField->value = 'sent';
    $channelField = new \stdClass();
    $channelField->value = 'general';
    $notification->method('get')
      ->willReturnCallback(function (string $field) use ($statusField, $channelField) {
        return match ($field) {
          'status' => $statusField,
          'channel' => $channelField,
          default => new \stdClass(),
        };
      });
    $notification->expects($this->once())->method('save');

    $this->notificationStorage
      ->expects($this->once())
      ->method('create')
      ->with($this->callback(function (array $values): bool {
        return $values['tenant_id'] === 7
          && $values['recipient_id'] === 42
          && $values['title'] === 'Test Title'
          && $values['body'] === 'Test Body'
          && $values['channel'] === 'general'
          && $values['status'] === 'sent';
      }))
      ->willReturn($notification);

    $result = $this->service->send(42, 'Test Title', 'Test Body', 'general');

    $this->assertSame(50, (int) $result->id());
  }

  /**
   * Tests sendBatch() creates multiple notifications.
   *
   * @covers ::sendBatch
   */
  public function testSendBatchCreatesMultipleNotifications(): void {
    // Channel config: general enabled, unlimited.
    $this->config
      ->method('get')
      ->willReturnCallback(function (string $key) {
        return match ($key) {
          'push_channels.general' => ['enabled' => TRUE, 'max_per_day' => 0],
          'fcm_project_id' => 'test-project-id',
          'fcm_server_key' => 'test-server-key',
          default => NULL,
        };
      });

    // Device queries return no devices (status=no_devices for simplicity).
    $deviceQuery = $this->createMock(QueryInterface::class);
    $deviceQuery->method('accessCheck')->willReturnSelf();
    $deviceQuery->method('condition')->willReturnSelf();
    $deviceQuery->method('execute')->willReturn([]);

    $this->deviceStorage
      ->method('getQuery')
      ->willReturn($deviceQuery);

    // Expect 3 notification entities created (one per recipient).
    $createCount = 0;
    $this->notificationStorage
      ->method('create')
      ->willReturnCallback(function () use (&$createCount) {
        $createCount++;
        $notification = $this->createMock(PushNotificationInterface::class);
        $notification->method('id')->willReturn($createCount);
        $notification->method('save')->willReturn(1);
        $statusField = new \stdClass();
        $statusField->value = 'no_devices';
        $notification->method('get')->willReturn($statusField);
        return $notification;
      });

    $result = $this->service->sendBatch([10, 20, 30], 'Batch Title', 'Batch Body');

    $this->assertCount(3, $result);
    $this->assertSame(3, $createCount);
  }

  /**
   * Tests channel rate limit enforcement blocks sending.
   *
   * @covers ::send
   */
  public function testChannelLimitEnforced(): void {
    // Channel config: jobs has max 2 per day.
    $this->config
      ->method('get')
      ->willReturnCallback(function (string $key) {
        return match ($key) {
          'push_channels.jobs' => ['enabled' => TRUE, 'max_per_day' => 2],
          default => NULL,
        };
      });

    // Count query returns 2 (at limit).
    $countQuery = $this->createMock(QueryInterface::class);
    $countQuery->method('accessCheck')->willReturnSelf();
    $countQuery->method('condition')->willReturnSelf();
    $countQuery->method('count')->willReturnSelf();
    $countQuery->method('execute')->willReturn(2);

    $this->notificationStorage
      ->method('getQuery')
      ->willReturn($countQuery);

    // Notification should be created with 'rate_limited' status.
    $notification = $this->createMock(PushNotificationInterface::class);
    $notification->method('id')->willReturn(77);
    $statusField = new \stdClass();
    $statusField->value = 'rate_limited';
    $notification->method('get')
      ->willReturnCallback(function (string $field) use ($statusField) {
        return match ($field) {
          'status' => $statusField,
          default => new \stdClass(),
        };
      });
    $notification->expects($this->once())->method('save');

    $this->notificationStorage
      ->expects($this->once())
      ->method('create')
      ->with($this->callback(function (array $values): bool {
        return $values['status'] === 'rate_limited' && $values['channel'] === 'jobs';
      }))
      ->willReturn($notification);

    // FCM should NOT be called.
    $this->httpClient
      ->expects($this->never())
      ->method('request');

    $result = $this->service->send(42, 'Job Alert', 'New job posted', 'jobs');

    $this->assertSame('rate_limited', $result->get('status')->value);
  }

  /**
   * Tests sendToChannel() finds all devices and sends.
   *
   * @covers ::sendToChannel
   */
  public function testSendToChannelFindsAllDevices(): void {
    // Channel config: alerts enabled, unlimited.
    $this->config
      ->method('get')
      ->willReturnCallback(function (string $key) {
        return match ($key) {
          'push_channels.alerts' => ['enabled' => TRUE, 'max_per_day' => 0],
          'fcm_project_id' => 'test-project-id',
          'fcm_server_key' => 'test-server-key',
          default => NULL,
        };
      });

    // Device query for sendToChannel — returns 2 devices with different users.
    $channelDeviceQuery = $this->createMock(QueryInterface::class);
    $channelDeviceQuery->method('accessCheck')->willReturnSelf();
    $channelDeviceQuery->method('condition')->willReturnSelf();
    $channelDeviceQuery->method('execute')->willReturn([1, 2]);

    // Per-user device queries return empty (no_devices status).
    $userDeviceQuery = $this->createMock(QueryInterface::class);
    $userDeviceQuery->method('accessCheck')->willReturnSelf();
    $userDeviceQuery->method('condition')->willReturnSelf();
    $userDeviceQuery->method('execute')->willReturn([]);

    $queryCallCount = 0;
    $this->deviceStorage
      ->method('getQuery')
      ->willReturnCallback(function () use ($channelDeviceQuery, $userDeviceQuery, &$queryCallCount) {
        $queryCallCount++;
        // First call is from sendToChannel, subsequent from send().
        return $queryCallCount === 1 ? $channelDeviceQuery : $userDeviceQuery;
      });

    $device1 = $this->createMock(MobileDeviceInterface::class);
    $userIdField1 = new \stdClass();
    $userIdField1->value = '10';
    $device1->method('get')
      ->with('user_id')
      ->willReturn($userIdField1);

    $device2 = $this->createMock(MobileDeviceInterface::class);
    $userIdField2 = new \stdClass();
    $userIdField2->value = '20';
    $device2->method('get')
      ->with('user_id')
      ->willReturn($userIdField2);

    $loadMultipleCallCount = 0;
    $this->deviceStorage
      ->method('loadMultiple')
      ->willReturnCallback(function () use ($device1, $device2, &$loadMultipleCallCount) {
        $loadMultipleCallCount++;
        return $loadMultipleCallCount === 1 ? [$device1, $device2] : [];
      });

    // Each send() call creates a notification.
    $this->notificationStorage
      ->method('create')
      ->willReturnCallback(function () {
        $notification = $this->createMock(PushNotificationInterface::class);
        $notification->method('id')->willReturn(1);
        $notification->method('save')->willReturn(1);
        $statusField = new \stdClass();
        $statusField->value = 'no_devices';
        $notification->method('get')->willReturn($statusField);
        return $notification;
      });

    // no_devices status means 0 "sent" count.
    $result = $this->service->sendToChannel('alerts', 'Alert!', 'System alert message');

    // Both users got notifications but with no_devices status, so sent=0.
    $this->assertSame(0, $result);
  }

  /**
   * Tests sendToChannel() returns 0 when no devices exist.
   *
   * @covers ::sendToChannel
   */
  public function testSendToChannelReturnsZeroWhenNoDevices(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->deviceStorage
      ->method('getQuery')
      ->willReturn($query);

    $result = $this->service->sendToChannel('general', 'Test', 'Test body');

    $this->assertSame(0, $result);
  }

  /**
   * Tests send() handles FCM exception gracefully.
   *
   * @covers ::send
   */
  public function testSendHandlesFcmException(): void {
    // Channel config: general enabled, unlimited.
    $this->config
      ->method('get')
      ->willReturnCallback(function (string $key) {
        return match ($key) {
          'push_channels.general' => ['enabled' => TRUE, 'max_per_day' => 0],
          'fcm_project_id' => 'test-project-id',
          'fcm_server_key' => 'test-server-key',
          default => NULL,
        };
      });

    // Device query returns one device.
    $deviceQuery = $this->createMock(QueryInterface::class);
    $deviceQuery->method('accessCheck')->willReturnSelf();
    $deviceQuery->method('condition')->willReturnSelf();
    $deviceQuery->method('execute')->willReturn([100]);

    $this->deviceStorage
      ->method('getQuery')
      ->willReturn($deviceQuery);

    $deviceEntity = $this->createMock(MobileDeviceInterface::class);
    $deviceTokenField = new \stdClass();
    $deviceTokenField->value = 'fcm-token-fail';
    $deviceEntity->method('get')
      ->with('device_token')
      ->willReturn($deviceTokenField);

    $this->deviceStorage
      ->method('loadMultiple')
      ->willReturn([$deviceEntity]);

    // FCM call throws exception — caught per-token inside sendToFcm(),
    // so the exception does NOT propagate to send(). The status ends up
    // 'sent' (notification processed) with error details in fcm_response.
    $this->httpClient
      ->method('request')
      ->willThrowException(new \RuntimeException('Connection timeout'));

    // sendToFcm logs per-token failures as warnings, not errors.
    $this->logger
      ->expects($this->atLeastOnce())
      ->method('warning');

    // Notification is created with 'sent' status because sendToFcm
    // catches per-token exceptions internally and returns normally.
    $notification = $this->createMock(PushNotificationInterface::class);
    $notification->method('id')->willReturn(88);
    $statusField = new \stdClass();
    $statusField->value = 'sent';
    $notification->method('get')->willReturn($statusField);
    $notification->method('save')->willReturn(1);

    $this->notificationStorage
      ->expects($this->once())
      ->method('create')
      ->with($this->callback(function (array $values): bool {
        return $values['status'] === 'sent'
          && str_contains($values['fcm_response'], 'Connection timeout');
      }))
      ->willReturn($notification);

    $result = $this->service->send(42, 'Test', 'Body', 'general');

    $this->assertSame('sent', $result->get('status')->value);
  }

}
