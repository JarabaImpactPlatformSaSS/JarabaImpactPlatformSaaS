<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Post-execution self-reflection for AI agents.
 *
 * Uses fast tier (Haiku) to evaluate agent responses and propose
 * prompt improvements when quality drops below threshold. Non-blocking:
 * failures in self-reflection MUST NOT affect response delivery.
 *
 * Flow: Agent response → reflect() → evaluation JSON → if score < threshold
 *       → SelfImprovingPromptManager::proposeImprovement().
 *
 * @see \Drupal\jaraba_ai_agents\Service\SelfImprovingPromptManager
 * @see \Drupal\jaraba_ai_agents\Service\ModelRouterService
 */
class AgentSelfReflectionService {

  /**
   * Default minimum quality score to skip improvement proposal.
   */
  private const DEFAULT_QUALITY_THRESHOLD = 0.75;

  public function __construct(
    protected readonly object $aiProvider,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly LoggerChannelInterface $logger,
    protected readonly ModelRouterService $modelRouter,
    protected readonly ?SelfImprovingPromptManager $promptManager = NULL,
    protected readonly ?AIObservabilityService $observability = NULL,
  ) {}

  /**
   * Reflects on an agent execution and evaluates quality.
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
   *   Execution context: tenant_id, vertical, etc.
   *
   * @return array
   *   Reflection result with quality scores and improvement suggestions.
   *   Returns {skipped: true, reason: string} if disabled or on error.
   */
  public function reflect(
    string $agentId,
    string $action,
    string $userInput,
    string $agentOutput,
    array $context = [],
  ): array {
    $config = $this->configFactory->get('jaraba_ai_agents.self_reflection');
    if (!($config->get('enabled') ?? TRUE)) {
      return ['skipped' => TRUE, 'reason' => 'Self-reflection disabled'];
    }

    try {
      $evaluationPrompt = $this->buildReflectionPrompt(
        $agentId, $action, $userInput, $agentOutput,
      );

      $routingConfig = $this->modelRouter->route('reflection', $evaluationPrompt, [
        'force_tier' => 'fast',
      ]);

      $provider = $this->aiProvider->createInstance($routingConfig['provider_id']);
      $input = new \Drupal\ai\OperationType\Chat\ChatInput([
        new \Drupal\ai\OperationType\Chat\ChatMessage(
          'system',
          'You are a quality evaluation agent. Respond only with valid JSON.',
        ),
        new \Drupal\ai\OperationType\Chat\ChatMessage('user', $evaluationPrompt),
      ]);

      $response = $provider->chat($input, $routingConfig['model_id'], [
        'chat_system_role' => 'Quality Evaluator',
      ]);

      $evaluation = $this->parseReflection($response->getNormalized()->getText());

      // Propose improvement if quality is below threshold.
      $threshold = $config->get('quality_threshold') ?? self::DEFAULT_QUALITY_THRESHOLD;
      if ($evaluation['overall_score'] < $threshold && $this->promptManager) {
        $this->proposeImprovement($agentId, $action, $evaluation, $context);
      }

      $this->observability?->log([
        'agent_id' => $agentId,
        'action' => 'self_reflection',
        'tier' => 'fast',
        'model_id' => $routingConfig['model_id'] ?? '',
        'provider_id' => $routingConfig['provider_id'] ?? '',
        'tenant_id' => $context['tenant_id'] ?? '',
        'success' => TRUE,
        'quality_score' => $evaluation['overall_score'],
      ]);

      return $evaluation;
    }
    catch (\Throwable $e) {
      $this->logger->warning('Self-reflection failed for @agent: @error', [
        '@agent' => $agentId,
        '@error' => $e->getMessage(),
      ]);
      return ['skipped' => TRUE, 'reason' => 'Reflection error: ' . $e->getMessage()];
    }
  }

  /**
   * Builds the reflection evaluation prompt.
   */
  protected function buildReflectionPrompt(
    string $agentId,
    string $action,
    string $userInput,
    string $agentOutput,
  ): string {
    return <<<PROMPT
Evaluate the following AI agent response:

Agent: {$agentId}
Action: {$action}

User Input:
{$userInput}

Agent Response:
{$agentOutput}

Evaluate on these dimensions (0.0 to 1.0):
1. relevance: Does the response address the user's request?
2. accuracy: Is the information factually correct?
3. completeness: Does it cover all aspects of the request?
4. tone: Is the tone professional and aligned with brand?
5. actionability: Can the user act on this response?

Respond with JSON only:
{"relevance": 0.0, "accuracy": 0.0, "completeness": 0.0, "tone": 0.0, "actionability": 0.0, "overall_score": 0.0, "improvement_suggestions": ["suggestion 1"], "critical_issues": []}
PROMPT;
  }

  /**
   * Parses the reflection JSON response.
   */
  protected function parseReflection(string $text): array {
    $text = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($text));
    $data = json_decode($text, TRUE);

    if (!is_array($data)) {
      return [
        'overall_score' => 0.5,
        'improvement_suggestions' => [],
        'critical_issues' => [],
        'parse_error' => TRUE,
      ];
    }

    // Ensure required keys with defaults.
    $data['overall_score'] = (float) ($data['overall_score'] ?? 0.5);
    $data['improvement_suggestions'] = $data['improvement_suggestions'] ?? [];
    $data['critical_issues'] = $data['critical_issues'] ?? [];

    return $data;
  }

  /**
   * Proposes a prompt improvement via SelfImprovingPromptManager.
   */
  protected function proposeImprovement(
    string $agentId,
    string $action,
    array $evaluation,
    array $context,
  ): void {
    try {
      $this->promptManager?->proposeImprovement($agentId, $action, [
        'quality_score' => $evaluation['overall_score'],
        'suggestions' => $evaluation['improvement_suggestions'],
        'critical_issues' => $evaluation['critical_issues'],
        'tenant_id' => $context['tenant_id'] ?? '',
      ]);
    }
    catch (\Throwable $e) {
      $this->logger->warning('Prompt improvement proposal failed for @agent: @error', [
        '@agent' => $agentId,
        '@error' => $e->getMessage(),
      ]);
    }
  }

}
