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
   * State key prefix for tier override flags.
   */
  protected const STATE_TIER_OVERRIDE = 'jaraba_ai_agents.tier_override.';

  /**
   * State key prefix for provider rotation flags.
   */
  protected const STATE_PROVIDER_ROTATED = 'jaraba_ai_agents.provider_rotated.';

  /**
   * State key prefix for throttle flags.
   */
  protected const STATE_THROTTLE = 'jaraba_ai_agents.throttle.';

  /**
   * Duration (seconds) for temporary tier overrides.
   */
  protected const TIER_OVERRIDE_TTL = 3600;

  /**
   * Duration (seconds) for provider rotation.
   */
  protected const PROVIDER_ROTATION_TTL = 1800;

  /**
   * Duration (seconds) for throttle flag.
   */
  protected const THROTTLE_TTL = 7200;

  /**
   * Auto-downgrade: forces fast tier temporarily via State API.
   *
   * Sets a state flag that ModelRouter reads at call time via
   * $options['force_tier']. Agents call getTierOverride() before routing.
   * Override auto-expires after TIER_OVERRIDE_TTL seconds.
   */
  protected function executeAutoDowngrade(string $tenantId): string {
    $state = \Drupal::state();
    $key = self::STATE_TIER_OVERRIDE . $tenantId;

    $state->set($key, [
      'tier' => 'fast',
      'reason' => 'auto_downgrade_high_latency',
      'expires' => time() + self::TIER_OVERRIDE_TTL,
      'previous_tier' => 'balanced',
    ]);

    $this->logger->notice('GAP-L5-G: Auto-downgrading to fast tier for tenant @tenant (TTL: @ttl s).', [
      '@tenant' => $tenantId,
      '@ttl' => self::TIER_OVERRIDE_TTL,
    ]);

    return 'success';
  }

  /**
   * Auto-refresh: rolls back auto-generated prompts to last-known-good.
   *
   * Finds PromptTemplate entities with auto_generated=TRUE for the tenant,
   * and resets their template_text to the last verified version.
   */
  protected function executeAutoRefreshPrompt(string $tenantId): string {
    try {
      $storage = $this->entityTypeManager->getStorage('prompt_template');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('auto_generated', TRUE)
        ->condition('tenant_id', $tenantId);

      $ids = $query->execute();
      if (empty($ids)) {
        $this->logger->notice('GAP-L5-G: No auto-generated prompts to refresh for tenant @tenant.', [
          '@tenant' => $tenantId,
        ]);
        return 'success';
      }

      $templates = $storage->loadMultiple($ids);
      $refreshed = 0;

      foreach ($templates as $template) {
        // Roll back to original_text if available, otherwise mark for review.
        $originalText = $template->get('original_text')->value ?? NULL;
        if ($originalText) {
          $template->set('template_text', $originalText);
          $template->set('auto_generated', FALSE);
          $template->set('constitutional_status', 'needs_review');
          $template->save();
          $refreshed++;
        }
      }

      $this->logger->notice('GAP-L5-G: Refreshed @count prompts for tenant @tenant.', [
        '@count' => $refreshed,
        '@tenant' => $tenantId,
      ]);

      return $refreshed > 0 ? 'success' : 'partial';
    }
    catch (\Throwable $e) {
      $this->logger->error('GAP-L5-G: Prompt refresh failed for tenant @tenant: @msg', [
        '@tenant' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
      return 'failed';
    }
  }

  /**
   * Auto-rotate: marks the primary provider as temporarily unavailable.
   *
   * Sets a state flag so that ProviderFallbackService skips the primary
   * provider and uses the next one in the fallback chain.
   */
  protected function executeAutoRotate(string $tenantId): string {
    $state = \Drupal::state();
    $key = self::STATE_PROVIDER_ROTATED . $tenantId;

    $state->set($key, [
      'rotated' => TRUE,
      'reason' => 'auto_rotate_high_error_rate',
      'expires' => time() + self::PROVIDER_ROTATION_TTL,
    ]);

    $this->logger->notice('GAP-L5-G: Auto-rotating provider for tenant @tenant (TTL: @ttl s).', [
      '@tenant' => $tenantId,
      '@ttl' => self::PROVIDER_ROTATION_TTL,
    ]);

    return 'success';
  }

  /**
   * Auto-warm: pre-populates semantic cache with frequent queries.
   *
   * Reads the most frequent recent queries from observability data
   * and ensures they are present in the semantic cache.
   */
  protected function executeAutoWarmCache(string $tenantId): string {
    if ($this->semanticCache === NULL) {
      $this->logger->info('GAP-L5-G: SemanticCacheService not available; cache warm skipped for @tenant.', [
        '@tenant' => $tenantId,
      ]);
      return 'partial';
    }

    if ($this->observability === NULL || !method_exists($this->observability, 'getFrequentQueries')) {
      $this->logger->info('GAP-L5-G: Observability lacks getFrequentQueries(); cache warm skipped for @tenant.', [
        '@tenant' => $tenantId,
      ]);
      return 'partial';
    }

    try {
      $frequentQueries = $this->observability->getFrequentQueries($tenantId, 20);
      $warmed = 0;

      foreach ($frequentQueries as $queryData) {
        $query = $queryData['query'] ?? '';
        $mode = $queryData['mode'] ?? 'chat';
        if (empty($query)) {
          continue;
        }

        // Check if already cached.
        $cached = $this->semanticCache->get($query, $mode, $tenantId);
        if ($cached === NULL && !empty($queryData['last_response'])) {
          $this->semanticCache->set($query, $queryData['last_response'], $mode, $tenantId);
          $warmed++;
        }
      }

      $this->logger->notice('GAP-L5-G: Warmed @count cache entries for tenant @tenant.', [
        '@count' => $warmed,
        '@tenant' => $tenantId,
      ]);

      return $warmed > 0 ? 'success' : 'partial';
    }
    catch (\Throwable $e) {
      $this->logger->error('GAP-L5-G: Cache warm failed for tenant @tenant: @msg', [
        '@tenant' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
      return 'failed';
    }
  }

  /**
   * Auto-throttle: enables rate limiting for the tenant.
   *
   * Sets a state flag with a reduced requests-per-minute cap.
   * Services check this flag before processing new requests.
   */
  protected function executeAutoThrottle(string $tenantId): string {
    $state = \Drupal::state();
    $key = self::STATE_THROTTLE . $tenantId;

    $state->set($key, [
      'throttled' => TRUE,
      'max_requests_per_minute' => 10,
      'reason' => 'auto_throttle_cost_spike',
      'expires' => time() + self::THROTTLE_TTL,
    ]);

    $this->logger->notice('GAP-L5-G: Auto-throttling tenant @tenant to 10 req/min (TTL: @ttl s).', [
      '@tenant' => $tenantId,
      '@ttl' => self::THROTTLE_TTL,
    ]);

    return 'success';
  }

  /**
   * Gets the active tier override for a tenant, if any.
   *
   * Agents should call this before ModelRouter::route() and pass the result
   * as $options['force_tier'] if not NULL.
   *
   * @param string $tenantId
   *   The tenant ID.
   *
   * @return string|null
   *   The forced tier name ('fast', 'balanced') or NULL if no override.
   */
  public function getTierOverride(string $tenantId): ?string {
    $state = \Drupal::state();
    $data = $state->get(self::STATE_TIER_OVERRIDE . $tenantId);

    if (!is_array($data)) {
      return NULL;
    }

    // Auto-expire.
    if (isset($data['expires']) && time() > $data['expires']) {
      $state->delete(self::STATE_TIER_OVERRIDE . $tenantId);
      return NULL;
    }

    return $data['tier'] ?? NULL;
  }

  /**
   * Checks whether the provider is rotated for a tenant.
   *
   * @param string $tenantId
   *   The tenant ID.
   *
   * @return bool
   *   TRUE if the primary provider should be skipped.
   */
  public function isProviderRotated(string $tenantId): bool {
    $state = \Drupal::state();
    $data = $state->get(self::STATE_PROVIDER_ROTATED . $tenantId);

    if (!is_array($data)) {
      return FALSE;
    }

    if (isset($data['expires']) && time() > $data['expires']) {
      $state->delete(self::STATE_PROVIDER_ROTATED . $tenantId);
      return FALSE;
    }

    return !empty($data['rotated']);
  }

  /**
   * Gets the throttle configuration for a tenant.
   *
   * @param string $tenantId
   *   The tenant ID.
   *
   * @return array|null
   *   Throttle config ['max_requests_per_minute' => int] or NULL.
   */
  public function getThrottleConfig(string $tenantId): ?array {
    $state = \Drupal::state();
    $data = $state->get(self::STATE_THROTTLE . $tenantId);

    if (!is_array($data)) {
      return NULL;
    }

    if (isset($data['expires']) && time() > $data['expires']) {
      $state->delete(self::STATE_THROTTLE . $tenantId);
      return NULL;
    }

    return $data;
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
