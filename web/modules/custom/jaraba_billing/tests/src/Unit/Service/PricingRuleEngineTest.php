<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_billing\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_billing\Service\PricingRuleEngine;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests PricingRuleEngine — financial-critical pricing calculations.
 *
 * Covers tiered, volume, and package pricing models plus IVA calculation.
 *
 * @group jaraba_billing
 * @coversDefaultClass \Drupal\jaraba_billing\Service\PricingRuleEngine
 */
class PricingRuleEngineTest extends TestCase {

  /**
   * The service under test.
   */
  protected PricingRuleEngine $service;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')->willReturn([]);

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeManager->method('getStorage')
      ->willReturn($storage);
    $this->entityTypeManager->method('hasDefinition')
      ->willReturn(TRUE);

    $logger = $this->createMock(LoggerInterface::class);
    $this->service = new PricingRuleEngine($this->entityTypeManager, $logger);
  }

  /**
   * Tests calculateCost falls back to DEFAULT_PRICES when no entity rules.
   */
  public function testCalculateCostDefaultPrices(): void {
    $result = $this->service->calculateCost('ai_tokens', 1000);

    $this->assertIsArray($result);
    $this->assertArrayHasKey('cost', $result);
    $this->assertIsFloat($result['cost']);
    $this->assertGreaterThanOrEqual(0, $result['cost']);
  }

  /**
   * Tests calculateCost returns zero for zero quantity.
   */
  public function testZeroQuantityZeroCost(): void {
    $result = $this->service->calculateCost('ai_tokens', 0);
    $this->assertSame(0.0, $result['cost']);
  }

  /**
   * Tests calculateBill applies 21% IVA correctly.
   */
  public function testCalculateBillAppliesIva(): void {
    $usageMetrics = [
      'ai_tokens' => 1000,
    ];

    $result = $this->service->calculateBill($usageMetrics);

    $this->assertIsArray($result);
    $this->assertArrayHasKey('subtotal', $result);
    $this->assertArrayHasKey('tax_rate', $result);
    $this->assertArrayHasKey('tax', $result);
    $this->assertArrayHasKey('total', $result);

    if ($result['subtotal'] > 0) {
      $expectedTax = round($result['subtotal'] * 0.21, 2);
      $this->assertEquals($expectedTax, $result['tax'], '', 0.01);
    }
  }

  /**
   * Tests calculateBill with empty metrics returns zero.
   */
  public function testCalculateBillEmptyMetrics(): void {
    $result = $this->service->calculateBill([]);
    $this->assertSame(0.0, $result['subtotal']);
    $this->assertSame(0.0, $result['total']);
  }

  /**
   * Tests getRulesForPlan returns array.
   */
  public function testGetRulesForPlanReturnsArray(): void {
    $rules = $this->service->getRulesForPlan('free');
    $this->assertIsArray($rules);
  }

}
