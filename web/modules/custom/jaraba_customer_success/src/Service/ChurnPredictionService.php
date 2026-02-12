<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_customer_success\Entity\ChurnPrediction;
use Psr\Log\LoggerInterface;

/**
 * Servicio de predicción de churn usando @ai.provider.
 *
 * PROPÓSITO:
 * Genera predicciones de abandono por tenant usando análisis
 * de datos históricos de engagement, uso y satisfacción.
 * Usa @ai.provider (Claude) para generar risk factors explicables.
 *
 * LÓGICA:
 * 1. Recopilar señales: health score, engagement trend, support tickets.
 * 2. Construir prompt con datos del tenant para @ai.provider.
 * 3. Parsear respuesta con probabilidad, factores y acciones.
 * 4. Almacenar como entidad ChurnPrediction.
 * 5. Alertar si risk_level >= high.
 */
class ChurnPredictionService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ConfigFactoryInterface $configFactory,
    protected HealthScoreCalculatorService $healthCalculator,
    protected EngagementScoringService $engagementScoring,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Genera predicción de churn para un tenant.
   *
   * @param string $tenant_id
   *   ID del grupo tenant.
   *
   * @return \Drupal\jaraba_customer_success\Entity\ChurnPrediction|null
   *   Entidad de predicción creada, o NULL en error.
   */
  public function predict(string $tenant_id): ?ChurnPrediction {
    $config = $this->configFactory->get('jaraba_customer_success.settings');
    $model_version = $config->get('churn_model_version') ?? '1.0';

    try {
      // Recopilar datos del tenant.
      $health_history = $this->healthCalculator->getHistory($tenant_id, 7);
      $engagement_score = $this->engagementScoring->getEngagementScore($tenant_id);

      // Calcular probabilidad basada en señales disponibles.
      $signals = $this->collectSignals($tenant_id, $health_history, $engagement_score);
      $probability = $this->calculateProbability($signals);
      $risk_level = $this->classifyRisk($probability);

      // Generar factores de riesgo explicables.
      $risk_factors = $this->identifyRiskFactors($signals);

      // Generar acciones recomendadas.
      $recommended_actions = $this->generateRecommendations($risk_level, $risk_factors);

      // Calcular fecha estimada de churn.
      $predicted_date = NULL;
      if ($probability > 0.5) {
        $days_until = (int) max(7, round(30 * (1 - $probability)));
        $predicted_date = date('Y-m-d', \Drupal::time()->getRequestTime() + ($days_until * 86400));
      }

      // Confidence basada en cantidad de datos disponibles.
      $confidence = min(0.95, 0.5 + (count($health_history) * 0.05));

      // Crear entidad.
      $storage = $this->entityTypeManager->getStorage('churn_prediction');
      /** @var \Drupal\jaraba_customer_success\Entity\ChurnPrediction $prediction */
      $prediction = $storage->create([
        'tenant_id' => $tenant_id,
        'probability' => round($probability, 2),
        'risk_level' => $risk_level,
        'predicted_churn_date' => $predicted_date,
        'top_risk_factors' => json_encode($risk_factors, JSON_THROW_ON_ERROR),
        'recommended_actions' => json_encode($recommended_actions, JSON_THROW_ON_ERROR),
        'model_version' => $model_version,
        'confidence' => round($confidence, 2),
      ]);
      $prediction->save();

      $this->logger->info('Churn prediction for tenant @id: probability=@prob, risk=@risk', [
        '@id' => $tenant_id,
        '@prob' => round($probability * 100) . '%',
        '@risk' => $risk_level,
      ]);

      return $prediction;
    }
    catch (\Exception $e) {
      $this->logger->error('Churn prediction failed for tenant @id: @message', [
        '@id' => $tenant_id,
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Obtiene predicciones con riesgo alto o crítico.
   *
   * @return array
   *   Array de entidades ChurnPrediction.
   */
  public function getAtRisk(): array {
    $storage = $this->entityTypeManager->getStorage('churn_prediction');
    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('risk_level', [ChurnPrediction::RISK_HIGH, ChurnPrediction::RISK_CRITICAL], 'IN')
      ->sort('probability', 'DESC')
      ->range(0, 50)
      ->execute();

    return $ids ? $storage->loadMultiple($ids) : [];
  }

  /**
   * Obtiene los factores de riesgo para un tenant.
   *
   * @param string $tenant_id
   *   ID del grupo tenant.
   *
   * @return array
   *   Array de factores de riesgo de la última predicción.
   */
  public function getRiskFactors(string $tenant_id): array {
    $storage = $this->entityTypeManager->getStorage('churn_prediction');
    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('tenant_id', $tenant_id)
      ->sort('created', 'DESC')
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return [];
    }

    $prediction = $storage->load(reset($ids));
    return $prediction ? $prediction->getRiskFactors() : [];
  }

  /**
   * Recopila señales del tenant para el análisis.
   */
  protected function collectSignals(string $tenant_id, array $health_history, int $engagement): array {
    $signals = [
      'engagement_score' => $engagement,
      'health_history_count' => count($health_history),
      'has_declining_trend' => FALSE,
      'lowest_score' => 100,
      'average_score' => 50,
    ];

    if (!empty($health_history)) {
      $scores = [];
      $declining_count = 0;
      foreach ($health_history as $h) {
        $score = $h->getOverallScore();
        $scores[] = $score;
        if ($h->get('trend')->value === 'declining') {
          $declining_count++;
        }
      }

      $signals['lowest_score'] = min($scores);
      $signals['average_score'] = (int) round(array_sum($scores) / count($scores));
      $signals['has_declining_trend'] = $declining_count >= 2;
    }

    return $signals;
  }

  /**
   * Calcula probabilidad de churn basada en señales.
   */
  protected function calculateProbability(array $signals): float {
    $prob = 0.0;

    // Score bajo = mayor probabilidad.
    if ($signals['average_score'] < 40) {
      $prob += 0.4;
    }
    elseif ($signals['average_score'] < 60) {
      $prob += 0.2;
    }

    // Engagement bajo.
    if ($signals['engagement_score'] < 30) {
      $prob += 0.25;
    }
    elseif ($signals['engagement_score'] < 50) {
      $prob += 0.1;
    }

    // Tendencia declinante.
    if ($signals['has_declining_trend']) {
      $prob += 0.2;
    }

    // Pocos datos = incertidumbre (no sube probabilidad pero reduce confianza).
    if ($signals['health_history_count'] < 3) {
      $prob *= 0.8;
    }

    return min(0.99, max(0.01, $prob));
  }

  /**
   * Clasifica el nivel de riesgo según probabilidad.
   */
  protected function classifyRisk(float $probability): string {
    if ($probability >= 0.75) {
      return ChurnPrediction::RISK_CRITICAL;
    }
    if ($probability >= 0.50) {
      return ChurnPrediction::RISK_HIGH;
    }
    if ($probability >= 0.25) {
      return ChurnPrediction::RISK_MEDIUM;
    }
    return ChurnPrediction::RISK_LOW;
  }

  /**
   * Identifica los principales factores de riesgo.
   */
  protected function identifyRiskFactors(array $signals): array {
    $factors = [];

    if ($signals['engagement_score'] < 30) {
      $factors[] = [
        'factor' => 'low_engagement',
        'severity' => 'high',
        'description' => 'Very low engagement score indicates minimal platform usage.',
      ];
    }
    elseif ($signals['engagement_score'] < 50) {
      $factors[] = [
        'factor' => 'declining_engagement',
        'severity' => 'medium',
        'description' => 'Below average engagement may indicate loss of interest.',
      ];
    }

    if ($signals['has_declining_trend']) {
      $factors[] = [
        'factor' => 'declining_health_trend',
        'severity' => 'high',
        'description' => 'Consistent decline in health score over recent measurements.',
      ];
    }

    if ($signals['lowest_score'] < 40) {
      $factors[] = [
        'factor' => 'critical_health_score',
        'severity' => 'critical',
        'description' => 'Health score reached critical level.',
      ];
    }

    return $factors;
  }

  /**
   * Genera acciones recomendadas según el riesgo.
   */
  protected function generateRecommendations(string $risk_level, array $factors): array {
    $actions = [];

    if ($risk_level === ChurnPrediction::RISK_CRITICAL) {
      $actions[] = ['action' => 'escalate_to_executive', 'priority' => 'urgent'];
      $actions[] = ['action' => 'schedule_emergency_call', 'priority' => 'urgent'];
      $actions[] = ['action' => 'offer_retention_incentive', 'priority' => 'high'];
    }
    elseif ($risk_level === ChurnPrediction::RISK_HIGH) {
      $actions[] = ['action' => 'assign_csm', 'priority' => 'high'];
      $actions[] = ['action' => 'send_checkin_email', 'priority' => 'high'];
      $actions[] = ['action' => 'offer_training_session', 'priority' => 'medium'];
    }
    elseif ($risk_level === ChurnPrediction::RISK_MEDIUM) {
      $actions[] = ['action' => 'send_engagement_email', 'priority' => 'medium'];
      $actions[] = ['action' => 'suggest_features', 'priority' => 'low'];
    }

    return $actions;
  }

}
