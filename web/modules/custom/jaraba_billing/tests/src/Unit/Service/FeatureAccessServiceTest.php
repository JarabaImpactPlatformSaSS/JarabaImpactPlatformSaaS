<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_billing\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;
use Drupal\jaraba_billing\Entity\TenantAddon;
use Drupal\jaraba_billing\Service\FeatureAccessService;
use Drupal\jaraba_billing\Service\PlanValidator;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para FeatureAccessService.
 *
 * @covers \Drupal\jaraba_billing\Service\FeatureAccessService
 * @group jaraba_billing
 */
class FeatureAccessServiceTest extends UnitTestCase {

  protected PlanValidator $planValidator;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected LoggerInterface $logger;
  protected FeatureAccessService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->planValidator = $this->createMock(PlanValidator::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new FeatureAccessService(
      $this->planValidator,
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * Tests canAccess returns TRUE when feature is in plan.
   */
  public function testCanAccessWithPlanFeature(): void {
    $tenant = $this->createMock(TenantInterface::class);
    $groupStorage = $this->createMock(EntityStorageInterface::class);
    $groupStorage->method('load')->with(1)->willReturn($tenant);
    $this->entityTypeManager->method('getStorage')
      ->with('tenant')
      ->willReturn($groupStorage);

    $this->planValidator->method('hasFeature')->willReturn(TRUE);

    $this->assertTrue($this->service->canAccess(1, 'some_feature'));
  }

  /**
   * Tests canAccess returns TRUE when feature is provided by add-on.
   */
  public function testCanAccessWithAddon(): void {
    $tenant = $this->createMock(TenantInterface::class);
    $groupStorage = $this->createMock(EntityStorageInterface::class);
    $groupStorage->method('load')->with(1)->willReturn($tenant);

    $addonEntity = $this->createMock(TenantAddon::class);
    $addonStorage = $this->createMock(EntityStorageInterface::class);
    $addonStorage->method('loadByProperties')
      ->willReturn([$addonEntity]);

    $this->entityTypeManager->method('getStorage')
      ->willReturnMap([
        ['tenant', $groupStorage],
        ['tenant_addon', $addonStorage],
      ]);

    $this->planValidator->method('hasFeature')->willReturn(FALSE);

    $this->assertTrue($this->service->canAccess(1, 'crm_pipeline'));
  }

  /**
   * Tests canAccess returns FALSE when no plan and no addon.
   */
  public function testCanAccessReturnsFalseWithNothing(): void {
    $tenant = $this->createMock(TenantInterface::class);
    $groupStorage = $this->createMock(EntityStorageInterface::class);
    $groupStorage->method('load')->with(1)->willReturn($tenant);

    $addonStorage = $this->createMock(EntityStorageInterface::class);
    $addonStorage->method('loadByProperties')->willReturn([]);

    $this->entityTypeManager->method('getStorage')
      ->willReturnMap([
        ['tenant', $groupStorage],
        ['tenant_addon', $addonStorage],
      ]);

    $this->planValidator->method('hasFeature')->willReturn(FALSE);

    $this->assertFalse($this->service->canAccess(1, 'crm_pipeline'));
  }

  /**
   * Tests canAccess returns FALSE when tenant not found.
   */
  public function testCanAccessReturnsFalseWhenNoTenant(): void {
    $groupStorage = $this->createMock(EntityStorageInterface::class);
    $groupStorage->method('load')->willReturn(NULL);
    $this->entityTypeManager->method('getStorage')
      ->with('tenant')
      ->willReturn($groupStorage);

    $this->assertFalse($this->service->canAccess(999, 'some_feature'));
  }

  /**
   * Tests getAddonForFeature returns correct mapping.
   */
  public function testGetAddonForFeature(): void {
    $this->assertEquals('jaraba_crm', $this->service->getAddonForFeature('crm_pipeline'));
    $this->assertEquals('jaraba_email', $this->service->getAddonForFeature('email_campaigns'));
    $this->assertEquals('paid_ads_sync', $this->service->getAddonForFeature('ads_sync'));
    $this->assertNull($this->service->getAddonForFeature('nonexistent_feature'));
  }

  /**
   * Tests hasActiveAddon delegates to entity storage.
   */
  public function testHasActiveAddon(): void {
    $addon = $this->createMock(TenantAddon::class);
    $addonStorage = $this->createMock(EntityStorageInterface::class);
    $addonStorage->method('loadByProperties')
      ->with([
        'tenant_id' => 1,
        'addon_code' => 'jaraba_crm',
        'status' => 'active',
      ])
      ->willReturn([$addon]);

    $this->entityTypeManager->method('getStorage')
      ->with('tenant_addon')
      ->willReturn($addonStorage);

    $this->assertTrue($this->service->hasActiveAddon(1, 'jaraba_crm'));
  }

  /**
   * Tests getActiveAddons returns addon codes.
   */
  public function testGetActiveAddons(): void {
    $addon1 = $this->createMock(TenantAddon::class);
    $addon1->method('get')->with('addon_code')->willReturn((object) ['value' => 'jaraba_crm']);

    $addon2 = $this->createMock(TenantAddon::class);
    $addon2->method('get')->with('addon_code')->willReturn((object) ['value' => 'jaraba_email']);

    $addonStorage = $this->createMock(EntityStorageInterface::class);
    $addonStorage->method('loadByProperties')->willReturn([$addon1, $addon2]);

    $this->entityTypeManager->method('getStorage')
      ->with('tenant_addon')
      ->willReturn($addonStorage);

    $result = $this->service->getActiveAddons(1);
    $this->assertEquals(['jaraba_crm', 'jaraba_email'], $result);
  }

  /**
   * Tests getAvailableAddons excludes active ones.
   */
  public function testGetAvailableAddonsExcludesActive(): void {
    $addon = $this->createMock(TenantAddon::class);
    $addon->method('get')->with('addon_code')->willReturn((object) ['value' => 'jaraba_crm']);

    $addonStorage = $this->createMock(EntityStorageInterface::class);
    $addonStorage->method('loadByProperties')->willReturn([$addon]);

    $this->entityTypeManager->method('getStorage')
      ->with('tenant_addon')
      ->willReturn($addonStorage);

    $available = $this->service->getAvailableAddons(1);
    $codes = array_column($available, 'code');

    $this->assertNotContains('jaraba_crm', $codes);
    $this->assertContains('jaraba_email', $codes);
    $this->assertContains('ab_testing', $codes);
  }

}
