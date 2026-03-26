<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Bridges jaraba_predictive services to consuming modules (CRM, Billing, Support).
 *
 * Resolves the architectural gap where predictive ML services exist but are
 * isolated from the modules that need their intelligence.
 *
 * @see AI-COVERAGE-001
 */
class PredictiveIntegrationService {

  /**
   * Churn risk thresholds.
   */
  protected const CHURN_HIGH_THRESHOLD = 60;
  protected const CHURN_CRITICAL_THRESHOLD = 85;

  /**
   * Lead qualification thresholds.
   */
  protected const LEAD_HOT_THRESHOLD = 70;
  protected const LEAD_SALES_READY_THRESHOLD = 85;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected ?object $leadScorer = NULL,
    protected ?object $churnPredictor = NULL,
    protected ?object $forecastEngine = NULL,
    protected ?object $anomalyDetector = NULL,
    protected ?object $retentionWorkflow = NULL,
  ) {}

  /**
   * Gets lead score enrichment for a CRM contact.
   *
   * @param int $userId
   *   The user ID associated with the contact.
   *
   * @return array<string, mixed>
   *   Lead scoring data: score, qualification, breakdown, recommended_actions.
   */
  public function getLeadEnrichment(int $userId): array {
    if ($this->leadScorer === NULL) {
      return $this->emptyLeadResult();
    }

    try {
      $result = $this->leadScorer->scoreUser($userId);
      if (!isset($result['lead_score'])) {
        return $this->emptyLeadResult();
      }

      $leadScore = $result['lead_score'];
      $score = (int) ($leadScore->get('total_score')->value ?? 0);
      $qualification = (string) ($leadScore->get('qualification')->value ?? 'cold');

      return [
        'score' => $score,
        'qualification' => $qualification,
        'breakdown' => json_decode((string) ($leadScore->get('score_breakdown')->value ?? '{}'), TRUE) ?? [],
        'is_hot' => $score >= self::LEAD_HOT_THRESHOLD,
        'is_sales_ready' => $score >= self::LEAD_SALES_READY_THRESHOLD,
        'recommended_priority' => $this->mapLeadToPriority($qualification),
        'calculated_at' => $leadScore->get('calculated_at')->value ?? '',
      ];
    }
    catch (\Throwable $e) {
      $this->logger->warning('Lead enrichment error for user @uid: @error', [
        '@uid' => $userId,
        '@error' => $e->getMessage(),
      ]);
      return $this->emptyLeadResult();
    }
  }

  /**
   * Gets churn prediction for a tenant.
   *
   * @param int $tenantId
   *   The tenant (group) ID.
   *
   * @return array<string, mixed>
   *   Churn data: risk_score, risk_level, contributing_factors, recommended_actions.
   */
  public function getChurnRisk(int $tenantId): array {
    if ($this->churnPredictor === NULL) {
      return $this->emptyChurnResult();
    }

    try {
      $result = $this->churnPredictor->calculateChurnRisk($tenantId);
      $riskScore = (int) ($result['risk_score'] ?? 0);

      $prediction = $result['prediction'] ?? NULL;
      $factors = [];
      $actions = [];

      if ($prediction !== NULL) {
        $factorsJson = $prediction->get('contributing_factors')->value ?? '[]';
        $factors = json_decode((string) $factorsJson, TRUE) ?? [];
        $actionsJson = $prediction->get('recommended_actions')->value ?? '[]';
        $actions = json_decode((string) $actionsJson, TRUE) ?? [];
      }

      return [
        'risk_score' => $riskScore,
        'risk_level' => $this->mapChurnLevel($riskScore),
        'is_high_risk' => $riskScore >= self::CHURN_HIGH_THRESHOLD,
        'is_critical' => $riskScore >= self::CHURN_CRITICAL_THRESHOLD,
        'contributing_factors' => $factors,
        'recommended_actions' => $actions,
        'needs_retention' => $riskScore >= self::CHURN_HIGH_THRESHOLD,
      ];
    }
    catch (\Throwable $e) {
      $this->logger->warning('Churn prediction error for tenant @tid: @error', [
        '@tid' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
      return $this->emptyChurnResult();
    }
  }

  /**
   * Gets revenue forecast.
   *
   * @param string $metric
   *   The metric to forecast: mrr, arr, revenue, users.
   * @param string $period
   *   The period: monthly, quarterly, yearly.
   *
   * @return array<string, mixed>
   *   Forecast data with predicted_value, confidence interval, and trend.
   */
  public function getRevenueForecast(string $metric = 'mrr', string $period = 'monthly'): array {
    if ($this->forecastEngine === NULL) {
      return $this->emptyForecastResult();
    }

    try {
      $result = $this->forecastEngine->generateForecast($metric, $period);
      $forecast = $result['forecast'] ?? NULL;

      if ($forecast === NULL) {
        return $this->emptyForecastResult();
      }

      return [
        'predicted_value' => (float) ($forecast->get('predicted_value')->value ?? 0),
        'confidence_low' => (float) ($forecast->get('confidence_low')->value ?? 0),
        'confidence_high' => (float) ($forecast->get('confidence_high')->value ?? 0),
        'forecast_date' => $forecast->get('forecast_date')->value ?? '',
        'model_version' => $forecast->get('model_version')->value ?? 'heuristic_v1',
      ];
    }
    catch (\Throwable $e) {
      $this->logger->warning('Forecast error for @metric/@period: @error', [
        '@metric' => $metric,
        '@period' => $period,
        '@error' => $e->getMessage(),
      ]);
      return $this->emptyForecastResult();
    }
  }

  /**
   * Detects anomalies in a metric.
   *
   * @param string $metric
   *   The metric to check.
   *
   * @return array<string, mixed>
   *   Anomaly detection results.
   */
  public function detectAnomalies(string $metric): array {
    if ($this->anomalyDetector === NULL) {
      return ['anomalies' => [], 'data_points_analyzed' => 0];
    }

    try {
      return $this->anomalyDetector->detectAnomalies($metric);
    }
    catch (\Throwable $e) {
      $this->logger->warning('Anomaly detection error for @metric: @error', [
        '@metric' => $metric,
        '@error' => $e->getMessage(),
      ]);
      return ['anomalies' => [], 'data_points_analyzed' => 0];
    }
  }

  /**
   * Triggers retention workflow for high-risk tenant.
   *
   * @param int $tenantId
   *   The tenant ID.
   * @param int $riskScore
   *   The churn risk score (0-100).
   * @param string $riskLevel
   *   The risk level: low, medium, high, critical.
   */
  public function triggerRetention(int $tenantId, int $riskScore, string $riskLevel): void {
    if ($this->retentionWorkflow === NULL) {
      $this->logger->info('Retention workflow not available for tenant @tid (score: @score)', [
        '@tid' => $tenantId,
        '@score' => $riskScore,
      ]);
      return;
    }

    try {
      $this->retentionWorkflow->triggerResponse($tenantId, $riskScore, $riskLevel);
      $this->logger->info('Retention triggered for tenant @tid: score=@score, level=@level', [
        '@tid' => $tenantId,
        '@score' => $riskScore,
        '@level' => $riskLevel,
      ]);
    }
    catch (\Throwable $e) {
      $this->logger->error('Retention trigger failed for tenant @tid: @error', [
        '@tid' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Gets top leads across all tenants.
   *
   * @param int $limit
   *   Maximum results.
   *
   * @return array<int, mixed>
   *   Top leads sorted by score.
   */
  public function getTopLeads(int $limit = 20): array {
    if ($this->leadScorer === NULL) {
      return [];
    }

    try {
      return $this->leadScorer->getTopLeads($limit);
    }
    catch (\Throwable $e) {
      $this->logger->warning('Top leads error: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Gets high-risk tenants.
   *
   * @param int $limit
   *   Maximum results.
   *
   * @return array<int, mixed>
   *   High-risk tenants.
   */
  public function getHighRiskTenants(int $limit = 20): array {
    if ($this->churnPredictor === NULL) {
      return [];
    }

    try {
      return $this->churnPredictor->getHighRiskTenants($limit);
    }
    catch (\Throwable $e) {
      $this->logger->warning('High risk tenants error: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Maps lead qualification to action priority.
   */
  protected function mapLeadToPriority(string $qualification): string {
    return match ($qualification) {
      'sales_ready' => 'critical',
      'hot' => 'high',
      'warm' => 'medium',
      default => 'low',
    };
  }

  /**
   * Maps churn score to risk level.
   */
  protected function mapChurnLevel(int $score): string {
    if ($score >= self::CHURN_CRITICAL_THRESHOLD) {
      return 'critical';
    }
    if ($score >= self::CHURN_HIGH_THRESHOLD) {
      return 'high';
    }
    if ($score >= 30) {
      return 'medium';
    }
    return 'low';
  }

  /**
   * Empty lead result structure.
   *
   * @return array<string, mixed>
   */
  protected function emptyLeadResult(): array {
    return [
      'score' => 0,
      'qualification' => 'cold',
      'breakdown' => [],
      'is_hot' => FALSE,
      'is_sales_ready' => FALSE,
      'recommended_priority' => 'low',
      'calculated_at' => '',
    ];
  }

  /**
   * Empty churn result structure.
   *
   * @return array<string, mixed>
   */
  protected function emptyChurnResult(): array {
    return [
      'risk_score' => 0,
      'risk_level' => 'low',
      'is_high_risk' => FALSE,
      'is_critical' => FALSE,
      'contributing_factors' => [],
      'recommended_actions' => [],
      'needs_retention' => FALSE,
    ];
  }

  /**
   * Empty forecast result structure.
   *
   * @return array<string, mixed>
   */
  protected function emptyForecastResult(): array {
    return [
      'predicted_value' => 0.0,
      'confidence_low' => 0.0,
      'confidence_high' => 0.0,
      'forecast_date' => '',
      'model_version' => '',
    ];
  }

}
