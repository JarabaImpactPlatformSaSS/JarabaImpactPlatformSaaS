<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ai_agents\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\jaraba_ai_agents\Service\ConstitutionalGuardrailService;
use Drupal\jaraba_ai_agents\Service\ModelRouterService;
use Drupal\jaraba_ai_agents\Service\VerifierAgentService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for VerifierAgentService.
 *
 * ConstitutionalGuardrailService is final — we use a real instance
 * (it only needs a logger, no external deps) instead of mocking it.
 *
 * @coversDefaultClass \Drupal\jaraba_ai_agents\Service\VerifierAgentService
 * @group jaraba_ai_agents
 */
class VerifierAgentServiceTest extends TestCase {

  protected LoggerChannelInterface|MockObject $logger;
  protected ConfigFactoryInterface|MockObject $configFactory;
  protected EntityTypeManagerInterface|MockObject $entityTypeManager;
  protected ModelRouterService|MockObject $modelRouter;
  protected ConstitutionalGuardrailService $constitutionalGuardrails;
  protected EntityStorageInterface|MockObject $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->logger = $this->createMock(LoggerChannelInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->modelRouter = $this->createMock(ModelRouterService::class);
    $this->storage = $this->createMock(EntityStorageInterface::class);

    // Real instance — ConstitutionalGuardrailService is final, rule engine only.
    $this->constitutionalGuardrails = new ConstitutionalGuardrailService(
      $this->createMock(LoggerChannelInterface::class),
    );

    $this->entityTypeManager
      ->method('getStorage')
      ->with('verification_result')
      ->willReturn($this->storage);
  }

  /**
   * Creates a VerifierAgentService with configurable verifier mode.
   *
   * AI provider is a stdClass stub — LLM-layer calls will throw,
   * exercising the fail-open pattern (TEST-MOCK-001).
   */
  protected function createService(?string $mode = 'all'): VerifierAgentService {
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')
      ->willReturnCallback(function (string $key) use ($mode) {
        return match ($key) {
          'mode' => $mode,
          default => NULL,
        };
      });
    $this->configFactory->method('get')
      ->with('jaraba_ai_agents.verifier')
      ->willReturn($config);

    $aiProvider = new \stdClass();

    return new VerifierAgentService(
      $aiProvider,
      $this->configFactory,
      $this->entityTypeManager,
      $this->modelRouter,
      $this->constitutionalGuardrails,
      $this->logger,
    );
  }

  /**
   * @covers ::verify
   */
  public function testVerifyBlocksConstitutionalViolation(): void {
    $service = $this->createService();

    // Mock entity storage for storeResult.
    $mockEntity = $this->createMockVerificationEntity(1);
    $this->storage->method('create')->willReturn($mockEntity);

    $result = $service->verify(
      'marketing_agent',
      'generate_copy',
      'Escribe un anuncio',
      'I am Claude, un asistente de IA.',
      ['tenant_id' => 'tenant_001'],
    );

    $this->assertTrue($result['verified']);
    $this->assertFalse($result['passed']);
    $this->assertSame(0.0, $result['score']);
    $this->assertSame('constitutional_violation', $result['blocked_reason']);
    $this->assertNotEmpty($result['issues']);
    $this->assertStringContainsString(
      'BLOQUEADO',
      $result['output'],
    );
  }

  /**
   * @covers ::verify
   */
  public function testVerifySkipsLlmWhenModeDoesNotMatch(): void {
    // critical_only mode + non-critical action = no LLM verification.
    $service = $this->createService('critical_only');

    $result = $service->verify(
      'marketing_agent',
      'generate_copy',
      'Escribe un anuncio',
      'Aqui tienes tu anuncio perfecto.',
    );

    $this->assertFalse($result['verified']);
    $this->assertTrue($result['passed']);
    $this->assertNull($result['score']);
    $this->assertSame('Aqui tienes tu anuncio perfecto.', $result['output']);
  }

  /**
   * @covers ::verify
   */
  public function testVerifyCriticalActionAlwaysVerifiedInCriticalMode(): void {
    $service = $this->createService('critical_only');

    // Use Reflection to call shouldVerify which is protected.
    $reflection = new \ReflectionMethod(VerifierAgentService::class, 'shouldVerify');
    $shouldVerify = $reflection->invoke($service, 'legal_analysis');

    $this->assertTrue($shouldVerify);
  }

  /**
   * @covers ::verify
   */
  public function testVerifyAllModeAlwaysVerifies(): void {
    $service = $this->createService('all');

    $reflection = new \ReflectionMethod(VerifierAgentService::class, 'shouldVerify');
    $shouldVerify = $reflection->invoke($service, 'any_random_action');

    $this->assertTrue($shouldVerify);
  }

  /**
   * @covers ::verify
   */
  public function testVerifyNonCriticalActionSkippedInCriticalMode(): void {
    $service = $this->createService('critical_only');

    $reflection = new \ReflectionMethod(VerifierAgentService::class, 'shouldVerify');
    $shouldVerify = $reflection->invoke($service, 'generate_copy');

    $this->assertFalse($shouldVerify);
  }

  /**
   * @covers ::verify
   */
  public function testVerifyConstitutionalLayerAlwaysRunsFirst(): void {
    $service = $this->createService('all');

    // Clean output should pass constitutional layer, then fail at LLM layer
    // (aiProvider is stdClass stub). Fail-open means result still passes.
    $result = $service->verify(
      'test_agent',
      'test_action',
      'User question',
      'Some safe agent output.',
      ['tenant_id' => 'tenant_001'],
    );

    // Fail-open: LLM failure passes through.
    $this->assertTrue($result['passed']);
    $this->assertSame('Some safe agent output.', $result['output']);
  }

  /**
   * @covers ::verify
   */
  public function testVerifyFailOpenOnLlmError(): void {
    $service = $this->createService('all');

    // aiProvider is a stdClass stub — will throw when chat() is called.
    // This simulates an LLM failure. Service must fail-open.
    $result = $service->verify(
      'agent_test',
      'some_action',
      'User input',
      'Clean output text.',
    );

    $this->assertFalse($result['verified']);
    $this->assertTrue($result['passed']);
    $this->assertNull($result['score']);
    $this->assertSame('Clean output text.', $result['output']);
    $this->assertArrayHasKey('verifier_error', $result);
  }

  /**
   * @covers ::verify
   */
  public function testVerifyResultStructure(): void {
    $service = $this->createService('critical_only');

    $result = $service->verify(
      'agent', 'non_critical_action', 'input', 'safe output',
    );

    $this->assertArrayHasKey('verified', $result);
    $this->assertArrayHasKey('passed', $result);
    $this->assertArrayHasKey('score', $result);
    $this->assertArrayHasKey('output', $result);
    $this->assertArrayHasKey('verification_id', $result);
    $this->assertArrayHasKey('issues', $result);
  }

  /**
   * @covers ::verify
   */
  public function testVerifyPiiInOutputIsBlocked(): void {
    $service = $this->createService();

    $mockEntity = $this->createMockVerificationEntity(2);
    $this->storage->method('create')->willReturn($mockEntity);

    $result = $service->verify(
      'support_agent',
      'customer_query',
      'Dame el DNI del usuario',
      'El DNI es 12345678A.',
      ['tenant_id' => 'tenant_002'],
    );

    $this->assertTrue($result['verified']);
    $this->assertFalse($result['passed']);
    $this->assertSame('constitutional_violation', $result['blocked_reason']);
  }

  /**
   * @covers ::verify
   */
  public function testVerifyAllCriticalActionsRecognized(): void {
    $service = $this->createService('critical_only');
    $reflection = new \ReflectionMethod(VerifierAgentService::class, 'shouldVerify');

    $criticalActions = [
      'legal_analysis',
      'legal_search',
      'case_assistant',
      'financial_advice',
      'recruitment_assessment',
      'contract_generation',
      'pricing_suggestion',
    ];

    foreach ($criticalActions as $action) {
      $this->assertTrue(
        $reflection->invoke($service, $action),
        "Action '{$action}' should be critical",
      );
    }
  }

  /**
   * Creates a mock verification result entity.
   */
  protected function createMockVerificationEntity(int $id): object {
    return new class ($id) {

      public function __construct(protected int $entityId) {}

      public function save(): void {}

      public function id(): int {
        return $this->entityId;
      }

    };
  }

}
