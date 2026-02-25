<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_page_builder\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantBridgeService;
use Drupal\group\Entity\GroupInterface;
use Drupal\jaraba_billing\Service\PlanValidator;
use Drupal\jaraba_page_builder\Service\QuotaManagerService;
use Drupal\jaraba_page_builder\Service\TenantResolverService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for QuotaManagerService.
 *
 * Verifies quota enforcement, plan capabilities, and feature checks,
 * including the TenantBridgeService integration for Group→Tenant resolution.
 *
 * @coversDefaultClass \Drupal\jaraba_page_builder\Service\QuotaManagerService
 * @group jaraba_page_builder
 */
class QuotaManagerServiceTest extends TestCase {

  private QuotaManagerService $service;
  private TenantResolverService&MockObject $tenantResolver;
  private ConfigFactoryInterface&MockObject $configFactory;
  private PlanValidator&MockObject $planValidator;
  private TenantBridgeService&MockObject $tenantBridge;
  private ImmutableConfig&MockObject $config;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->tenantResolver = $this->createMock(TenantResolverService::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->planValidator = $this->createMock(PlanValidator::class);
    $this->tenantBridge = $this->createMock(TenantBridgeService::class);
    $this->config = $this->createMock(ImmutableConfig::class);

    $this->configFactory->method('get')
      ->with('jaraba_page_builder.settings')
      ->willReturn($this->config);

    $this->config->method('get')
      ->with('page_limits')
      ->willReturn([]);

    $this->service = new QuotaManagerService(
      $this->tenantResolver,
      $this->configFactory,
      $this->planValidator,
      NULL,
      $this->tenantBridge,
    );

    // Set string translation to avoid errors.
    $translation = $this->createMock(TranslationInterface::class);
    $translation->method('translateString')->willReturnCallback(fn($string) => $string->getUntranslatedString());
    $this->service->setStringTranslation($translation);
  }

  // =========================================================================
  // TESTS: checkCanCreatePage — allowed via PlanValidator
  // =========================================================================

  /**
   * @covers ::checkCanCreatePage
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testCheckCanCreatePageAllowedViaPlanValidator(): void {
    $group = $this->createMock(GroupInterface::class);
    $tenant = $this->createMock(TenantInterface::class);

    $this->tenantResolver->method('getCurrentTenantPageCount')->willReturn(3);
    $this->tenantResolver->method('getCurrentTenant')->willReturn($group);
    $this->tenantBridge->method('getTenantForGroup')->with($group)->willReturn($tenant);
    $this->planValidator->method('enforceLimit')->willReturn([
      'allowed' => TRUE,
      'usage' => ['limit' => 25],
    ]);

    $result = $this->service->checkCanCreatePage();

    $this->assertTrue($result['allowed']);
    $this->assertSame(22, $result['remaining']);
  }

  /**
   * @covers ::checkCanCreatePage
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testCheckCanCreatePageDeniedViaPlanValidator(): void {
    $group = $this->createMock(GroupInterface::class);
    $tenant = $this->createMock(TenantInterface::class);

    $this->tenantResolver->method('getCurrentTenantPageCount')->willReturn(5);
    $this->tenantResolver->method('getCurrentTenant')->willReturn($group);
    $this->tenantBridge->method('getTenantForGroup')->with($group)->willReturn($tenant);
    $this->planValidator->method('enforceLimit')->willReturn([
      'allowed' => FALSE,
      'usage' => ['limit' => 5],
    ]);

    $result = $this->service->checkCanCreatePage();

    $this->assertFalse($result['allowed']);
    $this->assertNotEmpty($result['message']);
  }

  /**
   * @covers ::checkCanCreatePage
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testCheckCanCreatePageUnlimitedViaPlanValidator(): void {
    $group = $this->createMock(GroupInterface::class);
    $tenant = $this->createMock(TenantInterface::class);

    $this->tenantResolver->method('getCurrentTenantPageCount')->willReturn(100);
    $this->tenantResolver->method('getCurrentTenant')->willReturn($group);
    $this->tenantBridge->method('getTenantForGroup')->with($group)->willReturn($tenant);
    $this->planValidator->method('enforceLimit')->willReturn([
      'allowed' => TRUE,
      'usage' => ['limit' => -1],
    ]);

    $result = $this->service->checkCanCreatePage();

    $this->assertTrue($result['allowed']);
    $this->assertSame(-1, $result['remaining']);
  }

  // =========================================================================
  // TESTS: checkCanCreatePage — fallback (no PlanValidator bridge)
  // =========================================================================

  /**
   * @covers ::checkCanCreatePage
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testCheckCanCreatePageFallbackWhenBridgeReturnsNull(): void {
    $group = $this->createMock(GroupInterface::class);

    $this->tenantResolver->method('getCurrentTenantPageCount')->willReturn(3);
    $this->tenantResolver->method('getCurrentTenant')->willReturn($group);
    $this->tenantResolver->method('getPageLimit')->willReturn(5);
    // Bridge returns NULL — falls through to local fallback.
    $this->tenantBridge->method('getTenantForGroup')->with($group)->willReturn(NULL);

    $result = $this->service->checkCanCreatePage();

    $this->assertTrue($result['allowed']);
    $this->assertSame(2, $result['remaining']);
  }

  /**
   * @covers ::checkCanCreatePage
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testCheckCanCreatePageFallbackDenied(): void {
    $group = $this->createMock(GroupInterface::class);

    $this->tenantResolver->method('getCurrentTenantPageCount')->willReturn(5);
    $this->tenantResolver->method('getCurrentTenant')->willReturn($group);
    $this->tenantResolver->method('getPageLimit')->willReturn(5);
    $this->tenantBridge->method('getTenantForGroup')->willReturn(NULL);

    $result = $this->service->checkCanCreatePage();

    $this->assertFalse($result['allowed']);
  }

  /**
   * @covers ::checkCanCreatePage
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testCheckCanCreatePageFallbackUnlimited(): void {
    $group = $this->createMock(GroupInterface::class);

    $this->tenantResolver->method('getCurrentTenantPageCount')->willReturn(999);
    $this->tenantResolver->method('getCurrentTenant')->willReturn($group);
    $this->tenantResolver->method('getPageLimit')->willReturn(-1);
    $this->tenantBridge->method('getTenantForGroup')->willReturn(NULL);

    $result = $this->service->checkCanCreatePage();

    $this->assertTrue($result['allowed']);
    $this->assertSame(-1, $result['remaining']);
  }

  // =========================================================================
  // TESTS: getPlanCapabilities
  // =========================================================================

  /**
   * @covers ::getPlanCapabilities
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testGetPlanCapabilitiesStarterDefaults(): void {
    $this->tenantResolver->method('getCurrentTenantPlan')->willReturn('starter');
    $this->tenantResolver->method('getCurrentTenant')->willReturn(NULL);

    // No PlanResolver, use fallback.
    $service = new QuotaManagerService(
      $this->tenantResolver,
      $this->configFactory,
      NULL,
      NULL,
      NULL,
    );

    $caps = $service->getPlanCapabilities();

    $this->assertSame(5, $caps['max_pages']);
    $this->assertFalse($caps['seo_advanced']);
    $this->assertFalse($caps['ab_testing']);
  }

  /**
   * @covers ::getPlanCapabilities
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testGetPlanCapabilitiesEnterprise(): void {
    $this->tenantResolver->method('getCurrentTenantPlan')->willReturn('enterprise');
    $this->tenantResolver->method('getCurrentTenant')->willReturn(NULL);

    $service = new QuotaManagerService(
      $this->tenantResolver,
      $this->configFactory,
      NULL,
      NULL,
      NULL,
    );

    $caps = $service->getPlanCapabilities();

    $this->assertSame(-1, $caps['max_pages']);
    $this->assertTrue($caps['seo_advanced']);
    $this->assertTrue($caps['ab_testing']);
    $this->assertTrue($caps['schema_org']);
  }

  // =========================================================================
  // TESTS: hasFeature — with bridge
  // =========================================================================

  /**
   * @covers ::hasFeature
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testHasFeatureWithBridgeAndPlanValidator(): void {
    $group = $this->createMock(GroupInterface::class);
    $tenant = $this->createMock(TenantInterface::class);

    $this->tenantResolver->method('getCurrentTenant')->willReturn($group);
    $this->tenantBridge->method('getTenantForGroup')->with($group)->willReturn($tenant);
    $this->planValidator->method('hasFeature')->with($tenant, 'seo_advanced')->willReturn(TRUE);

    $this->assertTrue($this->service->hasFeature('seo_advanced'));
  }

  /**
   * @covers ::hasFeature
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testHasFeatureFallbackWhenNoBridge(): void {
    $this->tenantResolver->method('getCurrentTenant')->willReturn(NULL);
    $this->tenantResolver->method('getCurrentTenantPlan')->willReturn('enterprise');

    $service = new QuotaManagerService(
      $this->tenantResolver,
      $this->configFactory,
      $this->planValidator,
      NULL,
      NULL,
    );

    // No bridge means it falls through to local capabilities.
    $this->assertTrue($service->hasFeature('seo_advanced'));
  }

}
