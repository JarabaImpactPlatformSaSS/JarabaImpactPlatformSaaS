<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ai_agents\Unit\Agent;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\ecosistema_jaraba_core\Service\UnifiedPromptBuilder;
use Drupal\jaraba_ai_agents\Agent\SmartBaseAgent;
use Drupal\jaraba_ai_agents\Service\AIObservabilityService;
use Drupal\jaraba_ai_agents\Service\ModelRouterService;
use Drupal\jaraba_ai_agents\Service\TenantBrandVoiceService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for SmartBaseAgent.
 *
 * Tests the abstract SmartBaseAgent using a concrete anonymous class.
 * Verifies execute/doExecute delegation, agent ID, system prompt
 * construction with brand voice, model routing delegation, and
 * observability logging.
 *
 * @coversDefaultClass \Drupal\jaraba_ai_agents\Agent\SmartBaseAgent
 * @group jaraba_ai_agents
 */
class SmartBaseAgentTest extends TestCase {

  /**
   * Mock AI provider (uses interface to bypass final AiProviderPluginManager).
   */
  protected MockAiProviderInterface|MockObject $aiProvider;

  /**
   * Mock config factory.
   */
  protected ConfigFactoryInterface|MockObject $configFactory;

  /**
   * Mock logger.
   */
  protected LoggerInterface|MockObject $logger;

  /**
   * Mock brand voice service.
   */
  protected TenantBrandVoiceService|MockObject $brandVoice;

  /**
   * Mock observability service.
   */
  protected AIObservabilityService|MockObject $observability;

  /**
   * Mock unified prompt builder.
   */
  protected UnifiedPromptBuilder|MockObject $promptBuilder;

  /**
   * Mock model router.
   */
  protected ModelRouterService|MockObject $modelRouter;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->aiProvider = $this->createMock(MockAiProviderInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->brandVoice = $this->createMock(TenantBrandVoiceService::class);
    $this->observability = $this->createMock(AIObservabilityService::class);
    $this->promptBuilder = $this->createMock(UnifiedPromptBuilder::class);
    $this->modelRouter = $this->createMock(ModelRouterService::class);
  }

  /**
   * Creates a concrete test agent extending SmartBaseAgent.
   *
   * Uses an anonymous class to test the abstract SmartBaseAgent.
   * The doExecute() method is overridden to return predictable results.
   *
   * @param array|null $doExecuteResult
   *   The result that doExecute() should return, or NULL for default.
   *
   * @return \Drupal\jaraba_ai_agents\Agent\SmartBaseAgent
   *   A concrete instance of the abstract class.
   */
  protected function createTestAgent(?array $doExecuteResult = NULL): SmartBaseAgent {
    $result = $doExecuteResult ?? ['success' => TRUE, 'data' => ['text' => 'Test response']];

    $agent = new class (
      $this->aiProvider,
      $this->configFactory,
      $this->logger,
      $this->brandVoice,
      $this->observability,
      $this->promptBuilder,
      $result,
    ) extends SmartBaseAgent {

      /**
       * Stores the doExecute result for testing.
       */
      private array $testResult;

      /**
       * Tracks if doExecute was called and with what arguments.
       *
       * @var array|null
       */
      public ?array $doExecuteCalled = NULL;

      public function __construct(
        object $aiProvider,
        ConfigFactoryInterface $configFactory,
        LoggerInterface $logger,
        TenantBrandVoiceService $brandVoice,
        AIObservabilityService $observability,
        ?UnifiedPromptBuilder $promptBuilder,
        array $testResult,
      ) {
        parent::__construct($aiProvider, $configFactory, $logger, $brandVoice, $observability, $promptBuilder);
        $this->testResult = $testResult;
      }

      public function getAgentId(): string {
        return 'test_smart_agent';
      }

      public function getLabel(): string {
        return 'Test Smart Agent';
      }

      public function getDescription(): string {
        return 'A test agent for unit testing SmartBaseAgent.';
      }

      public function getAvailableActions(): array {
        return [
          'generate' => [
            'label' => 'Generate',
            'description' => 'Generate test content.',
          ],
        ];
      }

      protected function getDefaultBrandVoice(): string {
        return 'You are a professional test assistant. Respond clearly and helpfully.';
      }

      protected function doExecute(string $action, array $context): array {
        $this->doExecuteCalled = [
          'action' => $action,
          'context' => $context,
        ];
        return $this->testResult;
      }

    };

    return $agent;
  }

  /**
   * Tests that execute() delegates to doExecute().
   *
   * SmartBaseAgent::execute() sets the current action, applies
   * experiment variant, and then calls the abstract doExecute().
   *
   * @covers ::execute
   * @covers ::doExecute
   */
  public function testExecuteCallsDoExecute(): void {
    $expectedResult = ['success' => TRUE, 'data' => ['text' => 'Generated content']];
    $agent = $this->createTestAgent($expectedResult);

    $context = ['message' => 'Hello', 'query' => 'Test query'];
    $result = $agent->execute('generate', $context);

    $this->assertSame($expectedResult, $result);
    $this->assertNotNull($agent->doExecuteCalled);
    $this->assertSame('generate', $agent->doExecuteCalled['action']);
    $this->assertSame($context, $agent->doExecuteCalled['context']);
  }

  /**
   * Tests that getAgentId returns the configured ID.
   *
   * @covers ::getAgentId
   */
  public function testGetAgentIdReturnsConfigured(): void {
    $agent = $this->createTestAgent();

    $this->assertSame('test_smart_agent', $agent->getAgentId());
  }

  /**
   * Tests that buildSystemPrompt includes brand voice content.
   *
   * When a tenant is set, the brand voice service should be called
   * to retrieve the tenant-specific prompt which is included in
   * the system prompt.
   *
   * @covers ::buildSystemPrompt
   */
  public function testBuildSystemPromptIncludesBrandVoice(): void {
    $agent = $this->createTestAgent();
    $agent->setTenantContext('tenant_123', 'comercioconecta');

    $this->brandVoice->method('getPromptForTenant')
      ->with('tenant_123')
      ->willReturn('You are the brand voice of Tienda Premium. Be professional and warm.');

    // Access protected buildSystemPrompt via reflection.
    $method = new \ReflectionMethod(SmartBaseAgent::class, 'buildSystemPrompt');
    $method->setAccessible(TRUE);

    $systemPrompt = $method->invoke($agent, 'Write a product description');

    $this->assertIsString($systemPrompt);
    $this->assertNotEmpty($systemPrompt);

    // Should contain the brand voice prompt.
    $this->assertStringContainsString(
      'Tienda Premium',
      $systemPrompt,
      'System prompt should include tenant brand voice.'
    );

    // Should contain vertical context.
    $this->assertStringContainsString(
      'comercio',
      strtolower($systemPrompt),
      'System prompt should include vertical context.'
    );
  }

  /**
   * Tests that model routing delegates to ModelRouterService.
   *
   * When a ModelRouterService is set, getRoutingConfig() should
   * use it to determine tier, provider, and model.
   *
   * @covers ::getRoutingConfig
   */
  public function testModelRoutingDelegatesToRouter(): void {
    $agent = $this->createTestAgent();
    $agent->setModelRouter($this->modelRouter);

    $routingResult = [
      'tier' => 'fast',
      'provider_id' => 'anthropic',
      'model_id' => 'claude-haiku-4-5-20251001',
      'estimated_cost' => 0.001,
    ];

    $this->modelRouter->method('route')
      ->willReturn($routingResult);

    // Access the protected getRoutingConfig() via reflection.
    $method = new \ReflectionMethod(SmartBaseAgent::class, 'getRoutingConfig');
    $method->setAccessible(TRUE);

    // Set current action first.
    $setAction = new \ReflectionMethod(SmartBaseAgent::class, 'setCurrentAction');
    $setAction->setAccessible(TRUE);
    $setAction->invoke($agent, 'social_post');

    $config = $method->invoke($agent, 'Write a tweet about summer', []);

    $this->assertSame('fast', $config['tier']);
    $this->assertSame('anthropic', $config['provider_id']);
    $this->assertSame('claude-haiku-4-5-20251001', $config['model_id']);
    $this->assertSame(0.001, $config['estimated_cost']);
  }

  /**
   * Tests that observability logs execution when callAiApi is invoked.
   *
   * Verifies that AIObservabilityService::log() is called with
   * the correct agent_id and action after an AI call.
   *
   * @covers ::callAiApi
   */
  public function testObservabilityLogsExecution(): void {
    $agent = $this->createTestAgent();
    $agent->setTenantContext('tenant_5', 'empleabilidad');
    $agent->setModelRouter($this->modelRouter);

    $this->modelRouter->method('route')
      ->willReturn([
        'tier' => 'balanced',
        'provider_id' => 'anthropic',
        'model_id' => 'claude-sonnet-4-6-20250514',
        'estimated_cost' => 0.005,
      ]);

    // Mock the LLM call chain.
    $mockResponse = $this->createMock(MockChatOutputInterface::class);
    $mockNormalized = $this->createMock(MockNormalizedInterface::class);
    $mockNormalized->method('getText')->willReturn('AI response text');
    $mockResponse->method('getNormalized')->willReturn($mockNormalized);

    $mockProvider = $this->createMock(MockProviderInterface::class);
    $mockProvider->method('chat')->willReturn($mockResponse);

    $this->aiProvider->method('createInstance')
      ->with('anthropic')
      ->willReturn($mockProvider);

    // Expect observability log to be called.
    $this->observability->expects($this->once())
      ->method('log')
      ->with($this->callback(function (array $data) {
        return $data['agent_id'] === 'test_smart_agent'
          && $data['action'] === 'generate_content'
          && $data['tier'] === 'balanced'
          && $data['tenant_id'] === 'tenant_5'
          && $data['vertical'] === 'empleabilidad'
          && $data['success'] === TRUE
          && $data['input_tokens'] > 0
          && $data['output_tokens'] > 0;
      }))
      ->willReturn(42);

    // Access protected callAiApi() via reflection.
    $setAction = new \ReflectionMethod(SmartBaseAgent::class, 'setCurrentAction');
    $setAction->setAccessible(TRUE);
    $setAction->invoke($agent, 'generate_content');

    $callAiApi = new \ReflectionMethod(SmartBaseAgent::class, 'callAiApi');
    $callAiApi->setAccessible(TRUE);

    $result = $callAiApi->invoke($agent, 'Generate a marketing email');

    $this->assertTrue($result['success']);
    $this->assertSame('AI response text', $result['data']['text']);
    $this->assertSame('test_smart_agent', $result['agent_id']);
    $this->assertSame('balanced', $result['routing']['tier']);
  }

}

/**
 * Mock interface for AiProviderPluginManager (final class, cannot be mocked).
 *
 * Provides the subset of methods used by BaseAgent and SmartBaseAgent.
 */
interface MockAiProviderInterface {

  /**
   * Gets the default provider for an operation type.
   */
  public function getDefaultProviderForOperationType(string $operationType): ?array;

  /**
   * Creates a provider instance.
   */
  public function createInstance(string $pluginId, array $configuration = []);

}

/**
 * Mock interface for chat output.
 */
interface MockChatOutputInterface {

  /**
   * Gets normalized response.
   */
  public function getNormalized(): object;

}

/**
 * Mock interface for normalized chat response.
 */
interface MockNormalizedInterface {

  /**
   * Gets the response text.
   */
  public function getText(): string;

}

/**
 * Mock interface for AI provider.
 */
interface MockProviderInterface {

  /**
   * Sends a chat request.
   */
  public function chat(object $input, string $modelId, array $configuration = []): object;

}
