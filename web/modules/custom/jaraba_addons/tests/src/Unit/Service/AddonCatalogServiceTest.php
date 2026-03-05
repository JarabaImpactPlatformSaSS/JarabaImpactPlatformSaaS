<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_addons\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_addons\Service\AddonCatalogService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para AddonCatalogService.
 *
 * Verifica la logica de consulta del catalogo de add-ons:
 * listado completo, filtrado por tipo y consulta de precios.
 *
 * @group jaraba_addons
 * @coversDefaultClass \Drupal\jaraba_addons\Service\AddonCatalogService
 */
class AddonCatalogServiceTest extends TestCase {

  protected AddonCatalogService $service;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    // tenantContext es opcional — NULL.
    $this->service = new AddonCatalogService(
      $this->entityTypeManager,
      NULL,
      $this->logger,
    );
  }

  // =========================================================================
  // getAvailableAddons()
  // =========================================================================

  /**
   * @covers ::getAvailableAddons
   */
  public function testGetAvailableAddonsReturnsEmptyWhenNone(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('addon')
      ->willReturn($storage);

    $this->assertSame([], $this->service->getAvailableAddons());
  }

  /**
   * @covers ::getAvailableAddons
   */
  public function testGetAvailableAddonsReturnsReindexedArray(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([5 => 5, 10 => 10]);

    $addon1 = new class { public function id(): int { return 5; } };
    $addon2 = new class { public function id(): int { return 10; } };

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);
    $storage->method('loadMultiple')
      ->with([5 => 5, 10 => 10])
      ->willReturn([5 => $addon1, 10 => $addon2]);

    $this->entityTypeManager->method('getStorage')
      ->with('addon')
      ->willReturn($storage);

    $result = $this->service->getAvailableAddons();
    $this->assertCount(2, $result);
    // array_values reindexes: keys 0, 1.
    $this->assertSame($addon1, $result[0]);
    $this->assertSame($addon2, $result[1]);
  }

  // =========================================================================
  // getAddonsByType()
  // =========================================================================

  /**
   * @covers ::getAddonsByType
   */
  public function testGetAddonsByTypeReturnsFilteredAddons(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([1 => 1]);

    $addon = new class { public function id(): int { return 1; } };

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);
    $storage->method('loadMultiple')
      ->with([1 => 1])
      ->willReturn([1 => $addon]);

    $this->entityTypeManager->method('getStorage')
      ->with('addon')
      ->willReturn($storage);

    $result = $this->service->getAddonsByType('vertical');
    $this->assertCount(1, $result);
    $this->assertSame($addon, $result[0]);
  }

  /**
   * @covers ::getAddonsByType
   */
  public function testGetAddonsByTypeReturnsEmptyForUnknownType(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('addon')
      ->willReturn($storage);

    $this->assertSame([], $this->service->getAddonsByType('nonexistent'));
  }

  // =========================================================================
  // getAddonPrice()
  // =========================================================================

  /**
   * @covers ::getAddonPrice
   */
  public function testGetAddonPriceReturnsNullWhenNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(999)->willReturn(NULL);

    $this->entityTypeManager->method('getStorage')
      ->with('addon')
      ->willReturn($storage);

    $this->assertNull($this->service->getAddonPrice(999));
  }

  /**
   * @covers ::getAddonPrice
   */
  public function testGetAddonPriceReturnsNullWhenInactive(): void {
    $addon = $this->createFakeAddonForPrice(active: false, monthly: 10.0, yearly: 100.0);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(1)->willReturn($addon);

    $this->entityTypeManager->method('getStorage')
      ->with('addon')
      ->willReturn($storage);

    $this->assertNull($this->service->getAddonPrice(1));
  }

  /**
   * @covers ::getAddonPrice
   */
  public function testGetAddonPriceReturnsMonthlyByDefault(): void {
    $addon = $this->createFakeAddonForPrice(active: true, monthly: 29.99, yearly: 299.90);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(1)->willReturn($addon);

    $this->entityTypeManager->method('getStorage')
      ->with('addon')
      ->willReturn($storage);

    $this->assertSame(29.99, $this->service->getAddonPrice(1));
  }

  /**
   * @covers ::getAddonPrice
   */
  public function testGetAddonPriceReturnsYearlyWhenSpecified(): void {
    $addon = $this->createFakeAddonForPrice(active: true, monthly: 29.99, yearly: 299.90);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(1)->willReturn($addon);

    $this->entityTypeManager->method('getStorage')
      ->with('addon')
      ->willReturn($storage);

    $this->assertSame(299.90, $this->service->getAddonPrice(1, 'yearly'));
  }

  // =========================================================================
  // Helpers
  // =========================================================================

  /**
   * Crea un fake addon con isActive() y getPrice() — PHP 8.4 safe.
   */
  private function createFakeAddonForPrice(bool $active, float $monthly, float $yearly): object {
    return new class ($active, $monthly, $yearly) {

      private bool $active;
      private float $monthly;
      private float $yearly;

      public function __construct(bool $a, float $m, float $y) {
        $this->active = $a;
        $this->monthly = $m;
        $this->yearly = $y;
      }

      public function isActive(): bool {
        return $this->active;
      }

      public function getPrice(string $billingCycle = 'monthly'): float {
        return $billingCycle === 'yearly' ? $this->yearly : $this->monthly;
      }

    };
  }

}
