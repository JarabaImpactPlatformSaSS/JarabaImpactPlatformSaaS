<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ai_agents\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\jaraba_ai_agents\Service\ModelRouterService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for ModelRouterService.
 *
 * Tests intelligent model routing based on task complexity,
 * including tier selection, forced tiers, speed/quality modifiers,
 * and cost savings calculations.
 *
 * @coversDefaultClass \Drupal\jaraba_ai_agents\Service\ModelRouterService
 * @group jaraba_ai_agents
 */
class ModelRouterServiceTest extends TestCase {

  /**
   * The service under test.
   */
  protected ModelRouterService $service;

  /**
   * Mock config factory.
   */
  protected ConfigFactoryInterface|MockObject $configFactory;

  /**
   * Mock logger.
   */
  protected LoggerInterface|MockObject $logger;

  /**
   * Creates a ModelRouterService bypassing the final AiProviderPluginManager.
   *
   * AiProviderPluginManager is declared final and cannot be mocked.
   * Since the tested methods never call it, we construct the service
   * via reflection and set the required properties directly.
   *
   * @return \Drupal\jaraba_ai_agents\Service\ModelRouterService
   *   The service with mocked dependencies.
   */
  protected function createServiceViaReflection(): ModelRouterService {
    $reflection = new \ReflectionClass(ModelRouterService::class);
    $service = $reflection->newInstanceWithoutConstructor();

    $configProp = $reflection->getProperty('configFactory');
    $configProp->setValue($service, $this->configFactory);

    $loggerProp = $reflection->getProperty('logger');
    $loggerProp->setValue($service, $this->logger);

    // Trigger loadCustomConfig via reflection (it reads from configFactory).
    $loadMethod = $reflection->getMethod('loadCustomConfig');
    $loadMethod->setAccessible(TRUE);
    $loadMethod->invoke($service);

    return $service;
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    // Mock ImmutableConfig that isNew() returns TRUE so no custom config is loaded.
    $mockConfig = $this->createMock(ImmutableConfig::class);
    $mockConfig->method('isNew')->willReturn(TRUE);

    $this->configFactory
      ->method('get')
      ->with('jaraba_ai_agents.model_routing')
      ->willReturn($mockConfig);

    $this->service = $this->createServiceViaReflection();
  }

  /**
   * Tests that force_tier option bypasses complexity assessment.
   *
   * When force_tier='fast' is provided, the router should return the fast
   * tier regardless of how complex the task type or prompt might be.
   *
   * @covers ::route
   */
  public function testRouteWithForceTier(): void {
    $result = $this->service->route(
      'brand_story',
      'This is a very complex prompt that requires deep analysis and creative thinking about multiple interconnected topics',
      ['force_tier' => 'fast'],
    );

    $this->assertSame('fast', $result['tier']);
    $this->assertSame('anthropic', $result['provider_id']);
    $this->assertSame('claude-haiku-4-5-20251001', $result['model_id']);
    $this->assertArrayHasKey('estimated_cost', $result);
  }

  /**
   * Tests that a simple task with short prompt routes to fast tier.
   *
   * faq_answer has base complexity 0.2. With a short prompt (<200 chars)
   * complexity is reduced by 0.1, giving 0.1 which falls in fast tier (<=0.3).
   *
   * @covers ::route
   */
  public function testRouteSimpleTask(): void {
    $result = $this->service->route('faq_answer', 'What are your opening hours?');

    $this->assertSame('fast', $result['tier']);
    $this->assertSame('anthropic', $result['provider_id']);
    $this->assertSame('claude-haiku-4-5-20251001', $result['model_id']);
  }

  /**
   * Tests that a complex task with analytical prompt routes to premium tier.
   *
   * brand_story has base complexity 0.7. A long prompt (>2000 chars) adds 0.15,
   * and "analyze" keyword adds 0.2, giving 1.05 clamped to 1.0 = premium.
   *
   * @covers ::route
   */
  public function testRouteComplexTask(): void {
    // Generate a prompt > 2000 chars that contains "analyze".
    $longPrompt = 'Please analyze the following brand narrative and provide insights. '
      . str_repeat('This is additional context for the brand story that requires deep analysis. ', 50);

    $this->assertGreaterThan(2000, strlen($longPrompt));

    $result = $this->service->route('brand_story', $longPrompt);

    $this->assertSame('premium', $result['tier']);
    $this->assertSame('anthropic', $result['provider_id']);
    $this->assertSame('claude-opus-4-6-20250515', $result['model_id']);
  }

  /**
   * Tests that require_speed clamps complexity to max 0.3.
   *
   * Even a complex task should be routed to fast tier when speed is required.
   *
   * @covers ::route
   */
  public function testRouteRequireSpeed(): void {
    $result = $this->service->route(
      'brand_story',
      'Write a compelling brand story about our heritage',
      ['require_speed' => TRUE],
    );

    $this->assertSame('fast', $result['tier']);
  }

  /**
   * Tests that require_quality clamps complexity to min 0.8.
   *
   * Even a simple task should be routed to premium tier when quality is required,
   * since 0.8 exceeds balanced max_complexity of 0.7.
   *
   * @covers ::route
   */
  public function testRouteRequireQuality(): void {
    $result = $this->service->route(
      'faq_answer',
      'What time do you open?',
      ['require_quality' => TRUE],
    );

    $this->assertSame('premium', $result['tier']);
  }

  /**
   * Tests that route() returns all expected keys in the result array.
   *
   * @covers ::route
   */
  public function testRouteReturnStructure(): void {
    $result = $this->service->route('social_post', 'Write a tweet about summer');

    $this->assertArrayHasKey('provider_id', $result);
    $this->assertArrayHasKey('model_id', $result);
    $this->assertArrayHasKey('tier', $result);
    $this->assertArrayHasKey('estimated_cost', $result);
    $this->assertArrayHasKey('cost_per_1k_input', $result);
    $this->assertArrayHasKey('cost_per_1k_output', $result);

    $this->assertIsString($result['provider_id']);
    $this->assertIsString($result['model_id']);
    $this->assertIsString($result['tier']);
    $this->assertIsFloat($result['estimated_cost']);
    $this->assertGreaterThanOrEqual(0, $result['estimated_cost']);
  }

  /**
   * Tests that selectTier() maps complexity ranges correctly.
   *
   * Uses Reflection to test the protected method directly.
   *
   * @covers ::selectTier
   */
  public function testSelectTier(): void {
    $reflection = new \ReflectionMethod($this->service, 'selectTier');
    $reflection->setAccessible(TRUE);

    // Complexity <= 0.3 should return fast.
    $this->assertSame('fast', $reflection->invoke($this->service, 0.0));
    $this->assertSame('fast', $reflection->invoke($this->service, 0.1));
    $this->assertSame('fast', $reflection->invoke($this->service, 0.3));

    // 0.3 < complexity <= 0.7 should return balanced.
    $this->assertSame('balanced', $reflection->invoke($this->service, 0.31));
    $this->assertSame('balanced', $reflection->invoke($this->service, 0.5));
    $this->assertSame('balanced', $reflection->invoke($this->service, 0.7));

    // complexity > 0.7 should return premium.
    $this->assertSame('premium', $reflection->invoke($this->service, 0.71));
    $this->assertSame('premium', $reflection->invoke($this->service, 0.9));
    $this->assertSame('premium', $reflection->invoke($this->service, 1.0));
  }

  /**
   * Tests calculateSavings() with a typical usage log.
   *
   * @covers ::calculateSavings
   */
  public function testCalculateSavings(): void {
    $usageLog = [
      ['cost' => 0.001, 'tokens' => 500],
      ['cost' => 0.003, 'tokens' => 1000],
      ['cost' => 0.010, 'tokens' => 2000],
    ];

    $result = $this->service->calculateSavings($usageLog);

    $this->assertArrayHasKey('actual_cost', $result);
    $this->assertArrayHasKey('premium_equivalent', $result);
    $this->assertArrayHasKey('savings', $result);
    $this->assertArrayHasKey('savings_percent', $result);

    // Actual cost = 0.001 + 0.003 + 0.010 = 0.014.
    $this->assertSame(0.014, $result['actual_cost']);

    // Premium equivalent is computed from tokens and premium tier costs.
    $this->assertGreaterThan(0, $result['premium_equivalent']);

    // Savings should be positive when actual cost is less than premium.
    $this->assertGreaterThanOrEqual(0, $result['savings']);
  }

  /**
   * Tests calculateSavings() with empty usage log.
   *
   * @covers ::calculateSavings
   */
  public function testCalculateSavingsEmpty(): void {
    $result = $this->service->calculateSavings([]);

    $this->assertSame(0.0, $result['actual_cost']);
    $this->assertSame(0.0, $result['premium_equivalent']);
    $this->assertSame(0.0, $result['savings']);
    $this->assertSame(0.0, $result['savings_percent']);
  }

  /**
   * Tests getTiers() returns all 3 configured tiers.
   *
   * @covers ::getTiers
   */
  public function testGetTiers(): void {
    $tiers = $this->service->getTiers();

    $this->assertArrayHasKey('fast', $tiers);
    $this->assertArrayHasKey('balanced', $tiers);
    $this->assertArrayHasKey('premium', $tiers);

    // Each tier must have required keys.
    foreach ($tiers as $tierName => $config) {
      $this->assertArrayHasKey('provider', $config, "Tier '{$tierName}' missing 'provider'");
      $this->assertArrayHasKey('model', $config, "Tier '{$tierName}' missing 'model'");
      $this->assertArrayHasKey('cost_per_1k_input', $config, "Tier '{$tierName}' missing 'cost_per_1k_input'");
      $this->assertArrayHasKey('cost_per_1k_output', $config, "Tier '{$tierName}' missing 'cost_per_1k_output'");
      $this->assertArrayHasKey('max_complexity', $config, "Tier '{$tierName}' missing 'max_complexity'");
    }
  }

  /**
   * Tests that force_tier with invalid tier name falls through to normal routing.
   *
   * @covers ::route
   */
  public function testRouteWithInvalidForceTier(): void {
    $result = $this->service->route(
      'faq_answer',
      'Short question?',
      ['force_tier' => 'nonexistent_tier'],
    );

    // Should fall through to normal complexity-based routing.
    $this->assertContains($result['tier'], ['fast', 'balanced', 'premium']);
  }

  /**
   * Tests that estimated cost increases with longer prompts.
   *
   * @covers ::route
   */
  public function testEstimatedCostIncreasesWithPromptLength(): void {
    $shortResult = $this->service->route('faq_answer', 'Hello', ['force_tier' => 'fast']);
    $longResult = $this->service->route(
      'faq_answer',
      str_repeat('This is a longer prompt. ', 200),
      ['force_tier' => 'fast'],
    );

    $this->assertGreaterThan($shortResult['estimated_cost'], $longResult['estimated_cost']);
  }

}
