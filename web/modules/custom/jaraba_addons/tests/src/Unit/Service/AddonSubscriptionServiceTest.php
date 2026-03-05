<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_addons\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_addons\Entity\AddonSubscription;
use Drupal\jaraba_addons\Service\AddonSubscriptionService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para AddonSubscriptionService.
 *
 * Verifica la logica de suscripcion, cancelacion, renovacion
 * y consultas de suscripciones a add-ons.
 *
 * @group jaraba_addons
 * @coversDefaultClass \Drupal\jaraba_addons\Service\AddonSubscriptionService
 */
class AddonSubscriptionServiceTest extends TestCase {

  protected AddonSubscriptionService $service;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    // tenantContext es opcional (@?) — lo pasamos como NULL.
    $this->service = new AddonSubscriptionService(
      $this->entityTypeManager,
      NULL,
      $this->logger,
    );
  }

  // =========================================================================
  // subscribe() — validaciones
  // =========================================================================

  /**
   * @covers ::subscribe
   */
  public function testSubscribeThrowsWhenAddonNotFound(): void {
    $addonStorage = $this->createMock(EntityStorageInterface::class);
    $addonStorage->method('load')->with(999)->willReturn(NULL);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(fn(string $type) => $addonStorage);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('no existe');
    $this->service->subscribe(999, 1);
  }

  /**
   * @covers ::subscribe
   */
  public function testSubscribeThrowsWhenAddonInactive(): void {
    $addon = $this->createFakeAddon(active: false, priceMonthly: 9.99);
    $addonStorage = $this->createMock(EntityStorageInterface::class);
    $addonStorage->method('load')->with(1)->willReturn($addon);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(fn(string $type) => $addonStorage);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('no est');
    $this->service->subscribe(1, 1);
  }

  /**
   * @covers ::subscribe
   */
  public function testSubscribeThrowsWhenDuplicateActiveSubscription(): void {
    $addon = $this->createFakeAddon(active: true, priceMonthly: 9.99);

    $addonStorage = $this->createMock(EntityStorageInterface::class);
    $addonStorage->method('load')->with(1)->willReturn($addon);

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    $query->method('execute')->willReturn(1);

    $subStorage = $this->createMock(EntityStorageInterface::class);
    $subStorage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $type) use ($addonStorage, $subStorage) {
        return $type === 'addon' ? $addonStorage : $subStorage;
      });

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('ya tiene');
    $this->service->subscribe(1, 1);
  }

  /**
   * @covers ::subscribe
   */
  public function testSubscribeCreatesSubscriptionWithMonthlyBilling(): void {
    $addon = $this->createFakeAddon(active: true, priceMonthly: 19.99);

    $addonStorage = $this->createMock(EntityStorageInterface::class);
    $addonStorage->method('load')->with(1)->willReturn($addon);

    // Query para verificar duplicados: devolver 0.
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    $query->method('execute')->willReturn(0);

    // Mock de la suscripcion creada.
    $subscription = $this->createMock(AddonSubscription::class);
    $subscription->method('id')->willReturn('42');

    $subStorage = $this->createMock(EntityStorageInterface::class);
    $subStorage->method('getQuery')->willReturn($query);
    $subStorage->method('create')
      ->with($this->callback(function (array $values) {
        return $values['addon_id'] === 1
          && $values['tenant_id'] === 5
          && $values['status'] === 'active'
          && $values['billing_cycle'] === 'monthly'
          && $values['price_paid'] === 19.99;
      }))
      ->willReturn($subscription);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $type) use ($addonStorage, $subStorage) {
        return $type === 'addon' ? $addonStorage : $subStorage;
      });

    $result = $this->service->subscribe(1, 5, 'monthly');
    $this->assertSame('42', $result->id());
  }

  /**
   * @covers ::subscribe
   */
  public function testSubscribeUsesYearlyPrice(): void {
    $addon = $this->createFakeAddon(active: true, priceMonthly: 19.99, priceYearly: 199.90);

    $addonStorage = $this->createMock(EntityStorageInterface::class);
    $addonStorage->method('load')->with(1)->willReturn($addon);

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    $query->method('execute')->willReturn(0);

    $subscription = $this->createMock(AddonSubscription::class);
    $subscription->method('id')->willReturn('43');

    $subStorage = $this->createMock(EntityStorageInterface::class);
    $subStorage->method('getQuery')->willReturn($query);
    $subStorage->method('create')
      ->with($this->callback(function (array $values) {
        return $values['billing_cycle'] === 'yearly'
          && $values['price_paid'] === 199.90;
      }))
      ->willReturn($subscription);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $type) use ($addonStorage, $subStorage) {
        return $type === 'addon' ? $addonStorage : $subStorage;
      });

    $result = $this->service->subscribe(1, 5, 'yearly');
    $this->assertSame('43', $result->id());
  }

  // =========================================================================
  // cancel()
  // =========================================================================

  /**
   * @covers ::cancel
   */
  public function testCancelReturnsNullWhenNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(999)->willReturn(NULL);

    $this->entityTypeManager->method('getStorage')
      ->with('addon_subscription')
      ->willReturn($storage);

    $this->assertNull($this->service->cancel(999));
  }

  /**
   * @covers ::cancel
   */
  public function testCancelSetsStatusToCancelled(): void {
    $subscription = $this->createMock(AddonSubscription::class);
    $subscription->expects($this->once())
      ->method('set')
      ->with('status', 'cancelled');
    $subscription->expects($this->once())
      ->method('save');

    // Mock para get('addon_id')->entity y get('tenant_id')->target_id.
    $addonRef = new class {
      public ?object $entity = null;
    };
    $tenantRef = new class {
      public ?int $target_id = 1;
    };

    $subscription->method('get')
      ->willReturnCallback(function (string $name) use ($addonRef, $tenantRef) {
        return $name === 'addon_id' ? $addonRef : $tenantRef;
      });

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(10)->willReturn($subscription);

    $this->entityTypeManager->method('getStorage')
      ->with('addon_subscription')
      ->willReturn($storage);

    $result = $this->service->cancel(10);
    $this->assertSame($subscription, $result);
  }

  // =========================================================================
  // isAddonActive()
  // =========================================================================

  /**
   * @covers ::isAddonActive
   */
  public function testIsAddonActiveReturnsTrueWhenActiveSubscriptionExists(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    $query->method('execute')->willReturn(1);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('addon_subscription')
      ->willReturn($storage);

    $this->assertTrue($this->service->isAddonActive(5, 1));
  }

  /**
   * @covers ::isAddonActive
   */
  public function testIsAddonActiveReturnsFalseWhenNoActiveSubscription(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    $query->method('execute')->willReturn(0);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('addon_subscription')
      ->willReturn($storage);

    $this->assertFalse($this->service->isAddonActive(5, 1));
  }

  // =========================================================================
  // getTenantSubscriptions()
  // =========================================================================

  /**
   * @covers ::getTenantSubscriptions
   */
  public function testGetTenantSubscriptionsReturnsEmptyWhenNoSubscriptions(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('addon_subscription')
      ->willReturn($storage);

    $this->assertSame([], $this->service->getTenantSubscriptions(1));
  }

  /**
   * @covers ::getTenantSubscriptions
   */
  public function testGetTenantSubscriptionsReturnsIndexedArray(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([10 => 10, 20 => 20]);

    $sub1 = $this->createMock(AddonSubscription::class);
    $sub2 = $this->createMock(AddonSubscription::class);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);
    $storage->method('loadMultiple')
      ->with([10 => 10, 20 => 20])
      ->willReturn([10 => $sub1, 20 => $sub2]);

    $this->entityTypeManager->method('getStorage')
      ->with('addon_subscription')
      ->willReturn($storage);

    $result = $this->service->getTenantSubscriptions(1);
    $this->assertCount(2, $result);
    // array_values reindexes: keys should be 0, 1.
    $this->assertSame($sub1, $result[0]);
    $this->assertSame($sub2, $result[1]);
  }

  // =========================================================================
  // Helpers
  // =========================================================================

  /**
   * Crea un fake addon con metodos tipados (PHP 8.4 safe).
   */
  private function createFakeAddon(bool $active, float $priceMonthly, float $priceYearly = 0): object {
    return new class ($active, $priceMonthly, $priceYearly) {

      private bool $active;
      private float $priceMonthly;
      private float $priceYearly;

      public function __construct(bool $a, float $pm, float $py) {
        $this->active = $a;
        $this->priceMonthly = $pm;
        $this->priceYearly = $py;
      }

      public function isActive(): bool {
        return $this->active;
      }

      public function label(): string {
        return 'Test Addon';
      }

      public function getPrice(string $billingCycle = 'monthly'): float {
        return $billingCycle === 'yearly' ? $this->priceYearly : $this->priceMonthly;
      }

    };
  }

}
