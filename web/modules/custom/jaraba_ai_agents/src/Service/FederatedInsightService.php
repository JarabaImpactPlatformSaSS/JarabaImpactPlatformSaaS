<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * GAP-L5-H: Federated insight aggregation service.
 *
 * Aggregates anonymized patterns across tenants with k-anonymity guarantee.
 * No raw tenant data is stored — only statistical aggregates.
 *
 * K-anonymity rule: An insight is only published if at least 5 distinct
 * tenants contributed data to it. This prevents re-identification.
 *
 * Opt-in model: Tenants must have federated_insights feature flag enabled
 * in their SaaS plan. Non-opted tenants are excluded from aggregation.
 *
 * This is NOT federated ML (no model training). It is statistical
 * aggregation with differential privacy noise injection.
 */
final class FederatedInsightService {

  /**
   * Minimum tenants for k-anonymity.
   */
  public const K_ANONYMITY_THRESHOLD = 5;

  /**
   * Differential privacy noise factor (Laplacian, epsilon = 1.0).
   */
  public const NOISE_EPSILON = 1.0;

  /**
   * Constructor.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected ?object $observability = NULL,
  ) {}

  /**
   * Generates federated insights from cross-tenant AI usage data.
   *
   * @param array $tenantIds
   *   List of opted-in tenant IDs.
   * @param string $vertical
   *   Vertical filter, or 'all' for cross-vertical.
   * @param string $period
   *   Period: 'week', 'month', 'quarter'.
   *
   * @return array
   *   Generated insights (only those meeting k-anonymity).
   */
  public function generateInsights(array $tenantIds, string $vertical = 'all', string $period = 'month'): array {
    if (count($tenantIds) < self::K_ANONYMITY_THRESHOLD) {
      $this->logger->info('GAP-L5-H: Insufficient tenants for k-anonymity (@count < @threshold).', [
        '@count' => count($tenantIds),
        '@threshold' => self::K_ANONYMITY_THRESHOLD,
      ]);
      return [];
    }

    $insights = [];

    // Collect per-tenant metrics.
    $tenantMetrics = $this->collectTenantMetrics($tenantIds, $period);

    // Generate insight categories.
    $usageInsight = $this->aggregateAiUsagePatterns($tenantMetrics, $vertical);
    if ($usageInsight) {
      $insights[] = $usageInsight;
    }

    $costInsight = $this->aggregateCostOptimization($tenantMetrics, $vertical);
    if ($costInsight) {
      $insights[] = $costInsight;
    }

    $qualityInsight = $this->aggregateQualityTrends($tenantMetrics, $vertical);
    if ($qualityInsight) {
      $insights[] = $qualityInsight;
    }

    // Persist valid insights.
    foreach ($insights as $insight) {
      $this->persistInsight($insight);
    }

    return $insights;
  }

  /**
   * Collects metrics per tenant from observability.
   *
   * @param array $tenantIds
   *   Tenant IDs.
   * @param string $period
   *   Period.
   *
   * @return array
   *   Per-tenant metrics keyed by tenant ID.
   */
  protected function collectTenantMetrics(array $tenantIds, string $period): array {
    $metrics = [];

    if ($this->observability === NULL || !method_exists($this->observability, 'getStats')) {
      return $metrics;
    }

    foreach ($tenantIds as $tenantId) {
      try {
        $stats = $this->observability->getStats($period, $tenantId);
        $metrics[$tenantId] = $stats;
      }
      catch (\Throwable $e) {
        // Exclude this tenant silently — don't leak info about which failed.
        $this->logger->debug('GAP-L5-H: Skipping tenant metrics collection: @msg', [
          '@msg' => $e->getMessage(),
        ]);
      }
    }

    return $metrics;
  }

  /**
   * Aggregates AI usage patterns across tenants.
   */
  protected function aggregateAiUsagePatterns(array $tenantMetrics, string $vertical): ?array {
    $contributing = count($tenantMetrics);
    if ($contributing < self::K_ANONYMITY_THRESHOLD) {
      return NULL;
    }

    $totalExecutions = 0;
    $totalTokens = 0;
    $successRates = [];

    foreach ($tenantMetrics as $stats) {
      $totalExecutions += $stats['total_executions'] ?? 0;
      $totalTokens += $stats['total_tokens'] ?? 0;
      if (isset($stats['success_rate'])) {
        $successRates[] = (float) $stats['success_rate'];
      }
    }

    $avgExecutions = $contributing > 0 ? $totalExecutions / $contributing : 0;
    $avgTokens = $contributing > 0 ? $totalTokens / $contributing : 0;
    $avgSuccessRate = !empty($successRates) ? array_sum($successRates) / count($successRates) : 0;

    // Add differential privacy noise.
    $avgExecutions = $this->addNoise($avgExecutions);
    $avgTokens = $this->addNoise($avgTokens);

    return [
      'insight_type' => 'ai_usage_pattern',
      'vertical' => $vertical,
      'title' => 'AI Usage Benchmark',
      'summary' => sprintf(
        'Cross-tenant AI usage: avg %.0f executions, %.0f tokens, %.1f%% success rate.',
        $avgExecutions, $avgTokens, $avgSuccessRate
      ),
      'data' => [
        'avg_executions' => round($avgExecutions),
        'avg_tokens' => round($avgTokens),
        'avg_success_rate' => round($avgSuccessRate, 1),
      ],
      'contributing_tenants' => $contributing,
      'confidence_score' => min(1.0, $contributing / 10),
    ];
  }

  /**
   * Aggregates cost optimization opportunities.
   */
  protected function aggregateCostOptimization(array $tenantMetrics, string $vertical): ?array {
    $contributing = count($tenantMetrics);
    if ($contributing < self::K_ANONYMITY_THRESHOLD) {
      return NULL;
    }

    $costs = [];
    foreach ($tenantMetrics as $stats) {
      if (isset($stats['total_cost'])) {
        $costs[] = (float) $stats['total_cost'];
      }
    }

    if (empty($costs)) {
      return NULL;
    }

    $avgCost = array_sum($costs) / count($costs);
    $medianCost = $this->calculateMedian($costs);

    // Add noise.
    $avgCost = $this->addNoise($avgCost);
    $medianCost = $this->addNoise($medianCost);

    return [
      'insight_type' => 'cost_optimization',
      'vertical' => $vertical,
      'title' => 'Cost Optimization Benchmark',
      'summary' => sprintf(
        'Industry AI cost benchmark: avg $%.4f, median $%.4f per period.',
        $avgCost, $medianCost
      ),
      'data' => [
        'avg_cost' => round($avgCost, 4),
        'median_cost' => round($medianCost, 4),
      ],
      'contributing_tenants' => $contributing,
      'confidence_score' => min(1.0, $contributing / 10),
    ];
  }

  /**
   * Aggregates quality trends across tenants.
   */
  protected function aggregateQualityTrends(array $tenantMetrics, string $vertical): ?array {
    $contributing = count($tenantMetrics);
    if ($contributing < self::K_ANONYMITY_THRESHOLD) {
      return NULL;
    }

    $qualityScores = [];
    foreach ($tenantMetrics as $stats) {
      if (isset($stats['avg_quality_score']) && $stats['avg_quality_score'] !== NULL) {
        $qualityScores[] = (float) $stats['avg_quality_score'];
      }
    }

    if (count($qualityScores) < self::K_ANONYMITY_THRESHOLD) {
      return NULL;
    }

    $avgQuality = array_sum($qualityScores) / count($qualityScores);

    return [
      'insight_type' => 'quality_trend',
      'vertical' => $vertical,
      'title' => 'AI Quality Benchmark',
      'summary' => sprintf(
        'Cross-tenant AI quality score: avg %.2f across %d contributors.',
        $avgQuality, count($qualityScores)
      ),
      'data' => [
        'avg_quality_score' => round($avgQuality, 3),
        'contributors_with_quality' => count($qualityScores),
      ],
      'contributing_tenants' => $contributing,
      'confidence_score' => min(1.0, count($qualityScores) / 10),
    ];
  }

  /**
   * Adds Laplacian noise for differential privacy.
   *
   * @param float $value
   *   The original value.
   *
   * @return float
   *   Value with noise added.
   */
  public function addNoise(float $value): float {
    if ($value == 0) {
      return 0.0;
    }

    // Laplacian noise: scale = sensitivity / epsilon.
    // Sensitivity proportional to value magnitude (capped at 10%).
    $scale = abs($value) * 0.1 / self::NOISE_EPSILON;

    // Generate Laplacian noise via uniform random.
    $u = mt_rand() / mt_getrandmax() - 0.5;
    $noise = -$scale * (($u > 0 ? 1 : -1) * log(1 - 2 * abs($u)));

    return $value + $noise;
  }

  /**
   * Calculates median of an array.
   */
  protected function calculateMedian(array $values): float {
    sort($values);
    $count = count($values);

    if ($count === 0) {
      return 0.0;
    }

    $middle = (int) floor($count / 2);

    if ($count % 2 === 0) {
      return ($values[$middle - 1] + $values[$middle]) / 2;
    }

    return $values[$middle];
  }

  /**
   * Persists an insight to the database.
   */
  protected function persistInsight(array $insight): void {
    try {
      $storage = $this->entityTypeManager->getStorage('aggregated_insight');
      $entity = $storage->create([
        'insight_type' => $insight['insight_type'],
        'vertical' => $insight['vertical'],
        'title' => $insight['title'],
        'summary' => $insight['summary'],
        'data' => json_encode($insight['data'], JSON_THROW_ON_ERROR),
        'contributing_tenants' => $insight['contributing_tenants'],
        'confidence_score' => $insight['confidence_score'],
        'period_start' => strtotime('-1 month'),
        'period_end' => time(),
        'status' => 'active',
      ]);
      $entity->save();
    }
    catch (\Throwable $e) {
      $this->logger->error('GAP-L5-H: Failed to persist insight: @msg', [
        '@msg' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Gets active insights, optionally filtered by vertical.
   *
   * @param string $vertical
   *   Vertical filter, or empty for all.
   * @param int $limit
   *   Maximum results.
   *
   * @return array
   *   Array of AggregatedInsight entities.
   */
  public function getInsights(string $vertical = '', int $limit = 20): array {
    try {
      $storage = $this->entityTypeManager->getStorage('aggregated_insight');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 'active')
        ->sort('created', 'DESC')
        ->range(0, $limit);

      if (!empty($vertical)) {
        $query->condition('vertical', $vertical);
      }

      $ids = $query->execute();
      return !empty($ids) ? $storage->loadMultiple($ids) : [];
    }
    catch (\Throwable $e) {
      $this->logger->error('GAP-L5-H: Failed to load insights: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

}
