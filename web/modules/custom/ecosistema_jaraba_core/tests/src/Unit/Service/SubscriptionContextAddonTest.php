<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\SubscriptionContextService;
use Drupal\Tests\UnitTestCase;

/**
 * Tests para las extensiones de add-ons en SubscriptionContextService.
 *
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\SubscriptionContextService
 * @group ecosistema_jaraba_core
 */
class SubscriptionContextAddonTest extends UnitTestCase {

  /**
   * El servicio bajo test.
   */
  protected SubscriptionContextService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadMultiple')->willReturn([]);
    $storage->method('loadByProperties')->willReturn([]);
    $entityTypeManager->method('getStorage')->willReturn($storage);

    $this->service = new SubscriptionContextService(
      $entityTypeManager,
      NULL,
      NULL,
      NULL,
      NULL,
      NULL,
    );
    $this->service->setStringTranslation($this->getStringTranslationStub());
  }

  /**
   * @covers ::getContextForUser
   */
  public function testGetContextForUserWithoutTenantReturnsEmpty(): void {
    $result = $this->service->getContextForUser(999);
    static::assertEmpty($result);
  }

  /**
   * @covers ::resolveAddonIcon
   */
  public function testResolveAddonIconMapping(): void {
    $method = new \ReflectionMethod($this->service, 'resolveAddonIcon');
    $method->setAccessible(TRUE);

    static::assertSame('users', $method->invoke($this->service, 'jaraba_crm'));
    static::assertSame('mail', $method->invoke($this->service, 'jaraba_email'));
    static::assertSame('mail', $method->invoke($this->service, 'jaraba_email_plus'));
    static::assertSame('share', $method->invoke($this->service, 'jaraba_social'));
    static::assertSame('megaphone', $method->invoke($this->service, 'paid_ads_sync'));
    static::assertSame('target', $method->invoke($this->service, 'retargeting_pixels'));
    static::assertSame('calendar', $method->invoke($this->service, 'events_webinars'));
    static::assertSame('split', $method->invoke($this->service, 'ab_testing'));
    static::assertSame('gift', $method->invoke($this->service, 'referral_program'));
    static::assertSame('package', $method->invoke($this->service, 'unknown_addon'));
  }

  /**
   * @covers ::resolveBillingSummary
   */
  public function testResolveBillingSummaryCalculation(): void {
    $method = new \ReflectionMethod($this->service, 'resolveBillingSummary');
    $method->setAccessible(TRUE);

    $tenant = new class {

      public function hasField(string $name): bool {
        return FALSE;
      }

    };

    $activeAddons = [
      ['price' => 19.0],
      ['price' => 29.0],
    ];

    /** @var array<string, mixed> $result */
    $result = $method->invoke($this->service, $tenant, $activeAddons, 79.0);

    static::assertSame(79.0, $result['plan_monthly']);
    static::assertSame(48.0, $result['addons_monthly']);
    static::assertSame(127.0, $result['total_monthly']);
    static::assertNull($result['next_invoice_date']);
    static::assertSame('monthly', $result['billing_cycle']);
  }

  /**
   * @covers ::buildFreePlanContext
   */
  public function testFreePlanContextIncludesAddonSections(): void {
    $method = new \ReflectionMethod($this->service, 'buildFreePlanContext');
    $method->setAccessible(TRUE);

    $tenant = new class {

      public function hasField(string $name): bool {
        return FALSE;
      }

    };

    /** @var array<string, mixed> $result */
    $result = $method->invoke($this->service, $tenant);

    static::assertArrayHasKey('addons', $result);
    static::assertArrayHasKey('active', $result['addons']);
    static::assertArrayHasKey('recommended', $result['addons']);
    static::assertEmpty($result['addons']['active']);

    static::assertArrayHasKey('billing', $result);
    static::assertSame(0.0, $result['billing']['total_monthly']);
    static::assertSame('monthly', $result['billing']['billing_cycle']);
  }

}
