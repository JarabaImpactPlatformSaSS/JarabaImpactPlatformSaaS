<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * GAP-L5-I: Causal analytics service using LLM premium tier.
 *
 * Provides natural language causal reasoning over structured data.
 *
 * Query types:
 *   - Diagnostic: "Why did conversions drop?" -> examines traffic, pricing, content.
 *   - Counterfactual: "What if we raise price 10%?" -> historical elasticity.
 *   - Predictive: "What will happen if we add this feature?" -> trend analysis.
 *   - Prescriptive: "How to increase retention?" -> actionable recommendations.
 *
 * Uses premium tier (Opus) for complex reasoning with structured data context.
 * Each analysis is persisted in CausalAnalysis entity for audit and reference.
 */
class CausalAnalyticsService {

  /**
   * Constructor.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected ?object $aiProvider = NULL,
    protected ?object $modelRouter = NULL,
    protected ?object $observability = NULL,
  ) {}

  /**
   * Executes a causal analysis query.
   *
   * @param string $query
   *   Natural language causal query.
   * @param string $queryType
   *   Query type: diagnostic, counterfactual, predictive, prescriptive.
   * @param array $dataContext
   *   Structured data to reason over.
   * @param string $tenantId
   *   The tenant ID.
   *
   * @return array
   *   Analysis result with causal_factors, recommendations, confidence.
   */
  public function analyze(string $query, string $queryType, array $dataContext, string $tenantId): array {
    $validTypes = ['diagnostic', 'counterfactual', 'predictive', 'prescriptive'];
    if (!in_array($queryType, $validTypes, TRUE)) {
      return ['error' => 'Invalid query type: ' . $queryType, 'success' => FALSE];
    }

    $startTime = microtime(TRUE);

    try {
      $result = $this->executeLlmAnalysis($query, $queryType, $dataContext);
      $durationMs = (int) ((microtime(TRUE) - $startTime) * 1000);

      $analysisId = $this->persistAnalysis(
        $query, $queryType, $dataContext, $result, $tenantId, $durationMs
      );

      // Log to observability.
      if ($this->observability !== NULL && method_exists($this->observability, 'log')) {
        $this->observability->log([
          'agent_id' => 'causal_analytics',
          'action' => 'analyze_' . $queryType,
          'tier' => 'premium',
          'tenant_id' => $tenantId,
          'duration_ms' => $durationMs,
          'success' => TRUE,
          'cost' => $result['cost'] ?? 0,
        ]);
      }

      return [
        'success' => TRUE,
        'analysis_id' => $analysisId,
        'query' => $query,
        'query_type' => $queryType,
        'causal_factors' => $result['causal_factors'] ?? [],
        'recommendations' => $result['recommendations'] ?? [],
        'confidence_score' => $result['confidence_score'] ?? 0.0,
        'summary' => $result['summary'] ?? '',
        'duration_ms' => $durationMs,
        'cost' => $result['cost'] ?? 0.0,
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('GAP-L5-I: Causal analysis failed: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'error' => $e->getMessage(),
        'query' => $query,
        'query_type' => $queryType,
      ];
    }
  }

  /**
   * Executes the LLM analysis.
   *
   * @param string $query
   *   The query.
   * @param string $queryType
   *   The query type.
   * @param array $dataContext
   *   Structured data context.
   *
   * @return array
   *   LLM result with causal_factors, recommendations, confidence_score.
   */
  protected function executeLlmAnalysis(string $query, string $queryType, array $dataContext): array {
    if ($this->aiProvider === NULL) {
      // Return a structured fallback when no AI provider is configured.
      return $this->generateRuleBasedAnalysis($query, $queryType, $dataContext);
    }

    $prompt = $this->buildPrompt($query, $queryType, $dataContext);

    // Route to premium tier for complex causal reasoning.
    $modelConfig = ['provider_id' => 'anthropic', 'model_id' => 'claude-opus-4-6-20250515', 'tier' => 'premium'];
    if ($this->modelRouter !== NULL && method_exists($this->modelRouter, 'route')) {
      $modelConfig = $this->modelRouter->route('causal_analysis', $prompt, ['force_tier' => 'premium']);
    }

    // Execute via AI provider.
    try {
      $input = new \Drupal\ai\OperationType\Chat\ChatInput([
        new \Drupal\ai\OperationType\Chat\ChatMessage('user', $prompt),
      ]);

      $provider = $this->aiProvider->createInstance($modelConfig['provider_id']);
      $response = $provider->chat($input, $modelConfig['model_id'])->getNormalized();
      $text = $response->getText();

      return $this->parseAnalysisResponse($text, $modelConfig);
    }
    catch (\Throwable $e) {
      $this->logger->warning('GAP-L5-I: LLM analysis failed, using rule-based fallback: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return $this->generateRuleBasedAnalysis($query, $queryType, $dataContext);
    }
  }

  /**
   * Builds the analysis prompt.
   */
  protected function buildPrompt(string $query, string $queryType, array $dataContext): string {
    $typeInstructions = match ($queryType) {
      'diagnostic' => 'Analyze the root causes. Identify the primary and secondary factors that led to this outcome. Rank factors by impact.',
      'counterfactual' => 'Analyze what would happen under the proposed scenario. Use historical data patterns to estimate the likely outcome. Quantify the expected impact.',
      'predictive' => 'Based on current trends and the data provided, predict the likely outcome. Include confidence intervals and key assumptions.',
      'prescriptive' => 'Recommend specific, actionable steps to achieve the stated goal. Prioritize by expected impact and effort required.',
      default => 'Analyze the data and provide insights.',
    };

    $contextJson = json_encode($dataContext, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);

    return <<<PROMPT
You are a causal analytics engine. Analyze the following query using the data context provided.

QUERY: {$query}
QUERY TYPE: {$queryType}

INSTRUCTIONS: {$typeInstructions}

DATA CONTEXT:
{$contextJson}

Respond in JSON format:
{
  "summary": "Brief summary of the analysis",
  "causal_factors": [
    {"factor": "Factor name", "impact_weight": 0.0-1.0, "direction": "positive|negative", "evidence": "Brief evidence"}
  ],
  "recommendations": [
    {"action": "Specific action", "expected_impact": "Quantified impact", "priority": "high|medium|low", "effort": "low|medium|high"}
  ],
  "confidence_score": 0.0-1.0,
  "assumptions": ["Key assumption 1", "Key assumption 2"],
  "data_quality_notes": "Any concerns about the data provided"
}
PROMPT;
  }

  /**
   * Parses the LLM response into structured format.
   */
  protected function parseAnalysisResponse(string $text, array $modelConfig): array {
    // Extract JSON from the response.
    $jsonMatch = [];
    if (preg_match('/\{[\s\S]*\}/', $text, $jsonMatch)) {
      $parsed = json_decode($jsonMatch[0], TRUE);
      if ($parsed) {
        return [
          'summary' => $parsed['summary'] ?? '',
          'causal_factors' => $parsed['causal_factors'] ?? [],
          'recommendations' => $parsed['recommendations'] ?? [],
          'confidence_score' => (float) ($parsed['confidence_score'] ?? 0.5),
          'model_id' => $modelConfig['model_id'] ?? '',
          'cost' => $modelConfig['estimated_cost'] ?? 0.0,
        ];
      }
    }

    // Fallback: return raw text as summary.
    return [
      'summary' => $text,
      'causal_factors' => [],
      'recommendations' => [],
      'confidence_score' => 0.3,
      'model_id' => $modelConfig['model_id'] ?? '',
      'cost' => $modelConfig['estimated_cost'] ?? 0.0,
    ];
  }

  /**
   * Generates a rule-based analysis when no LLM is available.
   */
  protected function generateRuleBasedAnalysis(string $query, string $queryType, array $dataContext): array {
    $factors = [];
    $recommendations = [];

    // Extract basic patterns from data context.
    if (isset($dataContext['metrics'])) {
      foreach ($dataContext['metrics'] as $metric => $values) {
        if (is_array($values) && count($values) >= 2) {
          $trend = end($values) - reset($values);
          $factors[] = [
            'factor' => $metric,
            'impact_weight' => min(1.0, abs($trend) / (abs(reset($values)) ?: 1)),
            'direction' => $trend >= 0 ? 'positive' : 'negative',
            'evidence' => sprintf('Changed by %.2f over the period.', $trend),
          ];
        }
      }
    }

    $recommendations[] = [
      'action' => 'Enable AI provider for deeper causal analysis.',
      'expected_impact' => 'More accurate causal reasoning with LLM premium tier.',
      'priority' => 'high',
      'effort' => 'low',
    ];

    return [
      'summary' => 'Rule-based analysis (LLM not available). Limited to statistical pattern detection.',
      'causal_factors' => $factors,
      'recommendations' => $recommendations,
      'confidence_score' => 0.3,
      'model_id' => 'rule_based',
      'cost' => 0.0,
    ];
  }

  /**
   * Persists an analysis to the CausalAnalysis entity.
   *
   * @return int|null
   *   The entity ID, or NULL on failure.
   */
  protected function persistAnalysis(
    string $query,
    string $queryType,
    array $dataContext,
    array $result,
    string $tenantId,
    int $durationMs,
  ): ?int {
    try {
      $storage = $this->entityTypeManager->getStorage('causal_analysis');
      $entity = $storage->create([
        'query' => $query,
        'query_type' => $queryType,
        'data_context' => json_encode($dataContext, JSON_THROW_ON_ERROR),
        'analysis_result' => json_encode($result, JSON_THROW_ON_ERROR),
        'confidence_score' => $result['confidence_score'] ?? 0.0,
        'causal_factors' => json_encode($result['causal_factors'] ?? [], JSON_THROW_ON_ERROR),
        'recommendations' => json_encode($result['recommendations'] ?? [], JSON_THROW_ON_ERROR),
        'model_id' => $result['model_id'] ?? '',
        'cost' => $result['cost'] ?? 0.0,
        'duration_ms' => $durationMs,
        'tenant_id' => $tenantId,
      ]);
      $entity->save();
      return (int) $entity->id();
    }
    catch (\Throwable $e) {
      $this->logger->error('GAP-L5-I: Failed to persist analysis: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Gets recent analyses for a tenant.
   *
   * @param string $tenantId
   *   The tenant ID.
   * @param int $limit
   *   Maximum results.
   *
   * @return array
   *   Array of CausalAnalysis entities.
   */
  public function getRecentAnalyses(string $tenantId, int $limit = 20): array {
    try {
      $storage = $this->entityTypeManager->getStorage('causal_analysis');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->sort('created', 'DESC')
        ->range(0, $limit);

      if (!empty($tenantId)) {
        $query->condition('tenant_id', $tenantId);
      }

      $ids = $query->execute();
      return !empty($ids) ? $storage->loadMultiple($ids) : [];
    }
    catch (\Throwable $e) {
      $this->logger->error('GAP-L5-I: Failed to load analyses: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

}
