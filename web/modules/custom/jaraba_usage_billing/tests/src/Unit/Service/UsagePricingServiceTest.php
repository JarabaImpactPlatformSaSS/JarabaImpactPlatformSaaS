<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_usage_billing\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\jaraba_usage_billing\Entity\PricingRule;
use Drupal\jaraba_usage_billing\Service\UsagePricingService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para UsagePricingService.
 *
 * @covers \Drupal\jaraba_usage_billing\Service\UsagePricingService
 * @group jaraba_usage_billing
 */
class UsagePricingServiceTest extends UnitTestCase {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected object $pricingEngine;
  protected LoggerInterface $logger;
  protected UsagePricingService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->pricingEngine = new \stdClass();
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new UsagePricingService(
      $this->entityTypeManager,
      $this->pricingEngine,
      $this->logger,
    );
  }

  /**
   * Tests calculateCost returns zero when no rule found.
   */
  public function testCalculateCostReturnsZeroWhenNoRule(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('notExists')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('pricing_rule')
      ->willReturn($storage);

    $this->logger->expects($this->once())
      ->method('warning');

    $result = $this->service->calculateCost('nonexistent_metric', 100.0, NULL);

    $this->assertEquals(0.0, $result);
  }

  /**
   * Tests calculateCost with per_unit model.
   */
  public function testCalculateCostPerUnit(): void {
    $rule = $this->createPricingRuleMock(
      PricingRule::MODEL_PER_UNIT,
      '0.0100',
      NULL
    );

    $this->setupStorageToReturnRule($rule);

    $result = $this->service->calculateCost('api_requests', 500.0, 10);

    $this->assertEquals(5.0, $result);
  }

  /**
   * Tests calculateCost with flat model.
   */
  public function testCalculateCostFlat(): void {
    $rule = $this->createPricingRuleMock(
      PricingRule::MODEL_FLAT,
      '29.9900',
      NULL
    );

    $this->setupStorageToReturnRule($rule);

    $result = $this->service->calculateCost('storage', 999.0, 10);

    $this->assertEquals(29.99, $result);
  }

  /**
   * Tests calculateCost handles exception gracefully.
   */
  public function testCalculateCostHandlesException(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')
      ->willThrowException(new \RuntimeException('Query error'));

    $this->entityTypeManager->method('getStorage')
      ->with('pricing_rule')
      ->willReturn($storage);

    $this->logger->expects($this->once())
      ->method('error');

    $result = $this->service->calculateCost('api_requests', 100.0, 10);

    $this->assertEquals(0.0, $result);
  }

  /**
   * Tests getPricingRules returns empty array on exception.
   */
  public function testGetPricingRulesHandlesException(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')
      ->willThrowException(new \RuntimeException('Storage error'));

    $this->entityTypeManager->method('getStorage')
      ->with('pricing_rule')
      ->willReturn($storage);

    $this->logger->expects($this->once())
      ->method('error');

    $result = $this->service->getPricingRules(10);

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * Tests calculateCost with tiered model.
   */
  public function testCalculateCostTiered(): void {
    $tiersJson = json_encode([
      ['from' => 0, 'up_to' => 100, 'unit_price' => 0.01, 'flat_price' => 0],
      ['from' => 100, 'up_to' => 1000, 'unit_price' => 0.005, 'flat_price' => 0],
    ]);

    $rule = $this->createPricingRuleMock(
      PricingRule::MODEL_TIERED,
      '0.0000',
      $tiersJson
    );

    $this->setupStorageToReturnRule($rule);

    // 100 * 0.01 + 50 * 0.005 = 1.00 + 0.25 = 1.25
    $result = $this->service->calculateCost('api_requests', 150.0, 10);

    $this->assertEquals(1.25, $result);
  }

  /**
   * Tests calculateCost with package model.
   */
  public function testCalculateCostPackage(): void {
    $tiersJson = json_encode([
      ['package_size' => 1000, 'package_price' => 9.99],
    ]);

    $rule = $this->createPricingRuleMock(
      PricingRule::MODEL_PACKAGE,
      '0.0000',
      $tiersJson
    );

    $this->setupStorageToReturnRule($rule);

    // ceil(2500 / 1000) = 3 packages * 9.99 = 29.97
    $result = $this->service->calculateCost('emails', 2500.0, 10);

    $this->assertEquals(29.97, $result);
  }

  /**
   * Creates a mock PricingRule entity.
   *
   * @param string $model
   *   Pricing model.
   * @param string $unitPrice
   *   Unit price as string.
   * @param string|null $tiersConfig
   *   JSON tiers configuration.
   *
   * @return \Drupal\jaraba_usage_billing\Entity\PricingRule
   *   Mocked pricing rule.
   */
  protected function createPricingRuleMock(string $model, string $unitPrice, ?string $tiersConfig): PricingRule {
    $rule = $this->createMock(PricingRule::class);

    $modelField = $this->createMock(FieldItemListInterface::class);
    $modelField->value = $model;

    $priceField = $this->createMock(FieldItemListInterface::class);
    $priceField->value = $unitPrice;

    $tiersField = $this->createMock(FieldItemListInterface::class);
    $tiersField->value = $tiersConfig;

    $rule->method('get')->willReturnMap([
      ['pricing_model', $modelField],
      ['unit_price', $priceField],
      ['tiers_config', $tiersField],
    ]);

    $decodedTiers = $tiersConfig ? json_decode($tiersConfig, TRUE) : [];
    $rule->method('getDecodedTiers')->willReturn(is_array($decodedTiers) ? $decodedTiers : []);

    return $rule;
  }

  /**
   * Sets up entity storage to return a specific rule.
   *
   * @param \Drupal\jaraba_usage_billing\Entity\PricingRule $rule
   *   The pricing rule mock.
   */
  protected function setupStorageToReturnRule(PricingRule $rule): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('notExists')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([1]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);
    $storage->method('load')->with(1)->willReturn($rule);

    $this->entityTypeManager->method('getStorage')
      ->with('pricing_rule')
      ->willReturn($storage);
  }

}
