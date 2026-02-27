<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Pre-delivery verification of agent responses.
 *
 * Evaluates agent output quality and safety before delivering to the user.
 * Two-layer verification:
 * 1. Constitutional enforcement (local, zero-cost, always active).
 * 2. LLM-based quality check (fast tier, configurable mode).
 *
 * Configurable modes:
 * - all: Every response verified by LLM.
 * - sample: 10% random sample verified.
 * - critical_only: Only high-risk actions (legal, recruitment, financial).
 *
 * Fail-open policy: if the verifier itself fails, the response passes through
 * to maintain availability. Constitutional checks always run regardless.
 *
 * @see \Drupal\jaraba_ai_agents\Service\ConstitutionalGuardrailService
 * @see \Drupal\jaraba_ai_agents\Entity\VerificationResult
 */
class VerifierAgentService {

  /**
   * Verification modes.
   */
  private const MODE_ALL = 'all';
  private const MODE_SAMPLE = 'sample';
  private const MODE_CRITICAL_ONLY = 'critical_only';

  /**
   * Sample rate for MODE_SAMPLE.
   */
  private const SAMPLE_RATE = 10;

  /**
   * Minimum score to pass verification.
   */
  private const PASS_THRESHOLD = 0.6;

  /**
   * Actions that always get verified in critical_only mode.
   */
  private const CRITICAL_ACTIONS = [
    'legal_analysis',
    'legal_search',
    'case_assistant',
    'financial_advice',
    'recruitment_assessment',
    'contract_generation',
    'pricing_suggestion',
  ];

  public function __construct(
    protected readonly object $aiProvider,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ModelRouterService $modelRouter,
    protected readonly ConstitutionalGuardrailService $constitutionalGuardrails,
    protected readonly LoggerChannelInterface $logger,
    protected readonly ?AIObservabilityService $observability = NULL,
  ) {}

  /**
   * Verifies an agent response before delivery.
   *
   * @param string $agentId
   *   The agent that produced the response.
   * @param string $action
   *   The action executed.
   * @param string $userInput
   *   The original user input.
   * @param string $agentOutput
   *   The agent's response text.
   * @param array $context
   *   Context: tenant_id, vertical, etc.
   *
   * @return array
   *   Verification result: verified, passed, score, output, verification_id,
   *   issues, blocked_reason (if blocked).
   */
  public function verify(
    string $agentId,
    string $action,
    string $userInput,
    string $agentOutput,
    array $context = [],
  ): array {
    // Layer 1: Constitutional enforcement (always, zero cost, local).
    $constitutional = $this->constitutionalGuardrails->enforce($agentOutput, [
      'agent_id' => $agentId,
      'action' => $action,
      'tenant_id' => $context['tenant_id'] ?? '',
    ]);

    if (!$constitutional['passed']) {
      $verificationId = $this->storeResult(
        $agentId, $action, 0.0, 'blocked_constitutional',
        $constitutional['violations'], $context['tenant_id'] ?? '',
      );
      return [
        'verified' => TRUE,
        'passed' => FALSE,
        'score' => 0.0,
        'output' => $constitutional['sanitized_output'],
        'verification_id' => $verificationId,
        'issues' => $constitutional['violations'],
        'blocked_reason' => 'constitutional_violation',
      ];
    }

    // Layer 2: Check if LLM verification should run.
    if (!$this->shouldVerify($action)) {
      return [
        'verified' => FALSE,
        'passed' => TRUE,
        'score' => NULL,
        'output' => $agentOutput,
        'verification_id' => NULL,
        'issues' => [],
      ];
    }

    // Layer 2: LLM-based quality verification (fast tier).
    try {
      $verificationPrompt = $this->buildVerificationPrompt(
        $agentId, $action, $userInput, $agentOutput,
      );
      $routingConfig = $this->modelRouter->route('verification', $verificationPrompt, [
        'force_tier' => 'fast',
      ]);

      $provider = $this->aiProvider->createInstance($routingConfig['provider_id']);
      $input = new \Drupal\ai\OperationType\Chat\ChatInput([
        new \Drupal\ai\OperationType\Chat\ChatMessage('system', 'You are a response quality verifier. Respond only with valid JSON.'),
        new \Drupal\ai\OperationType\Chat\ChatMessage('user', $verificationPrompt),
      ]);

      $response = $provider->chat($input, $routingConfig['model_id'], [
        'chat_system_role' => 'Verifier',
      ]);

      $evaluation = $this->parseVerification($response->getNormalized()->getText());
      $passed = $evaluation['score'] >= self::PASS_THRESHOLD;
      $status = $passed ? 'passed' : 'failed';

      $verificationId = $this->storeResult(
        $agentId, $action, $evaluation['score'], $status,
        $evaluation['issues'], $context['tenant_id'] ?? '',
      );

      $this->observability?->log([
        'agent_id' => 'verifier',
        'action' => 'verify_' . $agentId,
        'tier' => 'fast',
        'model_id' => $routingConfig['model_id'] ?? '',
        'provider_id' => $routingConfig['provider_id'] ?? '',
        'tenant_id' => $context['tenant_id'] ?? '',
        'success' => $passed,
        'quality_score' => $evaluation['score'],
      ]);

      return [
        'verified' => TRUE,
        'passed' => $passed,
        'score' => $evaluation['score'],
        'output' => $agentOutput,
        'verification_id' => $verificationId,
        'issues' => $evaluation['issues'],
      ];
    }
    catch (\Throwable $e) {
      // Fail-open: verifier failure must not block response delivery.
      $this->logger->warning('Verification failed for @agent/@action: @error', [
        '@agent' => $agentId,
        '@action' => $action,
        '@error' => $e->getMessage(),
      ]);
      return [
        'verified' => FALSE,
        'passed' => TRUE,
        'score' => NULL,
        'output' => $agentOutput,
        'verification_id' => NULL,
        'issues' => [],
        'verifier_error' => $e->getMessage(),
      ];
    }
  }

  /**
   * Determines if LLM verification should run for this action.
   */
  protected function shouldVerify(string $action): bool {
    $config = $this->configFactory->get('jaraba_ai_agents.verifier');
    $mode = $config->get('mode') ?? self::MODE_SAMPLE;

    return match ($mode) {
      self::MODE_ALL => TRUE,
      self::MODE_CRITICAL_ONLY => in_array($action, self::CRITICAL_ACTIONS, TRUE),
      self::MODE_SAMPLE => (mt_rand(1, 100) <= self::SAMPLE_RATE),
      default => FALSE,
    };
  }

  /**
   * Builds the verification prompt.
   */
  protected function buildVerificationPrompt(
    string $agentId,
    string $action,
    string $userInput,
    string $agentOutput,
  ): string {
    return <<<PROMPT
Verify this AI agent response for quality and safety:

Agent: {$agentId} | Action: {$action}

User asked: {$userInput}

Agent responded: {$agentOutput}

Check for:
1. Factual accuracy (no hallucinations)
2. Relevance to the user's request
3. Professional tone
4. No harmful or misleading content
5. No internal system information leaked
6. No PII exposure

Respond with JSON only:
{"score": 0.0, "issues": [{"type": "string", "severity": "low|medium|high|critical", "description": "string"}], "recommendation": "pass|warn|block"}
PROMPT;
  }

  /**
   * Parses verification response JSON.
   */
  protected function parseVerification(string $text): array {
    $text = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($text));
    $data = json_decode($text, TRUE);

    return [
      'score' => (float) ($data['score'] ?? 0.5),
      'issues' => $data['issues'] ?? [],
      'recommendation' => $data['recommendation'] ?? 'pass',
    ];
  }

  /**
   * Stores a verification result entity.
   */
  protected function storeResult(
    string $agentId,
    string $action,
    float $score,
    string $status,
    array $issues,
    string $tenantId = '',
  ): ?int {
    try {
      $storage = $this->entityTypeManager->getStorage('verification_result');
      $entity = $storage->create([
        'agent_id' => $agentId,
        'action' => $action,
        'score' => $score,
        'status' => $status,
        'issues' => json_encode($issues, JSON_THROW_ON_ERROR),
        'tenant_id' => $tenantId,
      ]);
      $entity->save();
      return (int) $entity->id();
    }
    catch (\Throwable $e) {
      $this->logger->error('Failed to store verification result: @error', [
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

}
