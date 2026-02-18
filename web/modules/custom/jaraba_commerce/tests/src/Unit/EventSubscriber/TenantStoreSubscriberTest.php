<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_commerce\Unit\EventSubscriber;

use Drupal\commerce_store\Entity\Store;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\jaraba_commerce\EventSubscriber\TenantStoreSubscriber;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for TenantStoreSubscriber.
 *
 * Covers store creation for tenants, error handling when Commerce Store
 * is not available, store type fallback, and tenant store retrieval.
 *
 * @coversDefaultClass \Drupal\jaraba_commerce\EventSubscriber\TenantStoreSubscriber
 * @group jaraba_commerce
 */
class TenantStoreSubscriberTest extends UnitTestCase {

  /**
   * The subscriber under test.
   */
  protected TenantStoreSubscriber $subscriber;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock logger.
   */
  protected LoggerChannelInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerChannelInterface::class);

    $this->subscriber = new TenantStoreSubscriber(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * Creates a mock tenant entity.
   *
   * @param int $id
   *   Tenant ID.
   * @param string $name
   *   Tenant name.
   * @param string|null $email
   *   Admin user email, or NULL for no admin user.
   * @param string $domain
   *   Tenant domain.
   *
   * @return object|\PHPUnit\Framework\MockObject\MockObject
   *   Mock tenant entity.
   */
  protected function createMockTenant(int $id, string $name, ?string $email = 'admin@test.com', string $domain = 'test.jaraba.io'): object {
    $tenant = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['id', 'getName', 'getAdminUser', 'getDomain'])
      ->getMock();

    $tenant->method('id')->willReturn($id);
    $tenant->method('getName')->willReturn($name);
    $tenant->method('getDomain')->willReturn($domain);

    if ($email !== NULL) {
      $adminUser = $this->getMockBuilder(\stdClass::class)
        ->addMethods(['getEmail'])
        ->getMock();
      $adminUser->method('getEmail')->willReturn($email);
      $tenant->method('getAdminUser')->willReturn($adminUser);
    }
    else {
      $tenant->method('getAdminUser')->willReturn(NULL);
    }

    return $tenant;
  }

  // -----------------------------------------------------------------------
  // getSubscribedEvents() tests
  // -----------------------------------------------------------------------

  /**
   * @covers ::getSubscribedEvents
   */
  public function testGetSubscribedEventsReturnsArray(): void {
    $events = TenantStoreSubscriber::getSubscribedEvents();

    $this->assertIsArray($events);
    // Currently returns empty array (events registered elsewhere).
    $this->assertEmpty($events);
  }

  // -----------------------------------------------------------------------
  // createStoreForTenant() tests
  // -----------------------------------------------------------------------

  /**
   * @covers ::createStoreForTenant
   */
  public function testCreateStoreForTenantReturnsNullWhenCommerceNotInstalled(): void {
    $this->entityTypeManager->method('hasDefinition')
      ->with('commerce_store')
      ->willReturn(FALSE);

    $this->logger->expects($this->once())
      ->method('warning')
      ->with($this->stringContains('Commerce Store no'));

    $tenant = $this->createMockTenant(1, 'Test Tenant');

    $result = $this->subscriber->createStoreForTenant($tenant);

    $this->assertNull($result);
  }

  /**
   * @covers ::createStoreForTenant
   */
  public function testCreateStoreForTenantReturnsNullWhenNoStoreTypeAvailable(): void {
    $this->entityTypeManager->method('hasDefinition')
      ->with('commerce_store')
      ->willReturn(TRUE);

    $storeTypeStorage = $this->createMock(EntityStorageInterface::class);
    $storeTypeStorage->method('load')->willReturn(NULL);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $entityType) use ($storeTypeStorage) {
        if ($entityType === 'commerce_store_type') {
          return $storeTypeStorage;
        }
        return $this->createMock(EntityStorageInterface::class);
      });

    $this->logger->expects($this->once())
      ->method('warning')
      ->with($this->stringContains('No hay tipos'));

    $tenant = $this->createMockTenant(1, 'Test Tenant');

    $result = $this->subscriber->createStoreForTenant($tenant);

    $this->assertNull($result);
  }

  /**
   * @covers ::createStoreForTenant
   */
  public function testCreateStoreForTenantCreatesStoreSuccessfully(): void {
    $this->entityTypeManager->method('hasDefinition')
      ->with('commerce_store')
      ->willReturn(TRUE);

    // Mock store type.
    $storeType = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['id'])
      ->getMock();
    $storeType->method('id')->willReturn('jaraba_store');

    $storeTypeStorage = $this->createMock(EntityStorageInterface::class);
    $storeTypeStorage->method('load')
      ->willReturnCallback(function (string $type) use ($storeType) {
        return $type === 'jaraba_store' ? $storeType : NULL;
      });

    // Mock store entity.
    $store = $this->createMock(Store::class);
    $store->method('id')->willReturn(42);
    $store->expects($this->once())->method('save');

    $storeStorage = $this->createMock(EntityStorageInterface::class);
    $storeStorage->expects($this->once())
      ->method('create')
      ->with($this->callback(function (array $values) {
        return $values['type'] === 'jaraba_store'
          && $values['name'] === 'Test Corp'
          && $values['mail'] === 'admin@corp.com'
          && $values['default_currency'] === 'EUR'
          && $values['field_tenant_id'] === 1;
      }))
      ->willReturn($store);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $entityType) use ($storeTypeStorage, $storeStorage) {
        return match ($entityType) {
          'commerce_store_type' => $storeTypeStorage,
          'commerce_store' => $storeStorage,
          default => $this->createMock(EntityStorageInterface::class),
        };
      });

    $this->logger->expects($this->once())
      ->method('info')
      ->with(
        $this->stringContains('Commerce Store creada'),
        $this->callback(fn(array $ctx) => $ctx['@tenant'] === 'Test Corp' && $ctx['@store_id'] === 42)
      );

    $tenant = $this->createMockTenant(1, 'Test Corp', 'admin@corp.com');

    $result = $this->subscriber->createStoreForTenant($tenant);

    $this->assertNotNull($result);
    $this->assertEquals(42, $result->id());
  }

  /**
   * @covers ::createStoreForTenant
   */
  public function testCreateStoreForTenantUsesDefaultEmailWhenNoAdminUser(): void {
    $this->entityTypeManager->method('hasDefinition')
      ->with('commerce_store')
      ->willReturn(TRUE);

    $storeType = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['id'])
      ->getMock();
    $storeType->method('id')->willReturn('online');

    $storeTypeStorage = $this->createMock(EntityStorageInterface::class);
    $storeTypeStorage->method('load')
      ->willReturnCallback(function (string $type) use ($storeType) {
        return $type === 'online' ? $storeType : NULL;
      });

    $store = $this->createMock(Store::class);
    $store->method('id')->willReturn(10);
    $store->method('save')->willReturn(1);

    $storeStorage = $this->createMock(EntityStorageInterface::class);
    $storeStorage->expects($this->once())
      ->method('create')
      ->with($this->callback(function (array $values) {
        return $values['mail'] === 'info@jaraba-impact.com'
          && $values['type'] === 'online';
      }))
      ->willReturn($store);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $entityType) use ($storeTypeStorage, $storeStorage) {
        return match ($entityType) {
          'commerce_store_type' => $storeTypeStorage,
          'commerce_store' => $storeStorage,
          default => $this->createMock(EntityStorageInterface::class),
        };
      });

    // Tenant without admin user.
    $tenant = $this->createMockTenant(2, 'No Admin Tenant', NULL);

    $result = $this->subscriber->createStoreForTenant($tenant);

    $this->assertNotNull($result);
  }

  /**
   * @covers ::createStoreForTenant
   */
  public function testCreateStoreForTenantReturnsNullOnException(): void {
    $this->entityTypeManager->method('hasDefinition')
      ->with('commerce_store')
      ->willReturn(TRUE);

    $this->entityTypeManager->method('getStorage')
      ->willThrowException(new \RuntimeException('DB error'));

    $this->logger->expects($this->once())
      ->method('error')
      ->with(
        $this->stringContains('Error creando Commerce Store'),
        $this->callback(fn(array $ctx) => str_contains($ctx['@error'], 'DB error'))
      );

    $tenant = $this->createMockTenant(1, 'Error Tenant');

    $result = $this->subscriber->createStoreForTenant($tenant);

    $this->assertNull($result);
  }

  // -----------------------------------------------------------------------
  // getStoreForTenant() tests
  // -----------------------------------------------------------------------

  /**
   * @covers ::getStoreForTenant
   */
  public function testGetStoreForTenantReturnsStoreWhenExists(): void {
    $store = $this->createMock(Store::class);
    $store->method('id')->willReturn(42);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')
      ->with(['field_tenant_id' => 5])
      ->willReturn([$store]);

    $this->entityTypeManager->method('getStorage')
      ->with('commerce_store')
      ->willReturn($storage);

    $result = $this->subscriber->getStoreForTenant(5);

    $this->assertNotNull($result);
    $this->assertEquals(42, $result->id());
  }

  /**
   * @covers ::getStoreForTenant
   */
  public function testGetStoreForTenantReturnsNullWhenNotExists(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')
      ->with(['field_tenant_id' => 999])
      ->willReturn([]);

    $this->entityTypeManager->method('getStorage')
      ->with('commerce_store')
      ->willReturn($storage);

    $result = $this->subscriber->getStoreForTenant(999);

    $this->assertNull($result);
  }

  /**
   * @covers ::getStoreForTenant
   */
  public function testGetStoreForTenantReturnsNullOnException(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')
      ->willThrowException(new \RuntimeException('Storage error'));

    $this->entityTypeManager->method('getStorage')
      ->with('commerce_store')
      ->willReturn($storage);

    $result = $this->subscriber->getStoreForTenant(1);

    $this->assertNull($result);
  }

}
