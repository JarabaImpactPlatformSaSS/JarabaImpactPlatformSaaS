<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_addons\Unit;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_addons\Entity\AddonSubscription;
use Drupal\jaraba_addons\Service\AddonSubscriptionService;
use Drupal\jaraba_addons\Service\TenantVerticalService;
use Drupal\jaraba_addons\Service\VerticalAddonBillingService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for VerticalAddonBillingService.
 *
 * @group jaraba_addons
 * @coversDefaultClass \Drupal\jaraba_addons\Service\VerticalAddonBillingService
 */
class VerticalAddonBillingServiceTest extends TestCase {

  protected VerticalAddonBillingService $service;
  protected AddonSubscriptionService $subscriptionService;
  protected TenantVerticalService $tenantVerticalService;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->subscriptionService = $this->createMock(AddonSubscriptionService::class);
    $this->tenantVerticalService = $this->createMock(TenantVerticalService::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new VerticalAddonBillingService(
      $this->subscriptionService,
      $this->tenantVerticalService,
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * @covers ::activateVerticalAddon
   */
  public function testActivateThrowsWhenAddonNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(999)->willReturn(NULL);
    $this->entityTypeManager->method('getStorage')->with('addon')->willReturn($storage);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('no existe');
    $this->service->activateVerticalAddon(999, 1);
  }

  /**
   * @covers ::activateVerticalAddon
   */
  public function testActivateThrowsWhenNotVerticalType(): void {
    $addon = $this->createFakeAddon('feature', '');
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(1)->willReturn($addon);
    $this->entityTypeManager->method('getStorage')->with('addon')->willReturn($storage);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('no es de tipo vertical');
    $this->service->activateVerticalAddon(1, 1);
  }

  /**
   * @covers ::activateVerticalAddon
   */
  public function testActivateThrowsWhenVerticalRefEmpty(): void {
    $addon = $this->createFakeAddon('vertical', '');
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(2)->willReturn($addon);
    $this->entityTypeManager->method('getStorage')->with('addon')->willReturn($storage);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('vertical_ref');
    $this->service->activateVerticalAddon(2, 1);
  }

  /**
   * @covers ::activateVerticalAddon
   */
  public function testActivateThrowsWhenAlreadyActive(): void {
    $addon = $this->createFakeAddon('vertical', 'formacion');
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(5)->willReturn($addon);
    $this->entityTypeManager->method('getStorage')->with('addon')->willReturn($storage);

    $this->tenantVerticalService->method('hasVertical')
      ->with(1, 'formacion')
      ->willReturn(TRUE);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('ya tiene el vertical');
    $this->service->activateVerticalAddon(5, 1);
  }

  /**
   * @covers ::activateVerticalAddon
   */
  public function testActivateSucceedsAndInvalidatesCache(): void {
    $addon = $this->createFakeAddon('vertical', 'agroconecta');
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(10)->willReturn($addon);
    $this->entityTypeManager->method('getStorage')->with('addon')->willReturn($storage);

    $this->tenantVerticalService->method('hasVertical')
      ->with(1, 'agroconecta')
      ->willReturn(FALSE);

    $subscription = $this->createMock(AddonSubscription::class);
    $subscription->method('id')->willReturn('42');

    $this->subscriptionService->expects($this->once())
      ->method('subscribe')
      ->with(10, 1, 'monthly')
      ->willReturn($subscription);

    $this->tenantVerticalService->expects($this->once())
      ->method('invalidateCache')
      ->with(1);

    $result = $this->service->activateVerticalAddon(10, 1);
    $this->assertSame(42, $result['subscription_id']);
    $this->assertSame('agroconecta', $result['vertical_ref']);
    $this->assertSame('active', $result['status']);
  }

  /**
   * @covers ::deactivateVerticalAddon
   */
  public function testDeactivateThrowsWhenSubscriptionNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(999)->willReturn(NULL);
    $this->entityTypeManager->method('getStorage')
      ->with('addon_subscription')
      ->willReturn($storage);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('no existe');
    $this->service->deactivateVerticalAddon(999, 1);
  }

  /**
   * @covers ::deactivateVerticalAddon
   */
  public function testDeactivateThrowsWhenWrongTenant(): void {
    $subscription = $this->createFakeSubscription(2);
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(50)->willReturn($subscription);
    $this->entityTypeManager->method('getStorage')
      ->with('addon_subscription')
      ->willReturn($storage);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('no pertenece');
    $this->service->deactivateVerticalAddon(50, 1);
  }

  /**
   * @covers ::deactivateVerticalAddon
   */
  public function testDeactivateSucceeds(): void {
    $subscription = $this->createFakeSubscription(1);
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(50)->willReturn($subscription);
    $this->entityTypeManager->method('getStorage')
      ->with('addon_subscription')
      ->willReturn($storage);

    $this->subscriptionService->expects($this->once())
      ->method('cancel')
      ->with(50);

    $this->tenantVerticalService->expects($this->once())
      ->method('invalidateCache')
      ->with(1);

    $result = $this->service->deactivateVerticalAddon(50, 1);
    $this->assertSame(50, $result['subscription_id']);
    $this->assertSame('cancelled', $result['status']);
  }

  /**
   * Creates a fake Addon object with typed field access.
   */
  private function createFakeAddon(string $addonType, string $verticalRef): object {
    return new class ($addonType, $verticalRef) {

      private string $addonType;
      private string $verticalRef;

      public function __construct(string $at, string $vr) {
        $this->addonType = $at;
        $this->verticalRef = $vr;
      }

      public function get(string $name): object {
        $value = match ($name) {
          'addon_type' => $this->addonType,
          'vertical_ref' => $this->verticalRef,
          default => '',
        };
        return new class ($value) {

          public ?string $value;

          public function __construct(string $v) {
            $this->value = $v;
          }

        };
      }

      public function label(): string {
        return 'Test Addon';
      }

    };
  }

  /**
   * Creates a fake AddonSubscription with typed tenant_id access.
   */
  private function createFakeSubscription(int $tenantId): object {
    return new class ($tenantId) {

      private int $tenantId;

      public function __construct(int $tid) {
        $this->tenantId = $tid;
      }

      public function get(string $name): object {
        return new class ($this->tenantId) {

          public ?int $target_id;

          public function __construct(int $tid) {
            $this->target_id = $tid;
          }

        };
      }

    };
  }

}
