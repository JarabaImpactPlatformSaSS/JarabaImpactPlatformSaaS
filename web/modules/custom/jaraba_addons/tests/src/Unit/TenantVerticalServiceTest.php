<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_addons\Unit;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_addons\Service\TenantVerticalService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for TenantVerticalService.
 *
 * @group jaraba_addons
 * @coversDefaultClass \Drupal\jaraba_addons\Service\TenantVerticalService
 */
class TenantVerticalServiceTest extends TestCase {

  protected TenantVerticalService $service;
  protected EntityTypeManagerInterface&MockObject $entityTypeManager;
  protected LoggerInterface&MockObject $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->service = new TenantVerticalService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * @covers ::hasVertical
   */
  public function testHasVerticalReturnsFalseWhenTenantNotFound(): void {
    $tenantStorage = $this->createMock(EntityStorageInterface::class);
    $tenantStorage->method('load')->with(99)->willReturn(NULL);

    $subStorage = $this->createMock(EntityStorageInterface::class);
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);
    $subStorage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $type) use ($tenantStorage, $subStorage) {
        return $type === 'tenant' ? $tenantStorage : $subStorage;
      });

    $this->assertFalse($this->service->hasVertical(99, 'formacion'));
  }

  /**
   * @covers ::hasVertical
   */
  public function testHasVerticalReturnsTrueForPrimaryVertical(): void {
    $tenant = $this->createFakeTenantWithVertical('formacion', 'Formacion');

    $tenantStorage = $this->createMock(EntityStorageInterface::class);
    $tenantStorage->method('load')->with(1)->willReturn($tenant);

    $subStorage = $this->createMock(EntityStorageInterface::class);
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);
    $subStorage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $type) use ($tenantStorage, $subStorage) {
        return $type === 'tenant' ? $tenantStorage : $subStorage;
      });

    $this->assertTrue($this->service->hasVertical(1, 'formacion'));
    $this->assertFalse($this->service->hasVertical(1, 'agroconecta'));
  }

  /**
   * @covers ::getPrimaryVertical
   */
  public function testGetPrimaryVerticalReturnsNullWhenNoTenant(): void {
    $tenantStorage = $this->createMock(EntityStorageInterface::class);
    $tenantStorage->method('load')->willReturn(NULL);

    $subStorage = $this->createMock(EntityStorageInterface::class);
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);
    $subStorage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $type) use ($tenantStorage, $subStorage) {
        return $type === 'tenant' ? $tenantStorage : $subStorage;
      });

    $this->assertNull($this->service->getPrimaryVertical(999));
  }

  /**
   * @covers ::getPrimaryVertical
   */
  public function testGetPrimaryVerticalReturnsKey(): void {
    $tenant = $this->createFakeTenantWithVertical('empleabilidad', 'Empleabilidad');

    $tenantStorage = $this->createMock(EntityStorageInterface::class);
    $tenantStorage->method('load')->with(5)->willReturn($tenant);

    $subStorage = $this->createMock(EntityStorageInterface::class);
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);
    $subStorage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $type) use ($tenantStorage, $subStorage) {
        return $type === 'tenant' ? $tenantStorage : $subStorage;
      });

    $this->assertSame('empleabilidad', $this->service->getPrimaryVertical(5));
  }

  /**
   * @covers ::getAddonVerticals
   */
  public function testGetAddonVerticalsExcludesPrimary(): void {
    $tenant = $this->createFakeTenantWithVertical('formacion', 'Formacion');

    $tenantStorage = $this->createMock(EntityStorageInterface::class);
    $tenantStorage->method('load')->with(1)->willReturn($tenant);

    // Subscription that references a vertical addon 'agroconecta'.
    $subscription = $this->createFakeSubscriptionWithVerticalAddon('agroconecta', 'AgroConecta');

    $subStorage = $this->createMock(EntityStorageInterface::class);
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([100]);
    $subStorage->method('getQuery')->willReturn($query);
    $subStorage->method('loadMultiple')->with([100])->willReturn([100 => $subscription]);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $type) use ($tenantStorage, $subStorage) {
        return $type === 'tenant' ? $tenantStorage : $subStorage;
      });

    $addonVerticals = $this->service->getAddonVerticals(1);
    $this->assertCount(1, $addonVerticals);
    $this->assertArrayHasKey('agroconecta', $addonVerticals);
    $this->assertFalse($addonVerticals['agroconecta']['is_primary']);

    // Full list includes both primary and addon.
    $all = $this->service->getActiveVerticals(1);
    $this->assertCount(2, $all);
    $this->assertTrue($all['formacion']['is_primary']);
    $this->assertFalse($all['agroconecta']['is_primary']);
  }

  /**
   * @covers ::invalidateCache
   */
  public function testInvalidateCacheForcesRequery(): void {
    $tenantStorage = $this->createMock(EntityStorageInterface::class);
    $tenantStorage->method('load')->willReturn(NULL);

    $subStorage = $this->createMock(EntityStorageInterface::class);
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);
    $subStorage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $type) use ($tenantStorage, $subStorage) {
        return $type === 'tenant' ? $tenantStorage : $subStorage;
      });

    $result1 = $this->service->getActiveVerticals(1);
    $this->assertEmpty($result1);

    $this->service->invalidateCache(1);

    $result2 = $this->service->getActiveVerticals(1);
    $this->assertEmpty($result2);
  }

  /**
   * Creates a fake tenant with a primary vertical.
   */
  private function createFakeTenantWithVertical(string $machineName, string $label): object {
    return new class ($machineName, $label) {

      private string $machineName;
      private string $label;

      public function __construct(string $mn, string $l) {
        $this->machineName = $mn;
        $this->label = $l;
      }

      public function hasField(string $name): bool {
        return $name === 'vertical';
      }

      public function get(string $name): object {
        $mn = $this->machineName;
        $l = $this->label;
        return new class ($mn, $l) {

          private string $machineName;
          private string $label;

          public function __construct(string $mn, string $l) {
            $this->machineName = $mn;
            $this->label = $l;
          }

          public function isEmpty(): bool {
            return FALSE;
          }

          public function __get(string $name): ?object {
            if ($name === 'entity') {
              $mn = $this->machineName;
              $l = $this->label;
              return new class ($mn, $l) {

                private string $machineName;
                private string $label;

                public function __construct(string $mn, string $l) {
                  $this->machineName = $mn;
                  $this->label = $l;
                }

                public function getMachineName(): string {
                  return $this->machineName;
                }

                public function label(): string {
                  return $this->label;
                }

              };
            }
            return NULL;
          }

        };
      }

    };
  }

  /**
   * Creates a fake subscription referencing a vertical addon.
   */
  private function createFakeSubscriptionWithVerticalAddon(string $verticalRef, string $label): object {
    return new class ($verticalRef, $label) {

      private string $verticalRef;
      private string $label;

      public function __construct(string $vr, string $l) {
        $this->verticalRef = $vr;
        $this->label = $l;
      }

      public function get(string $name): object {
        $vr = $this->verticalRef;
        $l = $this->label;
        return new class ($vr, $l) {

          private string $verticalRef;
          private string $label;

          public function __construct(string $vr, string $l) {
            $this->verticalRef = $vr;
            $this->label = $l;
          }

          public function __get(string $name): ?object {
            if ($name === 'entity') {
              $vr = $this->verticalRef;
              $l = $this->label;
              return new class ($vr, $l) {

                private string $verticalRef;
                private string $label;

                public function __construct(string $vr, string $l) {
                  $this->verticalRef = $vr;
                  $this->label = $l;
                }

                public function get(string $name): object {
                  $value = match ($name) {
                    'addon_type' => 'vertical',
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
                  return $this->label;
                }

              };
            }
            return NULL;
          }

        };
      }

    };
  }

}
