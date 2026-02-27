<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ai_agents\Unit\Service;

use Drupal\jaraba_ai_agents\Service\AIObservabilityService;
use Drupal\jaraba_ai_agents\Service\ReActLoopService;
use Drupal\jaraba_ai_agents\Tool\ToolRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests ReActLoopService (S3-02 + S4-05).
 *
 * @group jaraba_ai_agents
 * @covers \Drupal\jaraba_ai_agents\Service\ReActLoopService
 */
class ReActLoopServiceTest extends TestCase {

  protected ToolRegistry $toolRegistry;
  protected AIObservabilityService $observability;
  protected LoggerInterface $logger;
  protected ReActLoopService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->toolRegistry = $this->createMock(ToolRegistry::class);
    $this->toolRegistry->method('generateToolsDocumentation')
      ->willReturn('<tools><tool id="search">Search tool</tool></tools>');

    $this->observability = $this->createMock(AIObservabilityService::class);
    $this->observability->method('log')->willReturn(1);

    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new ReActLoopService(
      $this->toolRegistry,
      $this->observability,
      $this->logger,
    );
  }

  /**
   * Tests normal completion when LLM returns FINISH on first step.
   */
  public function testRunFinishesImmediately(): void {
    $llm = function (string $prompt, array $options) {
      return [
        'success' => TRUE,
        'data' => ['text' => '{"thought":"I know the answer","action":"FINISH","action_input":{},"final_answer":"42"}'],
      ];
    };

    $result = $this->service->run($llm, 'What is 6*7?');

    $this->assertTrue($result['success']);
    $this->assertSame('42', $result['final_answer']);
    $this->assertSame(1, $result['total_steps']);
    $this->assertEmpty($result['tools_used']);
    $this->assertArrayNotHasKey('loop_detected', $result);
    $this->assertArrayNotHasKey('max_steps_reached', $result);
  }

  /**
   * Tests that tool execution works and results feed back to LLM.
   */
  public function testRunExecutesTool(): void {
    $callCount = 0;
    $llm = function (string $prompt, array $options) use (&$callCount) {
      $callCount++;
      if ($callCount === 1) {
        return [
          'success' => TRUE,
          'data' => ['text' => '{"thought":"Need to search","action":"search","action_input":{"query":"test"}}'],
        ];
      }
      return [
        'success' => TRUE,
        'data' => ['text' => '{"thought":"Found it","action":"FINISH","action_input":{},"final_answer":"Result found"}'],
      ];
    };

    $this->toolRegistry->method('execute')
      ->willReturn(['result' => 'search result data']);

    $result = $this->service->run($llm, 'Search for something');

    $this->assertTrue($result['success']);
    $this->assertSame('Result found', $result['final_answer']);
    $this->assertSame(2, $result['total_steps']);
    $this->assertContains('search', $result['tools_used']);
  }

  /**
   * Tests max steps reached without FINISH.
   */
  public function testRunMaxStepsReached(): void {
    $llm = function (string $prompt, array $options) {
      return [
        'success' => TRUE,
        'data' => ['text' => '{"thought":"Thinking...","action":"search","action_input":{"q":"loop' . mt_rand() . '"}}'],
      ];
    };

    $this->toolRegistry->method('execute')
      ->willReturn(['data' => 'some result']);

    $result = $this->service->run($llm, 'Never-ending task', [], 3);

    $this->assertTrue($result['success']);
    $this->assertTrue($result['max_steps_reached']);
    $this->assertSame(3, $result['total_steps']);
  }

  /**
   * Tests S3-02: Duplicate action detection forces FINISH.
   */
  public function testRunDuplicateActionDetection(): void {
    $llm = function (string $prompt, array $options) {
      // Always returns the same action with same params.
      return [
        'success' => TRUE,
        'data' => ['text' => '{"thought":"Try again","action":"search","action_input":{"query":"same"}}'],
      ];
    };

    $this->toolRegistry->method('execute')
      ->willReturn(['data' => 'unchanged']);

    $result = $this->service->run($llm, 'Stuck in a loop', [], 10);

    $this->assertTrue($result['success']);
    $this->assertTrue($result['loop_detected'] ?? FALSE);
    $this->assertSame(3, $result['total_steps']);
  }

  /**
   * Tests that LLM failure breaks the loop early.
   */
  public function testRunLlmFailureBreaks(): void {
    $llm = function (string $prompt, array $options) {
      return [
        'success' => FALSE,
        'error' => 'API error',
      ];
    };

    $result = $this->service->run($llm, 'Will fail');

    // Max steps not flagged, loop just ended.
    $this->assertEmpty($result['steps'] ?? []);
  }

  /**
   * Tests pending approval halts the loop.
   */
  public function testRunPendingApprovalHalts(): void {
    $llm = function (string $prompt, array $options) {
      return [
        'success' => TRUE,
        'data' => ['text' => '{"thought":"Need approval","action":"dangerous_action","action_input":{"param":"val"}}'],
      ];
    };

    $this->toolRegistry->method('execute')
      ->willReturn([
        'pending_approval' => TRUE,
        'approval_id' => 'abc-123',
      ]);

    $result = $this->service->run($llm, 'Needs human approval');

    $this->assertFalse($result['success']);
    $this->assertTrue($result['pending_approval']);
    $this->assertSame('abc-123', $result['approval_id']);
    $this->assertSame(1, $result['total_steps']);
  }

  /**
   * Tests parseStepResponse with JSON format.
   */
  public function testParseStepResponseJson(): void {
    $method = new \ReflectionMethod(ReActLoopService::class, 'parseStepResponse');
    $method->setAccessible(TRUE);

    $json = '{"thought":"analyze data","action":"search","action_input":{"q":"test"},"final_answer":""}';
    $result = $method->invoke($this->service, $json);

    $this->assertSame('analyze data', $result['thought']);
    $this->assertSame('search', $result['action']);
    $this->assertSame(['q' => 'test'], $result['action_input']);
  }

  /**
   * Tests parseStepResponse with JSON wrapped in markdown code blocks.
   */
  public function testParseStepResponseJsonInCodeBlock(): void {
    $method = new \ReflectionMethod(ReActLoopService::class, 'parseStepResponse');
    $method->setAccessible(TRUE);

    $text = "```json\n{\"thought\":\"thinking\",\"action\":\"FINISH\",\"action_input\":{},\"final_answer\":\"done\"}\n```";
    $result = $method->invoke($this->service, $text);

    $this->assertSame('FINISH', $result['action']);
    $this->assertSame('done', $result['final_answer']);
  }

  /**
   * Tests parseStepResponse with THOUGHT/ACTION regex fallback.
   */
  public function testParseStepResponseRegexFallback(): void {
    $method = new \ReflectionMethod(ReActLoopService::class, 'parseStepResponse');
    $method->setAccessible(TRUE);

    $text = "THOUGHT: I need to search for more info\nACTION: search\nACTION_INPUT: {\"q\": \"drupal\"}";
    $result = $method->invoke($this->service, $text);

    $this->assertSame('search', $result['action']);
    $this->assertStringContainsString('search for more info', $result['thought']);
  }

  /**
   * Tests parseStepResponse falls back to FINISH for unparseable text.
   */
  public function testParseStepResponseFallbackFinish(): void {
    $method = new \ReflectionMethod(ReActLoopService::class, 'parseStepResponse');
    $method->setAccessible(TRUE);

    $text = 'Just a plain text response without any structure.';
    $result = $method->invoke($this->service, $text);

    $this->assertSame('FINISH', $result['action']);
    $this->assertSame($text, $result['final_answer']);
  }

  /**
   * Tests parseStepResponse normalizes lowercase "finish" to "FINISH".
   */
  public function testParseStepResponseFinishNormalization(): void {
    $method = new \ReflectionMethod(ReActLoopService::class, 'parseStepResponse');
    $method->setAccessible(TRUE);

    $json = '{"thought":"done","action":"finish","action_input":{},"final_answer":"answer"}';
    $result = $method->invoke($this->service, $json);

    $this->assertSame('FINISH', $result['action']);
  }

  /**
   * Tests buildStepPrompt includes objective and step counter.
   */
  public function testBuildStepPromptStructure(): void {
    $method = new \ReflectionMethod(ReActLoopService::class, 'buildStepPrompt');
    $method->setAccessible(TRUE);

    $prompt = $method->invoke(
      $this->service,
      'Find revenue data',
      [],
      [],
      '<tools></tools>',
      1,
      5
    );

    $this->assertStringContainsString('<objective>Find revenue data</objective>', $prompt);
    $this->assertStringContainsString('<step>1/5</step>', $prompt);
    $this->assertStringContainsString('NUNCA repitas la misma accion', $prompt);
    $this->assertStringContainsString('<tools></tools>', $prompt);
  }

  /**
   * Tests buildStepPrompt truncates long observations.
   */
  public function testBuildStepPromptTruncatesObservations(): void {
    $method = new \ReflectionMethod(ReActLoopService::class, 'buildStepPrompt');
    $method->setAccessible(TRUE);

    $longObs = str_repeat('x', 2000);
    $steps = [
      [
        'number' => 1,
        'thought' => 'test',
        'action' => 'search',
        'action_input' => ['q' => 'test'],
        'observation' => $longObs,
      ],
    ];

    $prompt = $method->invoke(
      $this->service,
      'Objective',
      $steps,
      [],
      '',
      2,
      5
    );

    $this->assertStringContainsString('[truncated]', $prompt);
    // The observation should be truncated to ~1000 chars.
    $this->assertLessThan(
      mb_strlen($longObs),
      mb_strlen($prompt)
    );
  }

}
