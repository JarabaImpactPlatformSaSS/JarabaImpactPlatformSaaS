<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Entity\FeatureFlag;
use Drupal\ecosistema_jaraba_core\Service\FeatureFlagService;
use PHPUnit\Framework\TestCase;

/**
 * Tests FeatureFlagService (S2-02 + S4-05).
 *
 * @group ecosistema_jaraba_core
 * @covers \Drupal\ecosistema_jaraba_core\Service\FeatureFlagService
 */
class FeatureFlagServiceTest extends TestCase {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected EntityStorageInterface $flagStorage;
  protected FeatureFlagService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->flagStorage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $type) {
        if ($type === 'feature_flag') {
          return $this->flagStorage;
        }
        return $this->createMock(EntityStorageInterface::class);
      });

    // No TenantContextService, no Logger — tests null-safe behavior.
    $this->service = new FeatureFlagService($this->entityTypeManager);
  }

  /**
   * Creates a mock FeatureFlag entity.
   */
  protected function createMockFlag(
    string $id,
    bool $enabled,
    string $scope,
    array $conditions = [],
  ): FeatureFlag {
    $flag = $this->createMock(FeatureFlag::class);
    $flag->method('id')->willReturn($id);
    $flag->method('isEnabled')->willReturn($enabled);
    $flag->method('getScope')->willReturn($scope);
    $flag->method('getConditions')->willReturn($conditions);
    return $flag;
  }

  /**
   * Tests global scope — always TRUE when enabled.
   */
  public function testIsEnabledGlobalScope(): void {
    $flag = $this->createMockFlag('my_feature', TRUE, 'global');
    $this->flagStorage->method('load')->with('my_feature')->willReturn($flag);

    $this->assertTrue($this->service->isEnabled('my_feature'));
  }

  /**
   * Tests disabled flag returns FALSE.
   */
  public function testIsEnabledDisabledFlag(): void {
    $flag = $this->createMockFlag('my_feature', FALSE, 'global');
    $this->flagStorage->method('load')->with('my_feature')->willReturn($flag);

    $this->assertFalse($this->service->isEnabled('my_feature'));
  }

  /**
   * Tests non-existent flag returns FALSE.
   */
  public function testIsEnabledNonExistentFlag(): void {
    $this->flagStorage->method('load')->with('nonexistent')->willReturn(NULL);

    $this->assertFalse($this->service->isEnabled('nonexistent'));
  }

  /**
   * Tests plan scope with matching plan.
   */
  public function testIsEnabledPlanScopeMatch(): void {
    $flag = $this->createMockFlag('premium_only', TRUE, 'plan', [
      'plans' => ['professional', 'enterprise'],
    ]);
    $this->flagStorage->method('load')->with('premium_only')->willReturn($flag);

    $this->assertTrue($this->service->isEnabled('premium_only', NULL, 'professional'));
  }

  /**
   * Tests plan scope with non-matching plan.
   */
  public function testIsEnabledPlanScopeNoMatch(): void {
    $flag = $this->createMockFlag('premium_only', TRUE, 'plan', [
      'plans' => ['professional', 'enterprise'],
    ]);
    $this->flagStorage->method('load')->with('premium_only')->willReturn($flag);

    $this->assertFalse($this->service->isEnabled('premium_only', NULL, 'starter'));
  }

  /**
   * Tests plan scope with empty plans allows all.
   */
  public function testIsEnabledPlanScopeEmptyPlansAllowAll(): void {
    $flag = $this->createMockFlag('open_feature', TRUE, 'plan', [
      'plans' => [],
    ]);
    $this->flagStorage->method('load')->with('open_feature')->willReturn($flag);

    $this->assertTrue($this->service->isEnabled('open_feature', NULL, 'starter'));
  }

  /**
   * Tests tenant scope with matching tenant.
   */
  public function testIsEnabledTenantScopeMatch(): void {
    $flag = $this->createMockFlag('beta_feature', TRUE, 'tenant', [
      'tenant_ids' => [10, 20, 30],
    ]);
    $this->flagStorage->method('load')->with('beta_feature')->willReturn($flag);

    $this->assertTrue($this->service->isEnabled('beta_feature', 20));
  }

  /**
   * Tests tenant scope with non-matching tenant.
   */
  public function testIsEnabledTenantScopeNoMatch(): void {
    $flag = $this->createMockFlag('beta_feature', TRUE, 'tenant', [
      'tenant_ids' => [10, 20, 30],
    ]);
    $this->flagStorage->method('load')->with('beta_feature')->willReturn($flag);

    $this->assertFalse($this->service->isEnabled('beta_feature', 99));
  }

  /**
   * Tests vertical scope with matching vertical.
   */
  public function testIsEnabledVerticalScopeMatch(): void {
    $flag = $this->createMockFlag('vertical_feature', TRUE, 'vertical', [
      'verticals' => ['empleabilidad', 'emprendimiento'],
    ]);
    $this->flagStorage->method('load')->with('vertical_feature')->willReturn($flag);

    $this->assertTrue($this->service->isEnabled('vertical_feature', NULL, '', 'empleabilidad'));
  }

  /**
   * Tests vertical scope with non-matching vertical.
   */
  public function testIsEnabledVerticalScopeNoMatch(): void {
    $flag = $this->createMockFlag('vertical_feature', TRUE, 'vertical', [
      'verticals' => ['empleabilidad'],
    ]);
    $this->flagStorage->method('load')->with('vertical_feature')->willReturn($flag);

    $this->assertFalse($this->service->isEnabled('vertical_feature', NULL, '', 'agroconecta'));
  }

  /**
   * Tests percentage scope — 100% always passes.
   */
  public function testIsEnabledPercentage100(): void {
    $flag = $this->createMockFlag('gradual_feature', TRUE, 'percentage', [
      'percentage' => 100,
    ]);
    $this->flagStorage->method('load')->with('gradual_feature')->willReturn($flag);

    $this->assertTrue($this->service->isEnabled('gradual_feature', 42));
  }

  /**
   * Tests percentage scope — 0% always fails.
   */
  public function testIsEnabledPercentage0(): void {
    $flag = $this->createMockFlag('gradual_feature', TRUE, 'percentage', [
      'percentage' => 0,
    ]);
    $this->flagStorage->method('load')->with('gradual_feature')->willReturn($flag);

    $this->assertFalse($this->service->isEnabled('gradual_feature', 42));
  }

  /**
   * Tests percentage rollout is deterministic for same tenant.
   */
  public function testIsEnabledPercentageDeterministic(): void {
    $flag = $this->createMockFlag('rollout', TRUE, 'percentage', [
      'percentage' => 50,
    ]);
    $this->flagStorage->method('load')->with('rollout')->willReturn($flag);

    $result1 = $this->service->isEnabled('rollout', 42);
    $result2 = $this->service->isEnabled('rollout', 42);

    // Same tenant, same flag → deterministic result.
    $this->assertSame($result1, $result2);
  }

  /**
   * Tests unknown scope returns FALSE.
   */
  public function testIsEnabledUnknownScope(): void {
    $flag = $this->createMockFlag('weird', TRUE, 'custom_scope');
    $this->flagStorage->method('load')->with('weird')->willReturn($flag);

    $this->assertFalse($this->service->isEnabled('weird'));
  }

  /**
   * Tests getAll returns all flags.
   */
  public function testGetAll(): void {
    $flag1 = $this->createMockFlag('a', TRUE, 'global');
    $flag2 = $this->createMockFlag('b', FALSE, 'tenant');
    $this->flagStorage->method('loadMultiple')->willReturn([$flag1, $flag2]);

    $result = $this->service->getAll();
    $this->assertCount(2, $result);
  }

  /**
   * Tests getAll handles storage exceptions gracefully.
   */
  public function testGetAllHandlesException(): void {
    $this->flagStorage->method('loadMultiple')
      ->willThrowException(new \RuntimeException('Storage error'));

    $result = $this->service->getAll();
    $this->assertSame([], $result);
  }

  /**
   * Tests getEnabledFlags filters correctly.
   */
  public function testGetEnabledFlags(): void {
    $flag1 = $this->createMockFlag('enabled_one', TRUE, 'global');
    $flag2 = $this->createMockFlag('disabled_one', FALSE, 'global');
    $flag3 = $this->createMockFlag('tenant_only', TRUE, 'tenant', [
      'tenant_ids' => [10],
    ]);

    $this->flagStorage->method('loadMultiple')
      ->willReturn([$flag1, $flag2, $flag3]);
    $this->flagStorage->method('load')
      ->willReturnCallback(function (string $id) use ($flag1, $flag2, $flag3) {
        return match ($id) {
          'enabled_one' => $flag1,
          'disabled_one' => $flag2,
          'tenant_only' => $flag3,
          default => NULL,
        };
      });

    // For tenant 10: enabled_one (global) + tenant_only (matches).
    $enabled = $this->service->getEnabledFlags(10);
    $this->assertContains('enabled_one', $enabled);
    $this->assertContains('tenant_only', $enabled);
    $this->assertNotContains('disabled_one', $enabled);
  }

}
