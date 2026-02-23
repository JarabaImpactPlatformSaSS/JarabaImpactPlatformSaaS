<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\jaraba_customer_success\Entity\VerticalRetentionProfileInterface;
use Psr\Log\LoggerInterface;

/**
 * Vertical retention evaluation engine.
 *
 * Orchestrates health score re-weighting, vertical-specific churn signal
 * evaluation, seasonal inactivity detection, and playbook selection
 * for each tenant based on their vertical profile.
 */
class VerticalRetentionService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected HealthScoreCalculatorService $healthCalculator,
    protected EngagementScoringService $engagementScoring,
    protected LifecycleStageService $lifecycleStage,
    protected StateInterface $state,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Evaluates a tenant's retention risk with vertical-specific logic.
   *
   * @param string $tenantId
   *   The tenant group entity ID.
   *
   * @return array
   *   Evaluation result with keys: tenant_id, vertical_id, health_score,
   *   adjusted_health_score, risk_level, is_seasonal_inactivity,
   *   signals_triggered, recommended_action, recommended_playbook_id,
   *   seasonal_context.
   */
  public function evaluateTenant(string $tenantId): array {
    $profile = $this->getProfileForTenant($tenantId);

    // Fallback to generic evaluation if no vertical profile exists.
    if (!$profile) {
      return $this->buildGenericEvaluation($tenantId);
    }

    // 1. Calculate base health score.
    $healthEntity = $this->healthCalculator->calculate($tenantId);
    $baseScore = $healthEntity ? (int) $healthEntity->get('overall_score')->value : 50;

    // 2. Re-weight with vertical weights.
    $adjustedScore = $this->reweightHealthScore($healthEntity, $profile);

    // 3. Get seasonal context.
    $currentMonth = (int) date('n');
    $seasonalAdj = $profile->getSeasonalAdjustment($currentMonth);
    $expectedPattern = $profile->getExpectedUsagePattern()[$currentMonth] ?? 'medium';

    // 4. Evaluate vertical-specific signals.
    $signals = $this->evaluateVerticalSignals($tenantId, $profile);
    $signalWeight = array_sum(array_column($signals, 'weight'));

    // 5. Determine seasonal vs real inactivity.
    $isSeasonalInactivity = $this->isSeasonalInactivity(
      $tenantId,
      $profile,
      $currentMonth
    );

    // 6. Calculate risk level.
    $riskScore = $this->calculateRiskScore(
      $adjustedScore,
      $signalWeight,
      $seasonalAdj,
      $isSeasonalInactivity
    );
    $riskLevel = $this->classifyRisk($riskScore);

    // 7. Select recommended action and playbook.
    $recommendedAction = $this->determineAction($riskLevel, $isSeasonalInactivity);
    $playbookId = $this->selectPlaybook($profile, $riskLevel);

    return [
      'tenant_id' => $tenantId,
      'vertical_id' => $profile->getVerticalId(),
      'health_score' => $baseScore,
      'adjusted_health_score' => $adjustedScore,
      'risk_level' => $riskLevel,
      'is_seasonal_inactivity' => $isSeasonalInactivity,
      'signals_triggered' => $signals,
      'recommended_action' => $recommendedAction,
      'recommended_playbook_id' => $playbookId,
      'seasonal_context' => [
        'month' => $currentMonth,
        'adjustment' => $seasonalAdj,
        'expected_pattern' => $expectedPattern,
      ],
    ];
  }

  /**
   * Evaluates vertical-specific churn signals for a tenant.
   *
   * @return array
   *   Array of triggered signals with signal_id, weight, description.
   */
  public function evaluateVerticalSignals(string $tenantId, VerticalRetentionProfileInterface $profile): array {
    $triggered = [];
    $signals = $profile->getChurnRiskSignals();

    foreach ($signals as $signal) {
      $metricValue = $this->getMetricValue($tenantId, $signal['metric'], (int) ($signal['lookback_days'] ?? 30));
      if ($this->evaluateCondition($metricValue, $signal['operator'], $signal['threshold'])) {
        $triggered[] = [
          'signal_id' => $signal['signal_id'],
          'weight' => (float) $signal['weight'],
          'description' => $signal['description'] ?? '',
          'metric_value' => $metricValue,
        ];
      }
    }

    return $triggered;
  }

  /**
   * Gets the vertical retention profile for a given tenant.
   */
  public function getProfileForTenant(string $tenantId): ?VerticalRetentionProfileInterface {
    // Check State cache.
    $cacheKey = 'jaraba_cs.retention_profile.' . $tenantId;
    $cachedProfileId = $this->state->get($cacheKey);
    $cacheTimestamp = $this->state->get($cacheKey . '.ts', 0);

    // Cache valid for 24 hours.
    if ($cachedProfileId && (time() - $cacheTimestamp) < 86400) {
      $profile = $this->entityTypeManager
        ->getStorage('vertical_retention_profile')
        ->load($cachedProfileId);
      if ($profile instanceof VerticalRetentionProfileInterface && $profile->isActive()) {
        return $profile;
      }
    }

    // Resolve vertical from tenant group type.
    $verticalId = $this->resolveVerticalId($tenantId);
    if (!$verticalId) {
      return NULL;
    }

    // Query profile by vertical_id.
    $profiles = $this->entityTypeManager
      ->getStorage('vertical_retention_profile')
      ->loadByProperties([
        'vertical_id' => $verticalId,
        'status' => VerticalRetentionProfileInterface::STATUS_ACTIVE,
      ]);

    $profile = reset($profiles) ?: NULL;

    // Cache the result.
    if ($profile) {
      $this->state->set($cacheKey, $profile->id());
      $this->state->set($cacheKey . '.ts', time());
    }

    return $profile;
  }

  /**
   * Gets a full risk assessment for API consumption.
   *
   * @return array
   *   Combined evaluation + prediction history.
   */
  public function getRiskAssessment(string $tenantId): array {
    $evaluation = $this->evaluateTenant($tenantId);

    // Get prediction history from SeasonalChurnService.
    // This will be injected at runtime via the controller.
    $evaluation['prediction_history'] = [];

    return $evaluation;
  }

  /**
   * Runs batch evaluation for all active tenants.
   *
   * @return int
   *   Number of tenants evaluated.
   */
  public function runBatchEvaluation(): int {
    $count = 0;

    try {
      $tenants = $this->entityTypeManager
        ->getStorage('group')
        ->getQuery()
        ->accessCheck(TRUE)
        ->condition('type', 'tenant')
        ->execute();

      foreach ($tenants as $tenantId) {
        try {
          $this->evaluateTenant((string) $tenantId);
          $count++;
        }
        catch (\Exception $e) {
          $this->logger->error('Retention evaluation failed for tenant @id: @error', [
            '@id' => $tenantId,
            '@error' => $e->getMessage(),
          ]);
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Batch retention evaluation failed: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    $this->state->set('jaraba_cs.retention_last_batch', time());
    $this->logger->info('Vertical retention batch completed: @count tenants evaluated.', [
      '@count' => $count,
    ]);

    return $count;
  }

  /**
   * Re-weights health score with vertical-specific weights.
   */
  protected function reweightHealthScore($healthEntity, VerticalRetentionProfileInterface $profile): int {
    if (!$healthEntity) {
      return 50;
    }

    $weights = $profile->getHealthScoreWeights();
    $engagement = (int) $healthEntity->get('engagement_score')->value;
    $adoption = (int) $healthEntity->get('adoption_score')->value;
    $satisfaction = (int) $healthEntity->get('satisfaction_score')->value;
    $support = (int) $healthEntity->get('support_score')->value;
    $growth = (int) $healthEntity->get('growth_score')->value;

    $score = (
      $engagement * ($weights['engagement'] ?? 30) +
      $adoption * ($weights['adoption'] ?? 25) +
      $satisfaction * ($weights['satisfaction'] ?? 20) +
      $support * ($weights['support'] ?? 15) +
      $growth * ($weights['growth'] ?? 10)
    ) / 100;

    return (int) round(max(0, min(100, $score)));
  }

  /**
   * Determines if inactivity is seasonal (expected) vs real churn.
   */
  protected function isSeasonalInactivity(string $tenantId, VerticalRetentionProfileInterface $profile, int $month): bool {
    $expectedPattern = $profile->getExpectedUsagePattern()[$month] ?? 'medium';

    // If we expect low usage this month, inactivity is likely seasonal.
    if ($expectedPattern === 'low') {
      $daysInactive = $this->getDaysInactive($tenantId);
      return $daysInactive <= $profile->getMaxInactivityDays();
    }

    return FALSE;
  }

  /**
   * Calculates composite risk score.
   */
  protected function calculateRiskScore(int $healthScore, float $signalWeight, float $seasonalAdj, bool $isSeasonal): float {
    // Base risk from health score (inverted: low health = high risk).
    $baseRisk = (100 - $healthScore) / 100;

    // Add signal weight contribution.
    $riskScore = $baseRisk * 0.5 + $signalWeight * 0.3 + max(0, $seasonalAdj) * 0.2;

    // Reduce risk if inactivity is seasonal.
    if ($isSeasonal) {
      $riskScore *= 0.6;
    }

    return max(0.0, min(1.0, $riskScore));
  }

  /**
   * Classifies risk level from score.
   */
  protected function classifyRisk(float $riskScore): string {
    if ($riskScore < 0.25) {
      return 'low';
    }
    if ($riskScore < 0.50) {
      return 'medium';
    }
    if ($riskScore < 0.75) {
      return 'high';
    }
    return 'critical';
  }

  /**
   * Determines recommended action based on risk and seasonality.
   */
  protected function determineAction(string $riskLevel, bool $isSeasonal): string {
    if ($isSeasonal) {
      return 'monitor';
    }
    return match ($riskLevel) {
      'critical' => 'immediate_intervention',
      'high' => 'reengagement',
      'medium' => 'proactive_outreach',
      'low' => 'monitor',
      default => 'monitor',
    };
  }

  /**
   * Selects appropriate playbook from vertical overrides.
   */
  protected function selectPlaybook(VerticalRetentionProfileInterface $profile, string $riskLevel): ?int {
    $overrides = $profile->getPlaybookOverrides();
    $triggerType = match ($riskLevel) {
      'critical', 'high' => 'churn_risk',
      'medium' => 'health_drop',
      default => NULL,
    };

    if ($triggerType && isset($overrides[$triggerType])) {
      return (int) $overrides[$triggerType];
    }
    return NULL;
  }

  /**
   * Resolves vertical ID from a tenant group entity.
   */
  protected function resolveVerticalId(string $tenantId): ?string {
    try {
      $group = $this->entityTypeManager->getStorage('group')->load($tenantId);
      if (!$group) {
        return NULL;
      }
      $groupType = $group->bundle();
      $verticalMap = [
        'tenant_agroconecta' => 'agroconecta',
        'tenant_comercioconecta' => 'comercioconecta',
        'tenant_serviciosconecta' => 'serviciosconecta',
        'tenant_empleabilidad' => 'empleabilidad',
        'tenant_emprendimiento' => 'emprendimiento',
        'tenant' => NULL,
      ];
      return $verticalMap[$groupType] ?? NULL;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to resolve vertical for tenant @id: @error', [
        '@id' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Gets a metric value for a tenant from usage logs.
   */
  protected function getMetricValue(string $tenantId, string $metric, int $lookbackDays): mixed {
    try {
      $connection = \Drupal::database();
      $since = strtotime("-{$lookbackDays} days");

      $count = $connection->select('finops_usage_log', 'f')
        ->condition('f.tenant_id', $tenantId)
        ->condition('f.metric_type', $metric)
        ->condition('f.created', $since, '>=')
        ->countQuery()
        ->execute()
        ->fetchField();

      return (int) $count;
    }
    catch (\Exception $e) {
      $this->logger->warning('Could not fetch metric @metric for tenant @tenant: @error', [
        '@metric' => $metric,
        '@tenant' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
      return 0;
    }
  }

  /**
   * Evaluates a condition: value OPERATOR threshold.
   */
  protected function evaluateCondition(mixed $value, string $operator, mixed $threshold): bool {
    return match ($operator) {
      '==' => $value == $threshold,
      '!=' => $value != $threshold,
      '>' => $value > $threshold,
      '>=' => $value >= $threshold,
      '<' => $value < $threshold,
      '<=' => $value <= $threshold,
      default => FALSE,
    };
  }

  /**
   * Gets number of days since last activity for a tenant.
   */
  protected function getDaysInactive(string $tenantId): int {
    try {
      $connection = \Drupal::database();
      $lastActivity = $connection->select('finops_usage_log', 'f')
        ->fields('f', ['created'])
        ->condition('f.tenant_id', $tenantId)
        ->orderBy('f.created', 'DESC')
        ->range(0, 1)
        ->execute()
        ->fetchField();

      if ($lastActivity) {
        return (int) ((time() - (int) $lastActivity) / 86400);
      }
    }
    catch (\Exception $e) {
      // Ignore — return max inactivity.
    }
    return 999;
  }

  /**
   * Builds a generic evaluation for tenants without vertical profile.
   */
  protected function buildGenericEvaluation(string $tenantId): array {
    $healthEntity = $this->healthCalculator->calculate($tenantId);
    $baseScore = $healthEntity ? (int) $healthEntity->get('overall_score')->value : 50;

    return [
      'tenant_id' => $tenantId,
      'vertical_id' => 'generic',
      'health_score' => $baseScore,
      'adjusted_health_score' => $baseScore,
      'risk_level' => $this->classifyRisk((100 - $baseScore) / 100),
      'is_seasonal_inactivity' => FALSE,
      'signals_triggered' => [],
      'recommended_action' => 'monitor',
      'recommended_playbook_id' => NULL,
      'seasonal_context' => [],
    ];
  }

}
