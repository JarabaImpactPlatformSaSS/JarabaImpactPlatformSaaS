<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\jaraba_customer_success\Entity\SeasonalChurnPrediction;
use Drupal\jaraba_customer_success\Entity\SeasonalChurnPredictionInterface;
use Drupal\jaraba_customer_success\Entity\VerticalRetentionProfileInterface;
use Psr\Log\LoggerInterface;

/**
 * Seasonal churn prediction service.
 *
 * Generates monthly churn predictions adjusted for seasonal patterns
 * specific to each vertical. All predictions are append-only.
 */
class SeasonalChurnService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ChurnPredictionService $churnPrediction,
    protected StateInterface $state,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Generates a seasonal churn prediction for a tenant.
   */
  public function predict(string $tenantId, VerticalRetentionProfileInterface $profile): SeasonalChurnPredictionInterface {
    // 1. Get base churn probability from generic predictor.
    $baseProbability = $this->getBaseProbability($tenantId);

    // 2. Get seasonal adjustment.
    $currentMonth = (int) date('n');
    $seasonalAdjustment = $profile->getSeasonalAdjustment($currentMonth);

    // 3. Calculate adjusted probability (clamped to 0-1).
    $adjustedProbability = max(0.0, min(1.0, $baseProbability + $seasonalAdjustment));

    // 4. Build seasonal context.
    $calendar = $profile->getSeasonalityCalendar();
    $monthEntry = $calendar[$currentMonth - 1] ?? [];
    $expectedPattern = $profile->getExpectedUsagePattern()[$currentMonth] ?? 'medium';

    $seasonalContext = [
      'month_label' => $monthEntry['label'] ?? '',
      'month_risk_level' => $monthEntry['risk_level'] ?? 'medium',
      'expected_pattern' => $expectedPattern,
      'base_probability' => $baseProbability,
      'adjustment_applied' => $seasonalAdjustment,
    ];

    // 5. Determine urgency.
    $urgency = $this->classifyUrgency($adjustedProbability);

    // 6. Select recommended playbook.
    $playbookId = $this->selectPlaybook($profile, $urgency);

    // 7. Create prediction entity (append-only).
    $predictionMonth = date('Y-m');
    $storage = $this->entityTypeManager->getStorage('seasonal_churn_prediction');

    /** @var \Drupal\jaraba_customer_success\Entity\SeasonalChurnPredictionInterface $prediction */
    $prediction = $storage->create([
      'tenant_id' => $tenantId,
      'vertical_id' => $profile->getVerticalId(),
      'prediction_month' => $predictionMonth,
      'base_churn_probability' => $baseProbability,
      'seasonal_adjustment' => $seasonalAdjustment,
      'adjusted_probability' => $adjustedProbability,
      'seasonal_context' => json_encode($seasonalContext, JSON_THROW_ON_ERROR),
      'recommended_playbook' => $playbookId,
      'intervention_urgency' => $urgency,
    ]);
    $prediction->save();

    $this->logger->info('Seasonal prediction for tenant @tenant (@vertical): @prob% (base @base% + adj @adj%).', [
      '@tenant' => $tenantId,
      '@vertical' => $profile->getVerticalId(),
      '@prob' => round($adjustedProbability * 100),
      '@base' => round($baseProbability * 100),
      '@adj' => round($seasonalAdjustment * 100),
    ]);

    return $prediction;
  }

  /**
   * Gets the latest prediction for a tenant.
   */
  public function getLatestPrediction(string $tenantId): ?SeasonalChurnPredictionInterface {
    $ids = $this->entityTypeManager
      ->getStorage('seasonal_churn_prediction')
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('tenant_id', $tenantId)
      ->sort('created', 'DESC')
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return NULL;
    }

    return $this->entityTypeManager
      ->getStorage('seasonal_churn_prediction')
      ->load(reset($ids));
  }

  /**
   * Gets prediction history for a tenant.
   */
  public function getPredictionHistory(string $tenantId, int $months = 6): array {
    $ids = $this->entityTypeManager
      ->getStorage('seasonal_churn_prediction')
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('tenant_id', $tenantId)
      ->sort('created', 'DESC')
      ->range(0, $months)
      ->execute();

    if (empty($ids)) {
      return [];
    }

    return $this->entityTypeManager
      ->getStorage('seasonal_churn_prediction')
      ->loadMultiple($ids);
  }

  /**
   * Gets all predictions for a given month.
   */
  public function getMonthlyPredictions(string $month): array {
    $ids = $this->entityTypeManager
      ->getStorage('seasonal_churn_prediction')
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('prediction_month', $month)
      ->sort('adjusted_probability', 'DESC')
      ->execute();

    if (empty($ids)) {
      return [];
    }

    return $this->entityTypeManager
      ->getStorage('seasonal_churn_prediction')
      ->loadMultiple($ids);
  }

  /**
   * Runs monthly predictions for all active tenants.
   */
  public function runMonthlyPredictions(): int {
    $count = 0;

    try {
      $profiles = $this->entityTypeManager
        ->getStorage('vertical_retention_profile')
        ->loadByProperties(['status' => VerticalRetentionProfileInterface::STATUS_ACTIVE]);

      foreach ($profiles as $profile) {
        $tenants = $this->entityTypeManager
          ->getStorage('group')
          ->getQuery()
          ->accessCheck(TRUE)
          ->condition('type', 'tenant')
          ->execute();

        foreach ($tenants as $tenantId) {
          try {
            $this->predict((string) $tenantId, $profile);
            $count++;
          }
          catch (\Exception $e) {
            $this->logger->error('Seasonal prediction failed for tenant @id: @error', [
              '@id' => $tenantId,
              '@error' => $e->getMessage(),
            ]);
          }
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Monthly prediction batch failed: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    $this->state->set('jaraba_cs.seasonal_last_predictions', time());
    $this->logger->info('Seasonal predictions batch completed: @count predictions generated.', [
      '@count' => $count,
    ]);

    return $count;
  }

  /**
   * Gets base churn probability from the generic predictor.
   */
  protected function getBaseProbability(string $tenantId): float {
    try {
      $predictions = $this->entityTypeManager
        ->getStorage('churn_prediction')
        ->getQuery()
        ->accessCheck(TRUE)
        ->condition('tenant_id', $tenantId)
        ->sort('created', 'DESC')
        ->range(0, 1)
        ->execute();

      if (!empty($predictions)) {
        $prediction = $this->entityTypeManager
          ->getStorage('churn_prediction')
          ->load(reset($predictions));
        if ($prediction) {
          return (float) $prediction->get('probability')->value;
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('Could not fetch base probability for tenant @id: @error', [
        '@id' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
    }

    return 0.30;
  }

  /**
   * Classifies urgency from probability.
   */
  protected function classifyUrgency(float $probability): string {
    if ($probability < 0.15) {
      return SeasonalChurnPrediction::URGENCY_NONE;
    }
    if ($probability < 0.30) {
      return SeasonalChurnPrediction::URGENCY_LOW;
    }
    if ($probability < 0.50) {
      return SeasonalChurnPrediction::URGENCY_MEDIUM;
    }
    if ($probability < 0.75) {
      return SeasonalChurnPrediction::URGENCY_HIGH;
    }
    return SeasonalChurnPrediction::URGENCY_CRITICAL;
  }

  /**
   * Selects a playbook for the given urgency and vertical.
   */
  protected function selectPlaybook(VerticalRetentionProfileInterface $profile, string $urgency): ?int {
    if ($urgency === SeasonalChurnPrediction::URGENCY_NONE) {
      return NULL;
    }

    $overrides = $profile->getPlaybookOverrides();
    $triggerType = match ($urgency) {
      SeasonalChurnPrediction::URGENCY_CRITICAL,
      SeasonalChurnPrediction::URGENCY_HIGH => 'churn_risk',
      SeasonalChurnPrediction::URGENCY_MEDIUM => 'health_drop',
      default => NULL,
    };

    if ($triggerType && isset($overrides[$triggerType])) {
      return (int) $overrides[$triggerType];
    }
    return NULL;
  }

}
