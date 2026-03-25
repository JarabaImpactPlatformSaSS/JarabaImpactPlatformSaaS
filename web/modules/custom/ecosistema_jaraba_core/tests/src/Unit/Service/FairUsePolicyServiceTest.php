<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\ecosistema_jaraba_core\Entity\FairUsePolicy;
use Drupal\ecosistema_jaraba_core\Service\FairUsePolicyService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for FairUsePolicyService.
 *
 * @group ecosistema_jaraba_core
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\FairUsePolicyService
 */
class FairUsePolicyServiceTest extends UnitTestCase {

  protected FairUsePolicyService $service;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected StateInterface $state;
  protected LoggerInterface $logger;
  protected EntityStorageInterface $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->state = $this->createMock(StateInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->storage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->with('fair_use_policy')
      ->willReturn($this->storage);

    $this->service = new FairUsePolicyService(
      $this->entityTypeManager,
      $this->state,
      $this->logger,
    );
  }

  /**
   * @covers ::evaluate
   */
  public function testEvaluateUnlimitedResourceAllows(): void {
    $result = $this->service->evaluate('tenant1', 'enterprise', 'max_pages', 500, -1);

    $this->assertEquals('allow', $result['decision']);
    $this->assertTrue($result['allowed']);
    $this->assertEquals(-1, $result['limit']);
    $this->assertEquals('none', $result['threshold_level']);
  }

  /**
   * @covers ::evaluate
   */
  public function testEvaluateZeroLimitBlocksHard(): void {
    $result = $this->service->evaluate('tenant1', 'starter', 'ab_test_limit', 1, 0);

    $this->assertEquals('hard_block', $result['decision']);
    $this->assertFalse($result['allowed']);
    $this->assertEquals('exceeded', $result['threshold_level']);
  }

  /**
   * @covers ::evaluate
   */
  public function testEvaluateBelowThresholdAllows(): void {
    // No policy loaded = NULL, uses defaults (70/85/95).
    $this->storage->method('load')->willReturn(NULL);

    $result = $this->service->evaluate('tenant1', 'professional', 'ai_queries', 30, 500);

    $this->assertEquals('allow', $result['decision']);
    $this->assertTrue($result['allowed']);
    $this->assertEquals('none', $result['threshold_level']);
    $this->assertEquals(6.0, $result['usage_pct']);
  }

  /**
   * @covers ::evaluate
   */
  public function testEvaluateAtWarningThreshold(): void {
    // 350 of 500 = 70% — matches first threshold.
    $this->storage->method('load')->willReturn(NULL);

    $result = $this->service->evaluate('tenant1', 'professional', 'ai_queries', 350, 500);

    $this->assertEquals('warn', $result['decision']);
    $this->assertTrue($result['allowed']);
    $this->assertEquals('warning', $result['threshold_level']);
    $this->assertEquals(70.0, $result['usage_pct']);
  }

  /**
   * @covers ::evaluate
   */
  public function testEvaluateAtCriticalThreshold(): void {
    // 475 of 500 = 95% — matches last threshold.
    $this->storage->method('load')->willReturn(NULL);

    $result = $this->service->evaluate('tenant1', 'professional', 'ai_queries', 475, 500);

    // No policy = default action 'warn'.
    $this->assertEquals('warn', $result['decision']);
    $this->assertTrue($result['allowed']);
    $this->assertEquals('critical', $result['threshold_level']);
    $this->assertEquals(95.0, $result['usage_pct']);
  }

  /**
   * @covers ::evaluate
   */
  public function testEvaluateExceededWithoutPolicyUsesWarn(): void {
    // 550 of 500 = 110% > effective_limit (500 + 0% burst = 500).
    $this->storage->method('load')->willReturn(NULL);

    $result = $this->service->evaluate('tenant1', 'professional', 'ai_queries', 550, 500);

    // No policy = default action 'warn' (exceeded level, no enforcement).
    $this->assertEquals('warn', $result['decision']);
    $this->assertTrue($result['allowed']);
    $this->assertEquals('exceeded', $result['threshold_level']);
  }

  /**
   * @covers ::evaluate
   */
  public function testEvaluateWithPolicyBurstTolerance(): void {
    $policy = $this->createPolicyMock(
      thresholds: [70, 85, 95],
      burstPct: 15,
      graceHours: 48,
      enforcementAction: 'throttle',
    );

    $this->storage->method('load')
      ->willReturnCallback(function (string $id) use ($policy) {
        return $id === 'enterprise' ? $policy : NULL;
      });

    // 520 of 500 = 104%, but effective limit = 500 * 1.15 = 575. Still critical.
    $this->state->method('get')->willReturn(NULL);

    $result = $this->service->evaluate('tenant1', 'enterprise', 'ai_queries', 520, 500);

    $this->assertEquals('throttle', $result['decision']);
    $this->assertTrue($result['allowed']);
    $this->assertEquals('critical', $result['threshold_level']);
    $this->assertEquals(575, $result['effective_limit']);
  }

  /**
   * @covers ::evaluate
   */
  public function testEvaluateSoftBlockNotAllowed(): void {
    $policy = $this->createPolicyMock(
      thresholds: [70, 85, 95],
      burstPct: 0,
      graceHours: 6,
      enforcementAction: 'soft_block',
    );

    $this->storage->method('load')
      ->willReturnCallback(function (string $id) use ($policy) {
        return $id === 'starter' ? $policy : NULL;
      });

    // Exceed limit: 550 of 500 = 110%.
    // Grace period: not started yet (state returns NULL).
    $this->state->method('get')->willReturn(NULL);

    $result = $this->service->evaluate('tenant1', 'starter', 'ai_queries', 550, 500);

    $this->assertEquals('soft_block', $result['decision']);
    $this->assertFalse($result['allowed']);
    $this->assertEquals('exceeded', $result['threshold_level']);
  }

  /**
   * @covers ::evaluate
   */
  public function testEvaluateGracePeriodDowngradesToCritical(): void {
    $policy = $this->createPolicyMock(
      thresholds: [70, 85, 95],
      burstPct: 0,
      graceHours: 12,
      enforcementAction: 'throttle',
    );

    $this->storage->method('load')
      ->willReturnCallback(function (string $id) use ($policy) {
        return $id === 'professional' ? $policy : NULL;
      });

    // Grace period active: started 1 hour ago.
    $graceKey = 'fair_use:grace:tenant1:ai_queries';
    $this->state->method('get')
      ->willReturnCallback(function (string $key) use ($graceKey) {
        if ($key === $graceKey) {
          // 1 hour ago.
          return time() - 3600;
        }
        return NULL;
      });

    // 550 of 500 = 110% exceeded, but in grace → downgrade to critical.
    $result = $this->service->evaluate('tenant1', 'professional', 'ai_queries', 550, 500);

    $this->assertEquals('critical', $result['threshold_level']);
    $this->assertEquals('throttle', $result['decision']);
  }

  /**
   * @covers ::getThresholds
   */
  public function testGetThresholdsDefaultsWhenNoPolicy(): void {
    $this->storage->method('load')->willReturn(NULL);

    $thresholds = $this->service->getThresholds('starter');
    $this->assertEquals([70, 85, 95], $thresholds);
  }

  /**
   * @covers ::getThresholds
   */
  public function testGetThresholdsFromPolicy(): void {
    $policy = $this->createPolicyMock(thresholds: [60, 80, 90]);

    $this->storage->method('load')
      ->willReturnCallback(function (string $id) use ($policy) {
        return $id === 'enterprise' ? $policy : NULL;
      });

    $thresholds = $this->service->getThresholds('enterprise');
    $this->assertEquals([60, 80, 90], $thresholds);
  }

  /**
   * @covers ::getUnitPrice
   */
  public function testGetUnitPriceDefaultWhenNoPolicy(): void {
    $this->storage->method('load')->willReturn(NULL);

    $price = $this->service->getUnitPrice('ai_tokens');
    $this->assertEquals(0.00002, $price);
  }

  /**
   * @covers ::getUnitPrice
   */
  public function testGetUnitPriceFromPolicy(): void {
    $policy = $this->createPolicyMock(overagePrice: 0.00005);

    $this->storage->method('load')
      ->willReturnCallback(function (string $id) use ($policy) {
        return $id === 'enterprise' ? $policy : NULL;
      });

    $price = $this->service->getUnitPrice('ai_tokens', 'enterprise');
    $this->assertEquals(0.00005, $price);
  }

  /**
   * @covers ::getActiveEnforcement
   */
  public function testGetActiveEnforcementReturnsNull(): void {
    $this->state->method('get')->willReturn(NULL);

    $result = $this->service->getActiveEnforcement('tenant1', 'ai_queries');
    $this->assertNull($result);
  }

  /**
   * @covers ::getActiveEnforcement
   */
  public function testGetActiveEnforcementReturnsAction(): void {
    $this->state->method('get')
      ->willReturn([
        'action' => 'throttle',
        'timestamp' => time() - 60,
      ]);

    $result = $this->service->getActiveEnforcement('tenant1', 'ai_queries');
    $this->assertEquals('throttle', $result);
  }

  /**
   * @covers ::getActiveEnforcement
   */
  public function testGetActiveEnforcementExpiresAfterTtl(): void {
    $this->state->method('get')
      ->willReturn([
        'action' => 'throttle',
    // > 7200s TTL.
        'timestamp' => time() - 8000,
      ]);

    $this->state->expects($this->once())
      ->method('delete');

    $result = $this->service->getActiveEnforcement('tenant1', 'ai_queries');
    $this->assertNull($result);
  }

  /**
   * @covers ::clearEnforcement
   */
  public function testClearEnforcementDeletesBothKeys(): void {
    $this->state->expects($this->exactly(2))
      ->method('delete');

    $this->service->clearEnforcement('tenant1', 'ai_queries');
  }

  /**
   * Creates a mock FairUsePolicy entity.
   *
   * Uses createMock + method stubs instead of anonymous class to avoid
   * ConfigEntityBase constructor requirements.
   */
  protected function createPolicyMock(
    array $thresholds = [70, 85, 95],
    int $burstPct = 0,
    int $graceHours = 6,
    string $enforcementAction = 'warn',
    float $overagePrice = 0.00002,
  ): FairUsePolicy {
    $policy = $this->createMock(FairUsePolicy::class);

    $policy->method('status')->willReturn(TRUE);

    $sorted = $thresholds;
    sort($sorted);
    $policy->method('getWarningThresholds')->willReturn($sorted);
    $policy->method('getBurstTolerancePct')->willReturn($burstPct);
    $policy->method('getGracePeriodHours')->willReturn($graceHours);
    $policy->method('getEnforcementAction')->willReturn($enforcementAction);
    $policy->method('getOverageUnitPrice')->willReturn($overagePrice);
    $policy->method('getOverageUnitPrices')->willReturn(['ai_tokens' => $overagePrice]);

    return $policy;
  }

}
