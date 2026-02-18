<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_mobile\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_mobile\Entity\MobileDeviceInterface;
use Drupal\jaraba_mobile\Service\DeviceRegistryService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DeviceRegistryService.
 *
 * @coversDefaultClass \Drupal\jaraba_mobile\Service\DeviceRegistryService
 * @group jaraba_mobile
 */
class DeviceRegistryServiceTest extends TestCase {

  /**
   * The service under test.
   */
  protected DeviceRegistryService $service;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock tenant context service.
   */
  protected TenantContextService $tenantContext;

  /**
   * Mock current user proxy.
   */
  protected AccountProxyInterface $currentUser;

  /**
   * Mock entity storage for mobile_device.
   */
  protected EntityStorageInterface $deviceStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set up Drupal container for \Drupal::time().
    $container = new \Drupal\Core\DependencyInjection\ContainerBuilder();
    $timeMock = $this->createMock(\Drupal\Component\Datetime\TimeInterface::class);
    $timeMock->method('getRequestTime')->willReturn(time());
    $container->set('datetime.time', $timeMock);
    \Drupal::setContainer($container);

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->tenantContext = $this->createMock(TenantContextService::class);
    $this->currentUser = $this->createMock(AccountProxyInterface::class);
    $this->deviceStorage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('mobile_device')
      ->willReturn($this->deviceStorage);

    $this->currentUser
      ->method('id')
      ->willReturn(42);

    $this->tenantContext
      ->method('getCurrentTenantId')
      ->willReturn(7);

    $this->service = new DeviceRegistryService(
      $this->entityTypeManager,
      $this->tenantContext,
      $this->currentUser,
    );
  }

  /**
   * Tests register() creates a new device when none exists.
   *
   * @covers ::register
   */
  public function testRegisterCreatesDevice(): void {
    // No existing device with this token + user.
    $this->deviceStorage
      ->expects($this->once())
      ->method('loadByProperties')
      ->with([
        'device_token' => 'fcm-token-abc123',
        'user_id' => 42,
      ])
      ->willReturn([]);

    // Expect entity creation.
    $device = $this->createMock(MobileDeviceInterface::class);
    $device->method('id')->willReturn(1);
    $device->expects($this->once())->method('save');

    $this->deviceStorage
      ->expects($this->once())
      ->method('create')
      ->with($this->callback(function (array $values): bool {
        return $values['device_token'] === 'fcm-token-abc123'
          && $values['platform'] === 'android'
          && $values['user_id'] === 42
          && $values['tenant_id'] === 7
          && $values['is_active'] === TRUE
          && $values['os_version'] === '14'
          && $values['app_version'] === '2.1.0';
      }))
      ->willReturn($device);

    $result = $this->service->register('fcm-token-abc123', 'android', [
      'os_version' => '14',
      'app_version' => '2.1.0',
      'device_model' => 'Pixel 8',
    ]);

    $this->assertSame(1, (int) $result->id());
  }

  /**
   * Tests register() updates an existing device with same token + user.
   *
   * @covers ::register
   */
  public function testRegisterUpdatesExistingDevice(): void {
    $existingDevice = $this->createMock(MobileDeviceInterface::class);
    $existingDevice->method('id')->willReturn(99);
    $existingDevice->expects($this->atLeastOnce())->method('set');
    $existingDevice->expects($this->once())->method('save');

    $this->deviceStorage
      ->expects($this->once())
      ->method('loadByProperties')
      ->with([
        'device_token' => 'fcm-token-existing',
        'user_id' => 42,
      ])
      ->willReturn([$existingDevice]);

    // create() should NOT be called when updating.
    $this->deviceStorage
      ->expects($this->never())
      ->method('create');

    $result = $this->service->register('fcm-token-existing', 'ios', [
      'os_version' => '17.2',
    ]);

    $this->assertSame(99, (int) $result->id());
  }

  /**
   * Tests unregister() marks device as inactive.
   *
   * @covers ::unregister
   */
  public function testUnregisterMarksInactive(): void {
    $device = $this->createMock(MobileDeviceInterface::class);
    $device->expects($this->once())
      ->method('set')
      ->with('is_active', FALSE);
    $device->expects($this->once())->method('save');

    $this->deviceStorage
      ->expects($this->once())
      ->method('loadByProperties')
      ->with([
        'device_token' => 'fcm-token-to-remove',
        'user_id' => 42,
      ])
      ->willReturn([$device]);

    $result = $this->service->unregister('fcm-token-to-remove');

    $this->assertTrue($result);
  }

  /**
   * Tests unregister() returns FALSE when device not found.
   *
   * @covers ::unregister
   */
  public function testUnregisterReturnsFalseWhenNotFound(): void {
    $this->deviceStorage
      ->expects($this->once())
      ->method('loadByProperties')
      ->willReturn([]);

    $result = $this->service->unregister('nonexistent-token');

    $this->assertFalse($result);
  }

  /**
   * Tests getDevices() filters by tenant and user.
   *
   * @covers ::getDevices
   */
  public function testGetDevicesFiltersByTenant(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->with(FALSE)->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([10, 20]);

    $this->deviceStorage
      ->method('getQuery')
      ->willReturn($query);

    $device1 = $this->createMock(MobileDeviceInterface::class);
    $device2 = $this->createMock(MobileDeviceInterface::class);

    $this->deviceStorage
      ->method('loadMultiple')
      ->with([10, 20])
      ->willReturn([$device1, $device2]);

    // Verify that the query conditions include tenant_id.
    $conditionCalls = [];
    $query->expects($this->exactly(3))
      ->method('condition')
      ->willReturnCallback(function (string $field, $value) use ($query, &$conditionCalls) {
        $conditionCalls[] = ['field' => $field, 'value' => $value];
        return $query;
      });

    $result = $this->service->getDevices();

    $this->assertCount(2, $result);

    // Verify tenant_id was part of the conditions.
    $tenantCondition = array_filter($conditionCalls, function (array $call): bool {
      return $call['field'] === 'tenant_id';
    });
    $this->assertNotEmpty($tenantCondition, 'Query must include tenant_id condition.');
  }

  /**
   * Tests getDevices() returns empty array when no devices found.
   *
   * @covers ::getDevices
   */
  public function testGetDevicesReturnsEmptyArrayWhenNone(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->deviceStorage
      ->method('getQuery')
      ->willReturn($query);

    $result = $this->service->getDevices();

    $this->assertSame([], $result);
  }

  /**
   * Tests updateToken() returns TRUE when old token is found and updated.
   *
   * @covers ::updateToken
   */
  public function testUpdateTokenSuccess(): void {
    $device = $this->createMock(MobileDeviceInterface::class);
    $device->expects($this->atLeastOnce())->method('set');
    $device->expects($this->once())->method('save');

    $this->deviceStorage
      ->expects($this->once())
      ->method('loadByProperties')
      ->with([
        'device_token' => 'old-fcm-token',
        'user_id' => 42,
      ])
      ->willReturn([$device]);

    $result = $this->service->updateToken('old-fcm-token', 'new-fcm-token');

    $this->assertTrue($result);
  }

  /**
   * Tests updateToken() returns FALSE when old token not found.
   *
   * @covers ::updateToken
   */
  public function testUpdateTokenReturnsFalseWhenNotFound(): void {
    $this->deviceStorage
      ->expects($this->once())
      ->method('loadByProperties')
      ->willReturn([]);

    $result = $this->service->updateToken('nonexistent-token', 'new-token');

    $this->assertFalse($result);
  }

}
