<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ai_agents\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\jaraba_ai_agents\Service\ModelRouterService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for ModelRouterService::assessComplexity() heuristics.
 *
 * Tests the complexity assessment algorithm that combines task type mapping
 * with prompt analysis heuristics (length, keywords, language).
 *
 * @coversDefaultClass \Drupal\jaraba_ai_agents\Service\ModelRouterService
 * @group jaraba_ai_agents
 */
class ModelRouterHeuristicsTest extends TestCase {

  /**
   * The service under test.
   */
  protected ModelRouterService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $logger = $this->createMock(LoggerInterface::class);

    $mockConfig = $this->createMock(ImmutableConfig::class);
    $mockConfig->method('isNew')->willReturn(TRUE);

    $configFactory
      ->method('get')
      ->with('jaraba_ai_agents.model_routing')
      ->willReturn($mockConfig);

    // AiProviderPluginManager is final and cannot be mocked.
    // Build service via reflection since tested methods never use it.
    $reflection = new \ReflectionClass(ModelRouterService::class);
    $this->service = $reflection->newInstanceWithoutConstructor();

    $reflection->getProperty('configFactory')->setValue($this->service, $configFactory);
    $reflection->getProperty('logger')->setValue($this->service, $logger);

    $loadMethod = $reflection->getMethod('loadCustomConfig');
    $loadMethod->setAccessible(TRUE);
    $loadMethod->invoke($this->service);
  }

  /**
   * Tests complexity with various prompt scenarios.
   *
   * @param string $taskType
   *   The task type.
   * @param string $prompt
   *   The prompt text.
   * @param float $expectedMin
   *   Minimum expected complexity.
   * @param float $expectedMax
   *   Maximum expected complexity.
   * @param string $description
   *   Human-readable description of the scenario.
   *
   * @dataProvider assessComplexityDataProvider
   * @covers ::assessComplexity
   */
  public function testAssessComplexity(
    string $taskType,
    string $prompt,
    float $expectedMin,
    float $expectedMax,
    string $description,
  ): void {
    $complexity = $this->service->assessComplexity($taskType, $prompt);

    $this->assertGreaterThanOrEqual(0.0, $complexity, 'Complexity must be >= 0.0');
    $this->assertLessThanOrEqual(1.0, $complexity, 'Complexity must be <= 1.0');
    $this->assertGreaterThanOrEqual(
      $expectedMin,
      $complexity,
      "{$description}: complexity {$complexity} should be >= {$expectedMin}",
    );
    $this->assertLessThanOrEqual(
      $expectedMax,
      $complexity,
      "{$description}: complexity {$complexity} should be <= {$expectedMax}",
    );
  }

  /**
   * Data provider for assessComplexity tests.
   *
   * @return array
   *   Array of test cases: [taskType, prompt, expectedMin, expectedMax, description].
   */
  public static function assessComplexityDataProvider(): array {
    return [
      'short prompt reduces complexity' => [
        'faq_answer',
        'Hi?',
        0.0,
        0.2,
        'Short prompt (<200 chars) on simple task should reduce by 0.1',
      ],
      'long prompt increases complexity' => [
        'email_promo',
        str_repeat('This is a very long prompt for testing purposes. ', 50),
        0.6,
        1.0,
        'Long prompt (>2000 chars) should increase complexity by 0.15',
      ],
      'analyze keyword increases complexity' => [
        'email_promo',
        'Please analyze this data and provide a report on the findings from the customer survey results',
        0.6,
        1.0,
        'Analyze keyword should add 0.2 to complexity',
      ],
      'creative keyword increases complexity' => [
        'email_promo',
        'Create creative content that is innovative and unique for our brand marketing campaign this quarter. We need something that resonates with our audience and captures the essence of our brand values in a compelling way.',
        0.6,
        1.0,
        'Creative keywords should add 0.15 to complexity',
      ],
      'structured keyword reduces complexity' => [
        'email_promo',
        'Return the results in JSON format with structured data fields for each category and subcategory',
        0.3,
        0.6,
        'JSON/structured keywords should reduce complexity by 0.05',
      ],
      'translate keyword reduces complexity' => [
        'email_promo',
        'Please translate this text from English to Spanish maintaining the professional tone throughout',
        0.2,
        0.5,
        'Translate keyword should reduce complexity by 0.1',
      ],
      'faq_answer base complexity' => [
        'faq_answer',
        'A medium length prompt that is more than two hundred characters in total so it does not trigger the short prompt reduction heuristic at all.',
        0.1,
        0.3,
        'faq_answer has base 0.2, no modifiers',
      ],
      'brand_story base complexity' => [
        'brand_story',
        'A medium length prompt that is more than two hundred characters in total so it does not trigger the short prompt reduction heuristic at all.',
        0.6,
        0.8,
        'brand_story has base 0.7, no modifiers',
      ],
      'unknown task defaults to 0.5' => [
        'unknown_task_type',
        'A medium length prompt that is more than two hundred characters in total so it does not trigger the short prompt reduction heuristic at all.',
        0.4,
        0.6,
        'Unknown task type uses default 0.5 base complexity',
      ],
      'Spanish analyze keyword' => [
        'email_promo',
        'Analiza los datos de ventas y compara con el trimestre anterior para evaluar nuestro rendimiento comercial',
        0.6,
        1.0,
        'Spanish analyze keywords (analiza/compara) should add 0.2',
      ],
      'Spanish translate keyword' => [
        'email_promo',
        'Traduce este texto al ingles manteniendo el tono profesional y formal para la comunicacion empresarial',
        0.2,
        0.5,
        'Spanish translate keyword (traduce) should reduce complexity by 0.1',
      ],
      'Spanish creative keyword' => [
        'email_promo',
        'Crea contenido creativo e innovador para nuestra campana de marketing que sea unico y original en el mercado. Necesitamos algo que resuene con nuestra audiencia y capture la esencia de los valores de marca.',
        0.6,
        1.0,
        'Spanish creative keywords (creativo/innovador) should add 0.15',
      ],
      'multiple modifiers stack' => [
        'brand_story',
        str_repeat('Please analyze this data. ', 100),
        0.9,
        1.0,
        'brand_story (0.7) + long prompt (+0.15) + analyze (+0.2) should be clamped to 1.0',
      ],
      'complexity never below zero' => [
        'faq_answer',
        'Translate JSON',
        0.0,
        0.15,
        'Multiple negative modifiers should clamp at 0.0',
      ],
    ];
  }

  /**
   * Tests that complexity is always bounded between 0.0 and 1.0.
   *
   * Exercises edge cases that could push complexity out of bounds.
   *
   * @covers ::assessComplexity
   */
  public function testComplexityBounds(): void {
    // Maximum positive scenario: complex task + long prompt + analyze + creative.
    $maxPrompt = 'Analyze and create creative innovative unique content. '
      . str_repeat('Additional context. ', 200);

    $maxComplexity = $this->service->assessComplexity('brand_story', $maxPrompt);
    $this->assertLessThanOrEqual(1.0, $maxComplexity, 'Complexity must not exceed 1.0');

    // Maximum negative scenario: simple task + short prompt + translate + structured.
    $minComplexity = $this->service->assessComplexity('faq_answer', 'Translate JSON');
    $this->assertGreaterThanOrEqual(0.0, $minComplexity, 'Complexity must not be negative');
  }

  /**
   * Tests that prompt length thresholds work correctly at boundaries.
   *
   * @covers ::assessComplexity
   */
  public function testPromptLengthBoundaries(): void {
    $taskType = 'email_promo'; // Base: 0.5.

    // Exactly 200 chars should NOT get the short prompt reduction.
    $prompt200 = str_repeat('x', 200);
    $complexity200 = $this->service->assessComplexity($taskType, $prompt200);

    // 199 chars SHOULD get the short prompt reduction (-0.1).
    $prompt199 = str_repeat('x', 199);
    $complexity199 = $this->service->assessComplexity($taskType, $prompt199);

    $this->assertLessThan(
      $complexity200,
      $complexity199,
      'Prompt with 199 chars should have lower complexity than 200 chars',
    );

    // Exactly 2000 chars should NOT get the long prompt increase.
    $prompt2000 = str_repeat('x', 2000);
    $complexity2000 = $this->service->assessComplexity($taskType, $prompt2000);

    // 2001 chars SHOULD get the long prompt increase (+0.15).
    $prompt2001 = str_repeat('x', 2001);
    $complexity2001 = $this->service->assessComplexity($taskType, $prompt2001);

    $this->assertGreaterThan(
      $complexity2000,
      $complexity2001,
      'Prompt with 2001 chars should have higher complexity than 2000 chars',
    );
  }

}
