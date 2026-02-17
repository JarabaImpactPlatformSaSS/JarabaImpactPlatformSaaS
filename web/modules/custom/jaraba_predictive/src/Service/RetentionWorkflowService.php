<?php

declare(strict_types=1);

namespace Drupal\jaraba_predictive\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de flujos de trabajo de retencion automatizados.
 *
 * ESTRUCTURA:
 *   Orquestador que conecta las predicciones de churn con acciones
 *   de retencion concretas. Cuando un tenant entra en riesgo alto
 *   o critico, dispara un workflow de retencion con acciones
 *   automatizadas y/o manuales.
 *
 * LOGICA:
 *   1. Ejecuta prediccion de churn via ChurnPredictorService.
 *   2. Si risk_level es 'high' o 'critical', crea acciones de retencion.
 *   3. Cada accion tiene un estado (pending, in_progress, completed, skipped).
 *   4. Registra historial de intervenciones para analisis posterior.
 *   5. Calcula metricas de retencion: interventions, saves, risk_reduction.
 *
 * RELACIONES:
 *   - Consume: jaraba_predictive.churn_predictor (predicciones de churn).
 *   - Consume: entity_type.manager (churn_prediction entities).
 *   - Produce: Arrays de acciones de retencion (flujo de trabajo).
 */
class RetentionWorkflowService {

  /**
   * Niveles de riesgo que activan el workflow de retencion.
   */
  protected const TRIGGER_RISK_LEVELS = ['high', 'critical'];

  /**
   * Construye el servicio de flujos de retencion.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad para acceso a almacenamiento.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del canal jaraba_predictive.
   * @param \Drupal\jaraba_predictive\Service\ChurnPredictorService $churnPredictor
   *   Servicio de prediccion de churn.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
    protected readonly ChurnPredictorService $churnPredictor,
  ) {}

  /**
   * Dispara un flujo de trabajo de retencion para un tenant.
   *
   * ESTRUCTURA:
   *   Metodo principal que ejecuta prediccion de churn y, si el
   *   riesgo es alto, genera acciones de retencion.
   *
   * LOGICA:
   *   1. Ejecuta calculateChurnRisk() para obtener prediccion actual.
   *   2. Si risk_level esta en TRIGGER_RISK_LEVELS:
   *      a. Genera acciones de retencion basadas en los factores.
   *      b. Registra el workflow en log.
   *   3. Si risk_level es bajo/medio:
   *      a. No genera acciones, solo registra monitoreo.
   *
   * RELACIONES:
   *   - Usa: ChurnPredictorService::calculateChurnRisk().
   *   - Lee: churn_prediction entities (acciones recomendadas).
   *
   * @param int $tenantId
   *   ID del grupo/organizacion.
   *
   * @return array
   *   Array con claves:
   *   - 'prediction': ChurnPrediction entity.
   *   - 'actions_triggered': array de acciones disparadas.
   *   - 'workflow_status': string estado del workflow.
   */
  public function triggerRetentionWorkflow(int $tenantId): array {
    $churnResult = $this->churnPredictor->calculateChurnRisk($tenantId);
    $prediction = $churnResult['prediction'];
    $riskLevel = $prediction->get('risk_level')->value ?? 'low';
    $riskScore = (int) ($prediction->get('risk_score')->value ?? 0);

    $actionsList = [];
    $workflowStatus = 'monitoring';

    if (in_array($riskLevel, self::TRIGGER_RISK_LEVELS, TRUE)) {
      $recommendedActions = json_decode(
        $prediction->get('recommended_actions')->value ?? '[]',
        TRUE,
      );

      foreach ($recommendedActions as $action) {
        $actionsList[] = [
          'action' => $action['action'] ?? 'unknown',
          'priority' => $action['priority'] ?? 'medium',
          'description' => $action['description'] ?? '',
          'status' => 'pending',
          'triggered_at' => date('Y-m-d\TH:i:s'),
          'tenant_id' => $tenantId,
          'risk_score' => $riskScore,
          'risk_level' => $riskLevel,
        ];
      }

      // Agregar accion automatica de notificacion.
      $actionsList[] = [
        'action' => 'internal_alert',
        'priority' => $riskLevel === 'critical' ? 'urgent' : 'high',
        'description' => "Alerta interna: tenant {$tenantId} en riesgo {$riskLevel} (score: {$riskScore}).",
        'status' => 'completed',
        'triggered_at' => date('Y-m-d\TH:i:s'),
        'tenant_id' => $tenantId,
        'risk_score' => $riskScore,
        'risk_level' => $riskLevel,
      ];

      $workflowStatus = 'actions_triggered';

      $this->logger->warning('Retention workflow triggered for tenant @id: @count actions, risk=@level (@score).', [
        '@id' => $tenantId,
        '@count' => count($actionsList),
        '@level' => $riskLevel,
        '@score' => $riskScore,
      ]);
    }
    else {
      $this->logger->info('Retention workflow for tenant @id: monitoring only (risk=@level, score=@score).', [
        '@id' => $tenantId,
        '@level' => $riskLevel,
        '@score' => $riskScore,
      ]);
    }

    return [
      'prediction' => $prediction,
      'actions_triggered' => $actionsList,
      'workflow_status' => $workflowStatus,
    ];
  }

  /**
   * Obtiene el historial de flujos de retencion para un tenant.
   *
   * ESTRUCTURA:
   *   Metodo de consulta del historial de predicciones asociadas
   *   a workflows de retencion para un tenant.
   *
   * LOGICA:
   *   Carga predicciones de churn con risk_level 'high' o 'critical'
   *   del tenant, que representan momentos donde se disparo o deberia
   *   haberse disparado un workflow de retencion.
   *
   * RELACIONES:
   *   - Lee: churn_prediction entities.
   *
   * @param int $tenantId
   *   ID del grupo/organizacion.
   *
   * @return array
   *   Array de arrays con datos de cada intervencion de retencion.
   */
  public function getRetentionHistory(int $tenantId): array {
    $storage = $this->entityTypeManager->getStorage('churn_prediction');

    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('tenant_id', $tenantId)
      ->condition('risk_level', self::TRIGGER_RISK_LEVELS, 'IN')
      ->sort('created', 'DESC')
      ->execute();

    if (empty($ids)) {
      return [];
    }

    $predictions = $storage->loadMultiple($ids);
    $history = [];

    foreach ($predictions as $prediction) {
      $history[] = [
        'prediction_id' => (int) $prediction->id(),
        'tenant_id' => $prediction->get('tenant_id')->target_id ? (int) $prediction->get('tenant_id')->target_id : NULL,
        'risk_score' => (int) ($prediction->get('risk_score')->value ?? 0),
        'risk_level' => $prediction->get('risk_level')->value ?? 'low',
        'recommended_actions' => json_decode($prediction->get('recommended_actions')->value ?? '[]', TRUE),
        'model_version' => $prediction->get('model_version')->value ?? '',
        'calculated_at' => $prediction->get('calculated_at')->value ?? NULL,
        'created' => $prediction->get('created')->value ?? NULL,
      ];
    }

    return $history;
  }

  /**
   * Calcula metricas agregadas de retencion.
   *
   * ESTRUCTURA:
   *   Metodo de metricas globales del sistema de retencion.
   *
   * LOGICA:
   *   - total_interventions: predicciones con risk_level high/critical.
   *   - successful_saves: tenants que bajaron de risk despues de intervencion.
   *   - average_risk_reduction: reduccion media de risk_score entre
   *     la primera prediccion high/critical y la mas reciente.
   *
   * RELACIONES:
   *   - Lee: churn_prediction entities.
   *
   * @return array
   *   Array con 'total_interventions', 'successful_saves',
   *   'average_risk_reduction', 'save_rate'.
   */
  public function getRetentionMetrics(): array {
    $storage = $this->entityTypeManager->getStorage('churn_prediction');

    // Total de intervenciones (predicciones high/critical).
    $totalInterventions = (int) $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('risk_level', self::TRIGGER_RISK_LEVELS, 'IN')
      ->count()
      ->execute();

    // Intentar calcular saves: tenants que tuvieron high/critical y luego low/medium.
    $highRiskIds = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('risk_level', self::TRIGGER_RISK_LEVELS, 'IN')
      ->execute();

    $successfulSaves = 0;
    $totalRiskReduction = 0.0;
    $reductionCount = 0;

    if (!empty($highRiskIds)) {
      $highRiskPredictions = $storage->loadMultiple($highRiskIds);

      // Agrupar por tenant.
      $tenantPredictions = [];
      foreach ($highRiskPredictions as $prediction) {
        $tid = $prediction->get('tenant_id')->target_id;
        if ($tid) {
          $tenantPredictions[(int) $tid][] = $prediction;
        }
      }

      foreach ($tenantPredictions as $tid => $predictions) {
        // Obtener la prediccion mas reciente para este tenant.
        $latestIds = $storage->getQuery()
          ->accessCheck(TRUE)
          ->condition('tenant_id', $tid)
          ->sort('created', 'DESC')
          ->range(0, 1)
          ->execute();

        if (!empty($latestIds)) {
          $latest = $storage->load(reset($latestIds));
          $latestRiskLevel = $latest->get('risk_level')->value ?? 'low';
          $latestScore = (int) ($latest->get('risk_score')->value ?? 0);

          // Si el ultimo registro ya no es high/critical, es un save.
          if (!in_array($latestRiskLevel, self::TRIGGER_RISK_LEVELS, TRUE)) {
            $successfulSaves++;
          }

          // Calcular reduccion de riesgo.
          // Encontrar el score mas alto historico.
          $maxScore = 0;
          foreach ($predictions as $pred) {
            $score = (int) ($pred->get('risk_score')->value ?? 0);
            if ($score > $maxScore) {
              $maxScore = $score;
            }
          }

          if ($maxScore > 0) {
            $reduction = $maxScore - $latestScore;
            $totalRiskReduction += $reduction;
            $reductionCount++;
          }
        }
      }
    }

    $averageRiskReduction = $reductionCount > 0
      ? round($totalRiskReduction / $reductionCount, 1)
      : 0.0;

    $saveRate = $totalInterventions > 0
      ? round(($successfulSaves / count($tenantPredictions ?? [])) * 100, 1)
      : 0.0;

    return [
      'total_interventions' => $totalInterventions,
      'successful_saves' => $successfulSaves,
      'average_risk_reduction' => $averageRiskReduction,
      'save_rate' => $saveRate,
    ];
  }

}
