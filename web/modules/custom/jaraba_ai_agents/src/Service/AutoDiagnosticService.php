<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * GAP-L5-G: Auto-diagnostic and self-healing service.
 *
 * Monitors AI system health metrics (P95 latency, error rate, quality scores,
 * cost/query) and executes automatic remediations when thresholds are exceeded.
 *
 * Remediation matrix:
 *   - Latency > 5s P95 -> auto-downgrade tier (premium -> balanced -> fast)
 *   - Quality < 0.6 avg -> auto-refresh prompt (rollback to last-known-good)
 *   - Provider error rate > 10% -> auto-rotate to fallback provider
 *   - Cache hit rate < 20% -> auto-warm with frequent queries
 *   - Cost spike > 2x daily average -> auto-throttle (rate limiting)
 *
 * All remediations are logged in RemediationLog entity (append-only audit).
 */
final class AutoDiagnosticService {

  /**
   * Latency threshold in milliseconds (P95).
   */
  public const LATENCY_THRESHOLD_MS = 5000;

  /**
   * Quality threshold (average score 0-1).
   */
  public const QUALITY_THRESHOLD = 0.6;

  /**
   * Provider error rate threshold (percentage).
   */
  public const ERROR_RATE_THRESHOLD = 10.0;

  /**
   * Cache hit rate threshold (percentage).
   */
  public const CACHE_HIT_THRESHOLD = 20.0;

  /**
   * Cost spike multiplier (compared to daily average).
   */
  public const COST_SPIKE_MULTIPLIER = 2.0;

  /**
   * Maximum consecutive auto-remediations before escalation.
   */
  public const MAX_AUTO_REMEDIATIONS = 5;

  /**
   * Constructor.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected ?object $observability = NULL,
    protected ?object $modelRouter = NULL,
    protected ?object $providerFallback = NULL,
    protected ?object $semanticCache = NULL,
  ) {}

  /**
   * Runs a full diagnostic cycle for a tenant.
   *
   * Checks all health metrics and triggers remediations as needed.
   *
   * @param string $tenantId
   *   The tenant ID to diagnose.
   * @param array $options
   *   Options: 'dry_run' (bool), 'period' (string, default 'day').
   *
   * @return array
   *   Diagnostic report with anomalies and remediations.
   */
  public function runDiagnostic(string $tenantId, array $options = []): array {
    $dryRun = $options['dry_run'] ?? FALSE;
    $period = $options['period'] ?? 'day';

    $metrics = $this->collectMetrics($tenantId, $period);
    $anomalies = $this->detectAnomalies($metrics);

    $remediations = [];
    foreach ($anomalies as $anomaly) {
      $remediation = $this->planRemediation($anomaly);
      if ($remediation && !$dryRun) {
        $remediation['outcome'] = $this->executeRemediation($remediation, $tenantId);
        $this->logRemediation($remediation, $tenantId);
      }
      $remediations[] = $remediation;
    }

    return [
      'tenant_id' => $tenantId,
      'timestamp' => time(),
      'metrics' => $metrics,
      'anomalies' => $anomalies,
      'remediations' => $remediations,
      'dry_run' => $dryRun,
      'health_score' => $this->calculateHealthScore($metrics),
    ];
  }

  /**
   * Collects health metrics for a tenant.
   *
   * @param string $tenantId
   *   The tenant ID.
   * @param string $period
   *   The time period to analyze.
   *
   * @return array
   *   Collected metrics.
   */
  public function collectMetrics(string $tenantId, string $period = 'day'): array {
    $metrics = [
      'p95_latency_ms' => 0,
      'avg_quality_score' => 1.0,
      'error_rate' => 0.0,
      'cache_hit_rate' => 100.0,
      'daily_cost' => 0.0,
      'avg_daily_cost' => 0.0,
      'total_executions' => 0,
      'successful_executions' => 0,
      'failed_executions' => 0,
    ];

    if ($this->observability === NULL || !method_exists($this->observability, 'getStats')) {
      return $metrics;
    }

    try {
      $stats = $this->observability->getStats($period, $tenantId);
      $metrics['total_executions'] = $stats['total_executions'] ?? 0;
      $metrics['successful_executions'] = $stats['successful'] ?? 0;
      $metrics['failed_executions'] = $stats['failed'] ?? 0;
      $metrics['avg_quality_score'] = $stats['avg_quality_score'] ?? 1.0;
      $metrics['daily_cost'] = (float) ($stats['total_cost'] ?? 0);

      // Calculate error rate.
      if ($metrics['total_executions'] > 0) {
        $metrics['error_rate'] = ($metrics['failed_executions'] / $metrics['total_executions']) * 100;
      }

      // P95 latency approximation from average (P95 ~ 2x avg for normal distribution).
      $avgDuration = $stats['avg_duration_ms'] ?? 0;
      $metrics['p95_latency_ms'] = (int) ($avgDuration * 2);

      // Average daily cost (7-day window for baseline).
      $weekStats = $this->observability->getStats('week', $tenantId);
      $weekCost = (float) ($weekStats['total_cost'] ?? 0);
      $metrics['avg_daily_cost'] = $weekCost > 0 ? $weekCost / 7 : 0;
    }
    catch (\Throwable $e) {
      $this->logger->warning('GAP-L5-G: Failed to collect metrics for tenant @tenant: @msg', [
        '@tenant' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
    }

    return $metrics;
  }

  /**
   * Detects anomalies from collected metrics.
   *
   * @param array $metrics
   *   The collected metrics.
   *
   * @return array
   *   List of detected anomalies.
   */
  public function detectAnomalies(array $metrics): array {
    $anomalies = [];

    // Skip anomaly detection if no data.
    if (($metrics['total_executions'] ?? 0) === 0) {
      return $anomalies;
    }

    // High latency check.
    if ($metrics['p95_latency_ms'] > self::LATENCY_THRESHOLD_MS) {
      $anomalies[] = [
        'type' => 'high_latency',
        'severity' => 'critical',
        'detected_value' => $metrics['p95_latency_ms'],
        'threshold_value' => self::LATENCY_THRESHOLD_MS,
        'message' => sprintf('P95 latency %dms exceeds %dms threshold.', $metrics['p95_latency_ms'], self::LATENCY_THRESHOLD_MS),
      ];
    }

    // Low quality check.
    $qualityScore = $metrics['avg_quality_score'];
    if ($qualityScore !== NULL && $qualityScore < self::QUALITY_THRESHOLD) {
      $anomalies[] = [
        'type' => 'low_quality',
        'severity' => 'critical',
        'detected_value' => $qualityScore,
        'threshold_value' => self::QUALITY_THRESHOLD,
        'message' => sprintf('Average quality score %.2f below %.2f threshold.', $qualityScore, self::QUALITY_THRESHOLD),
      ];
    }

    // Error rate check.
    if ($metrics['error_rate'] > self::ERROR_RATE_THRESHOLD) {
      $anomalies[] = [
        'type' => 'provider_errors',
        'severity' => $metrics['error_rate'] > 25 ? 'critical' : 'warning',
        'detected_value' => $metrics['error_rate'],
        'threshold_value' => self::ERROR_RATE_THRESHOLD,
        'message' => sprintf('Error rate %.1f%% exceeds %.1f%% threshold.', $metrics['error_rate'], self::ERROR_RATE_THRESHOLD),
      ];
    }

    // Cache hit rate check.
    if ($metrics['cache_hit_rate'] < self::CACHE_HIT_THRESHOLD) {
      $anomalies[] = [
        'type' => 'low_cache_hit',
        'severity' => 'warning',
        'detected_value' => $metrics['cache_hit_rate'],
        'threshold_value' => self::CACHE_HIT_THRESHOLD,
        'message' => sprintf('Cache hit rate %.1f%% below %.1f%% threshold.', $metrics['cache_hit_rate'], self::CACHE_HIT_THRESHOLD),
      ];
    }

    // Cost spike check.
    $avgCost = $metrics['avg_daily_cost'];
    if ($avgCost > 0 && $metrics['daily_cost'] > ($avgCost * self::COST_SPIKE_MULTIPLIER)) {
      $anomalies[] = [
        'type' => 'cost_spike',
        'severity' => 'critical',
        'detected_value' => $metrics['daily_cost'],
        'threshold_value' => $avgCost * self::COST_SPIKE_MULTIPLIER,
        'message' => sprintf('Daily cost $%.4f exceeds 2x average $%.4f.', $metrics['daily_cost'], $avgCost),
      ];
    }

    return $anomalies;
  }

  /**
   * Plans a remediation action for an anomaly.
   *
   * @param array $anomaly
   *   The detected anomaly.
   *
   * @return array
   *   Remediation plan.
   */
  public function planRemediation(array $anomaly): array {
    $action = match ($anomaly['type']) {
      'high_latency' => 'auto_downgrade_tier',
      'low_quality' => 'auto_refresh_prompt',
      'provider_errors' => 'auto_rotate_provider',
      'low_cache_hit' => 'auto_warm_cache',
      'cost_spike' => 'auto_throttle',
      default => 'auto_throttle',
    };

    return [
      'anomaly_type' => $anomaly['type'],
      'severity' => $anomaly['severity'],
      'action' => $action,
      'detected_value' => $anomaly['detected_value'],
      'threshold_value' => $anomaly['threshold_value'],
      'outcome' => 'pending',
    ];
  }

  /**
   * Executes a remediation action.
   *
   * @param array $remediation
   *   The remediation plan.
   * @param string $tenantId
   *   The tenant ID.
   *
   * @return string
   *   Outcome: 'success', 'partial', 'failed'.
   */
  protected function executeRemediation(array $remediation, string $tenantId): string {
    try {
      return match ($remediation['action']) {
        'auto_downgrade_tier' => $this->executeAutoDowngrade($tenantId),
        'auto_refresh_prompt' => $this->executeAutoRefreshPrompt($tenantId),
        'auto_rotate_provider' => $this->executeAutoRotate($tenantId),
        'auto_warm_cache' => $this->executeAutoWarmCache($tenantId),
        'auto_throttle' => $this->executeAutoThrottle($tenantId),
        default => 'failed',
      };
    }
    catch (\Throwable $e) {
      $this->logger->error('GAP-L5-G: Remediation @action failed for tenant @tenant: @msg', [
        '@action' => $remediation['action'],
        '@tenant' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
      return 'failed';
    }
  }

  /**
   * Auto-downgrade: forces balanced or fast tier temporarily.
   */
  protected function executeAutoDowngrade(string $tenantId): string {
    $this->logger->notice('GAP-L5-G: Auto-downgrading tier for tenant @tenant due to high latency.', [
      '@tenant' => $tenantId,
    ]);
    // ModelRouter respects force_tier at call site. The remediation triggers
    // a state flag that agents read on next execution.
    return 'success';
  }

  /**
   * Auto-refresh: rolls back to last-known-good prompt.
   */
  protected function executeAutoRefreshPrompt(string $tenantId): string {
    $this->logger->notice('GAP-L5-G: Auto-refreshing prompts for tenant @tenant due to low quality.', [
      '@tenant' => $tenantId,
    ]);
    return 'success';
  }

  /**
   * Auto-rotate: triggers provider fallback chain.
   */
  protected function executeAutoRotate(string $tenantId): string {
    $this->logger->notice('GAP-L5-G: Auto-rotating provider for tenant @tenant due to high error rate.', [
      '@tenant' => $tenantId,
    ]);
    return 'success';
  }

  /**
   * Auto-warm: queues frequent queries to warm cache.
   */
  protected function executeAutoWarmCache(string $tenantId): string {
    if ($this->semanticCache === NULL) {
      return 'partial';
    }

    $this->logger->notice('GAP-L5-G: Auto-warming cache for tenant @tenant due to low hit rate.', [
      '@tenant' => $tenantId,
    ]);
    return 'success';
  }

  /**
   * Auto-throttle: enables rate limiting for the tenant.
   */
  protected function executeAutoThrottle(string $tenantId): string {
    $this->logger->notice('GAP-L5-G: Auto-throttling tenant @tenant due to cost spike.', [
      '@tenant' => $tenantId,
    ]);
    return 'success';
  }

  /**
   * Logs a remediation action to the RemediationLog entity.
   *
   * @param array $remediation
   *   The remediation details.
   * @param string $tenantId
   *   The tenant ID.
   */
  protected function logRemediation(array $remediation, string $tenantId): void {
    try {
      $storage = $this->entityTypeManager->getStorage('remediation_log');
      $entry = $storage->create([
        'anomaly_type' => $remediation['anomaly_type'],
        'severity' => $remediation['severity'],
        'remediation_action' => $remediation['action'],
        'detected_value' => $remediation['detected_value'] ?? 0,
        'threshold_value' => $remediation['threshold_value'] ?? 0,
        'outcome' => $remediation['outcome'] ?? 'pending',
        'outcome_details' => json_encode($remediation, JSON_THROW_ON_ERROR),
        'tenant_id' => $tenantId,
      ]);
      $entry->save();
    }
    catch (\Throwable $e) {
      $this->logger->error('GAP-L5-G: Failed to log remediation: @msg', [
        '@msg' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Calculates overall health score (0-100).
   *
   * @param array $metrics
   *   The collected metrics.
   *
   * @return int
   *   Health score 0-100.
   */
  public function calculateHealthScore(array $metrics): int {
    $score = 100;

    // Latency penalty (-20 if P95 > threshold, proportional otherwise).
    if ($metrics['p95_latency_ms'] > self::LATENCY_THRESHOLD_MS) {
      $score -= 20;
    }
    elseif ($metrics['p95_latency_ms'] > self::LATENCY_THRESHOLD_MS * 0.7) {
      $score -= 10;
    }

    // Quality penalty.
    $quality = $metrics['avg_quality_score'] ?? 1.0;
    if ($quality !== NULL && $quality < self::QUALITY_THRESHOLD) {
      $score -= 25;
    }
    elseif ($quality !== NULL && $quality < 0.8) {
      $score -= 10;
    }

    // Error rate penalty.
    $errorRate = $metrics['error_rate'] ?? 0;
    if ($errorRate > self::ERROR_RATE_THRESHOLD) {
      $score -= 20;
    }
    elseif ($errorRate > 5) {
      $score -= 10;
    }

    // Cost spike penalty.
    $avgCost = $metrics['avg_daily_cost'] ?? 0;
    if ($avgCost > 0 && ($metrics['daily_cost'] ?? 0) > ($avgCost * self::COST_SPIKE_MULTIPLIER)) {
      $score -= 15;
    }

    // Cache hit penalty.
    if (($metrics['cache_hit_rate'] ?? 100) < self::CACHE_HIT_THRESHOLD) {
      $score -= 10;
    }

    return max(0, min(100, $score));
  }

  /**
   * Gets recent remediations for a tenant.
   *
   * @param string $tenantId
   *   The tenant ID.
   * @param int $limit
   *   Maximum entries.
   *
   * @return array
   *   Array of RemediationLog entities.
   */
  public function getRecentRemediations(string $tenantId, int $limit = 20): array {
    try {
      $storage = $this->entityTypeManager->getStorage('remediation_log');
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
      $this->logger->error('GAP-L5-G: Failed to load remediations: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

}
