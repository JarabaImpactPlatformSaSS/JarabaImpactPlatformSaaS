<?php

declare(strict_types=1);

namespace Drupal\jaraba_predictive\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\jaraba_predictive\Service\AnomalyDetectorService;
use Drupal\jaraba_predictive\Service\ChurnPredictorService;
use Drupal\jaraba_predictive\Service\FeatureStoreService;
use Drupal\jaraba_predictive\Service\ForecastEngineService;
use Drupal\jaraba_predictive\Service\LeadScorerService;
use Drupal\jaraba_predictive\Service\RetentionWorkflowService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller de la API REST de Predicciones y Analytics.
 *
 * ESTRUCTURA:
 *   Endpoints JSON para churn prediction, lead scoring, forecasting,
 *   anomaly detection y retention workflows. Sigue el patron del
 *   ecosistema con envelope estandar {data}/{data,meta}/{error}.
 *
 * LOGICA:
 *   Cada endpoint delega la logica de negocio al servicio correspondiente
 *   y envuelve el resultado en el formato de respuesta estandar.
 *   Los errores se capturan y retornan con codigo HTTP apropiado.
 *
 * RELACIONES:
 *   - Consume: jaraba_predictive.churn_predictor.
 *   - Consume: jaraba_predictive.lead_scorer.
 *   - Consume: jaraba_predictive.forecast_engine.
 *   - Consume: jaraba_predictive.anomaly_detector.
 *   - Consume: jaraba_predictive.feature_store.
 *   - Consume: jaraba_predictive.retention_workflow.
 */
class PredictiveApiController extends ControllerBase {

  /**
   * Constructor con inyeccion de dependencias.
   *
   * ESTRUCTURA: Recibe los 6 servicios del modulo jaraba_predictive.
   * LOGICA: PHP 8.3 promoted properties para asignacion automatica.
   *
   * @param \Drupal\jaraba_predictive\Service\ChurnPredictorService $churnPredictor
   *   Servicio de prediccion de churn.
   * @param \Drupal\jaraba_predictive\Service\LeadScorerService $leadScorer
   *   Servicio de lead scoring.
   * @param \Drupal\jaraba_predictive\Service\ForecastEngineService $forecastEngine
   *   Servicio de forecasting.
   * @param \Drupal\jaraba_predictive\Service\AnomalyDetectorService $anomalyDetector
   *   Servicio de deteccion de anomalias.
   * @param \Drupal\jaraba_predictive\Service\FeatureStoreService $featureStore
   *   Servicio de almacen de features.
   * @param \Drupal\jaraba_predictive\Service\RetentionWorkflowService $retentionWorkflow
   *   Servicio de flujos de retencion.
   */
  public function __construct(
    protected ChurnPredictorService $churnPredictor,
    protected LeadScorerService $leadScorer,
    protected ForecastEngineService $forecastEngine,
    protected AnomalyDetectorService $anomalyDetector,
    protected FeatureStoreService $featureStore,
    protected RetentionWorkflowService $retentionWorkflow,
  ) {}

  /**
   * {@inheritdoc}
   *
   * ESTRUCTURA: Factory method estatico requerido por ControllerBase.
   * LOGICA: Resuelve los 6 servicios desde el contenedor DI.
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_predictive.churn_predictor'),
      $container->get('jaraba_predictive.lead_scorer'),
      $container->get('jaraba_predictive.forecast_engine'),
      $container->get('jaraba_predictive.anomaly_detector'),
      $container->get('jaraba_predictive.feature_store'),
      $container->get('jaraba_predictive.retention_workflow'),
    );
  }

  // ============================================
  // CHURN PREDICTION
  // ============================================

  /**
   * POST /api/v1/predictions/churn/predict — Predecir churn de un tenant.
   *
   * ESTRUCTURA: Endpoint de escritura que ejecuta prediccion de churn.
   * LOGICA: Extrae tenant_id del body JSON, delega a ChurnPredictorService,
   *   serializa la prediccion y retorna en envelope {data}.
   */
  public function predictChurn(Request $request): JsonResponse {
    try {
      $body = json_decode($request->getContent(), TRUE, 512, JSON_THROW_ON_ERROR);
      $tenantId = (int) ($body['tenant_id'] ?? 0);

      if ($tenantId <= 0) {
        return new JsonResponse([
          'error' => (string) new TranslatableMarkup('Se requiere tenant_id valido.'),
        ], 400);
      }

      $result = $this->churnPredictor->calculateChurnRisk($tenantId);
      $prediction = $result['prediction'];

      return new JsonResponse([
        'data' => [
          'id' => (int) $prediction->id(),
          'tenant_id' => $tenantId,
          'risk_score' => $result['risk_score'],
          'risk_level' => $prediction->get('risk_level')->value ?? 'low',
          'contributing_factors' => json_decode($prediction->get('contributing_factors')->value ?? '[]', TRUE),
          'recommended_actions' => json_decode($prediction->get('recommended_actions')->value ?? '[]', TRUE),
          'model_version' => $prediction->get('model_version')->value ?? '',
          'accuracy_confidence' => $prediction->get('accuracy_confidence')->value ?? '0.00',
          'calculated_at' => $prediction->get('calculated_at')->value ?? NULL,
        ],
      ]);
    }
    catch (\InvalidArgumentException $e) {
      return new JsonResponse([
        'error' => $e->getMessage(),
      ], 404);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => (string) new TranslatableMarkup('Error al calcular la prediccion de churn.'),
      ], 500);
    }
  }

  /**
   * GET /api/v1/predictions/churn/history — Historial de predicciones de churn.
   *
   * ESTRUCTURA: Endpoint de lectura del historial de churn.
   * LOGICA: Acepta query params tenant_id y days. Delega a
   *   ChurnPredictorService::getChurnTrend() o getHighRiskTenants().
   */
  public function churnHistory(Request $request): JsonResponse {
    try {
      $tenantId = (int) $request->query->get('tenant_id', 0);
      $days = (int) $request->query->get('days', 90);

      if ($tenantId > 0) {
        $data = $this->churnPredictor->getChurnTrend($tenantId, $days);
      }
      else {
        $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
        $data = $this->churnPredictor->getHighRiskTenants($limit);
      }

      return new JsonResponse([
        'data' => $data,
        'meta' => [
          'tenant_id' => $tenantId ?: NULL,
          'days' => $days,
        ],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => (string) new TranslatableMarkup('Error al obtener el historial de churn.'),
      ], 500);
    }
  }

  /**
   * POST /api/v1/predictions/churn/batch — Prediccion de churn masiva.
   *
   * ESTRUCTURA: Endpoint de escritura batch para multiples tenants.
   * LOGICA: Extrae tenant_ids del body JSON, ejecuta prediccion para
   *   cada uno y retorna resultados agregados.
   */
  public function churnBatch(Request $request): JsonResponse {
    try {
      $body = json_decode($request->getContent(), TRUE, 512, JSON_THROW_ON_ERROR);
      $tenantIds = (array) ($body['tenant_ids'] ?? []);

      if (empty($tenantIds)) {
        return new JsonResponse([
          'error' => (string) new TranslatableMarkup('Se requiere tenant_ids (array).'),
        ], 400);
      }

      $results = [];
      $errors = [];

      foreach ($tenantIds as $tenantId) {
        $tenantId = (int) $tenantId;
        try {
          $result = $this->churnPredictor->calculateChurnRisk($tenantId);
          $prediction = $result['prediction'];
          $results[] = [
            'tenant_id' => $tenantId,
            'risk_score' => $result['risk_score'],
            'risk_level' => $prediction->get('risk_level')->value ?? 'low',
            'prediction_id' => (int) $prediction->id(),
          ];
        }
        catch (\Exception $e) {
          $errors[] = [
            'tenant_id' => $tenantId,
            'error' => $e->getMessage(),
          ];
        }
      }

      return new JsonResponse([
        'data' => $results,
        'meta' => [
          'total_requested' => count($tenantIds),
          'total_success' => count($results),
          'total_errors' => count($errors),
          'errors' => $errors,
        ],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => (string) new TranslatableMarkup('Error al procesar la prediccion batch de churn.'),
      ], 500);
    }
  }

  // ============================================
  // LEAD SCORING
  // ============================================

  /**
   * POST /api/v1/predictions/leads/score — Puntuar un lead.
   *
   * ESTRUCTURA: Endpoint de escritura que ejecuta lead scoring.
   * LOGICA: Extrae user_id del body JSON, delega a LeadScorerService,
   *   serializa el score y retorna en envelope {data}.
   */
  public function scoreLead(Request $request): JsonResponse {
    try {
      $body = json_decode($request->getContent(), TRUE, 512, JSON_THROW_ON_ERROR);
      $userId = (int) ($body['user_id'] ?? 0);

      if ($userId <= 0) {
        return new JsonResponse([
          'error' => (string) new TranslatableMarkup('Se requiere user_id valido.'),
        ], 400);
      }

      $result = $this->leadScorer->scoreUser($userId);
      $leadScore = $result['lead_score'];

      return new JsonResponse([
        'data' => [
          'id' => (int) $leadScore->id(),
          'user_id' => $userId,
          'total_score' => (int) ($leadScore->get('total_score')->value ?? 0),
          'qualification' => $leadScore->get('qualification')->value ?? 'cold',
          'score_breakdown' => json_decode($leadScore->get('score_breakdown')->value ?? '{}', TRUE),
          'last_activity' => $leadScore->get('last_activity')->value ?? NULL,
          'model_version' => $leadScore->get('model_version')->value ?? '',
          'calculated_at' => $leadScore->get('calculated_at')->value ?? NULL,
        ],
      ]);
    }
    catch (\InvalidArgumentException $e) {
      return new JsonResponse([
        'error' => $e->getMessage(),
      ], 404);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => (string) new TranslatableMarkup('Error al calcular la puntuacion del lead.'),
      ], 500);
    }
  }

  /**
   * POST /api/v1/predictions/leads/batch — Lead scoring masivo.
   *
   * ESTRUCTURA: Endpoint de escritura batch para multiples leads.
   * LOGICA: Extrae user_ids del body JSON, ejecuta scoring para cada
   *   uno y retorna resultados agregados.
   */
  public function leadBatch(Request $request): JsonResponse {
    try {
      $body = json_decode($request->getContent(), TRUE, 512, JSON_THROW_ON_ERROR);
      $userIds = (array) ($body['user_ids'] ?? []);

      if (empty($userIds)) {
        return new JsonResponse([
          'error' => (string) new TranslatableMarkup('Se requiere user_ids (array).'),
        ], 400);
      }

      $results = [];
      $errors = [];

      foreach ($userIds as $userId) {
        $userId = (int) $userId;
        try {
          $result = $this->leadScorer->scoreUser($userId);
          $leadScore = $result['lead_score'];
          $results[] = [
            'user_id' => $userId,
            'total_score' => (int) ($leadScore->get('total_score')->value ?? 0),
            'qualification' => $leadScore->get('qualification')->value ?? 'cold',
            'lead_score_id' => (int) $leadScore->id(),
          ];
        }
        catch (\Exception $e) {
          $errors[] = [
            'user_id' => $userId,
            'error' => $e->getMessage(),
          ];
        }
      }

      return new JsonResponse([
        'data' => $results,
        'meta' => [
          'total_requested' => count($userIds),
          'total_success' => count($results),
          'total_errors' => count($errors),
          'errors' => $errors,
        ],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => (string) new TranslatableMarkup('Error al procesar el scoring batch de leads.'),
      ], 500);
    }
  }

  /**
   * POST /api/v1/predictions/leads/{lead_score}/events — Registrar evento de lead.
   *
   * ESTRUCTURA: Endpoint de escritura para tracking de eventos.
   * LOGICA: Carga la entidad LeadScore, agrega el evento al campo
   *   events_tracked (JSON), actualiza last_activity.
   */
  public function trackLeadEvent(int $lead_score, Request $request): JsonResponse {
    try {
      $storage = $this->entityTypeManager()->getStorage('lead_score');
      $leadScore = $storage->load($lead_score);

      if (!$leadScore) {
        return new JsonResponse([
          'error' => (string) new TranslatableMarkup('Lead score no encontrado.'),
        ], 404);
      }

      $body = json_decode($request->getContent(), TRUE, 512, JSON_THROW_ON_ERROR);
      $eventType = $body['event_type'] ?? 'unknown';
      $eventData = $body['event_data'] ?? [];

      // Agregar evento a events_tracked.
      $existingEvents = json_decode($leadScore->get('events_tracked')->value ?? '[]', TRUE);
      $existingEvents[] = [
        'type' => $eventType,
        'data' => $eventData,
        'timestamp' => date('Y-m-d\TH:i:s'),
      ];

      $leadScore->set('events_tracked', json_encode($existingEvents, JSON_THROW_ON_ERROR));
      $leadScore->set('last_activity', date('Y-m-d\TH:i:s'));
      $leadScore->save();

      return new JsonResponse([
        'data' => [
          'lead_score_id' => (int) $leadScore->id(),
          'event_type' => $eventType,
          'total_events' => count($existingEvents),
          'tracked_at' => date('Y-m-d\TH:i:s'),
        ],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => (string) new TranslatableMarkup('Error al registrar el evento del lead.'),
      ], 500);
    }
  }

  // ============================================
  // FORECAST
  // ============================================

  /**
   * GET /api/v1/predictions/forecast/mrr — Forecast de MRR.
   *
   * ESTRUCTURA: Endpoint de lectura que genera o consulta forecasts.
   * LOGICA: Acepta query params action (generate|history), period.
   *   Si action=generate, ejecuta ForecastEngineService::generateForecast().
   *   Si action=history, ejecuta getForecastHistory().
   */
  public function forecastMrr(Request $request): JsonResponse {
    try {
      $action = $request->query->get('action', 'history');
      $metric = $request->query->get('metric', 'mrr');
      $period = $request->query->get('period', 'monthly');

      if ($action === 'generate') {
        $result = $this->forecastEngine->generateForecast($metric, $period);
        $forecast = $result['forecast'];

        return new JsonResponse([
          'data' => [
            'id' => (int) $forecast->id(),
            'forecast_type' => $forecast->get('forecast_type')->value ?? '',
            'period' => $forecast->get('period')->value ?? '',
            'predicted_value' => (float) ($forecast->get('predicted_value')->value ?? 0),
            'confidence_low' => (float) ($forecast->get('confidence_low')->value ?? 0),
            'confidence_high' => (float) ($forecast->get('confidence_high')->value ?? 0),
            'model_version' => $forecast->get('model_version')->value ?? '',
            'forecast_date' => $forecast->get('forecast_date')->value ?? NULL,
            'calculated_at' => $forecast->get('calculated_at')->value ?? NULL,
          ],
        ]);
      }

      // Default: historial.
      $limit = min(50, max(1, (int) $request->query->get('limit', 12)));
      $data = $this->forecastEngine->getForecastHistory($metric, $limit);

      return new JsonResponse([
        'data' => $data,
        'meta' => [
          'metric' => $metric,
          'period' => $period,
          'limit' => $limit,
        ],
      ]);
    }
    catch (\InvalidArgumentException $e) {
      return new JsonResponse([
        'error' => $e->getMessage(),
      ], 400);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => (string) new TranslatableMarkup('Error al obtener el forecast.'),
      ], 500);
    }
  }

  // ============================================
  // ANOMALY DETECTION
  // ============================================

  /**
   * GET /api/v1/predictions/anomalies — Detectar anomalias.
   *
   * ESTRUCTURA: Endpoint de lectura que ejecuta deteccion de anomalias.
   * LOGICA: Acepta query params metric y lookback_days.
   *   Si no se especifica metric, retorna anomalias recientes de todas
   *   las metricas via getRecentAnomalies().
   */
  public function detectAnomalies(Request $request): JsonResponse {
    try {
      $metric = $request->query->get('metric');
      $lookbackDays = (int) $request->query->get('lookback_days', 30);

      if ($metric) {
        $result = $this->anomalyDetector->detectAnomalies($metric, $lookbackDays);

        return new JsonResponse([
          'data' => $result,
          'meta' => [
            'metric' => $metric,
            'lookback_days' => $lookbackDays,
          ],
        ]);
      }

      // Sin metric especifico: retornar anomalias recientes.
      $limit = min(50, max(1, (int) $request->query->get('limit', 10)));
      $anomalies = $this->anomalyDetector->getRecentAnomalies($limit);

      return new JsonResponse([
        'data' => $anomalies,
        'meta' => [
          'limit' => $limit,
        ],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => (string) new TranslatableMarkup('Error al detectar anomalias.'),
      ], 500);
    }
  }

  // ============================================
  // STATS / DASHBOARD METRICS
  // ============================================

  /**
   * GET /api/v1/predictions/stats — Metricas agregadas del dashboard.
   *
   * ESTRUCTURA: Endpoint de lectura que agrega metricas de todos los servicios.
   * LOGICA: Consulta a churn, leads, forecast y retention para generar
   *   un resumen ejecutivo del estado predictivo.
   */
  public function getStats(Request $request): JsonResponse {
    try {
      // --- Churn metrics ---
      $highRiskTenants = $this->churnPredictor->getHighRiskTenants(5);

      // --- Lead metrics ---
      $topLeads = $this->leadScorer->getTopLeads(5);

      // --- Anomaly metrics ---
      $recentAnomalies = $this->anomalyDetector->getRecentAnomalies(5);

      // --- Retention metrics ---
      $retentionMetrics = $this->retentionWorkflow->getRetentionMetrics();

      // --- Forecast (latest MRR) ---
      $mrrForecasts = $this->forecastEngine->getForecastHistory('mrr', 1);

      return new JsonResponse([
        'data' => [
          'churn' => [
            'high_risk_count' => count($highRiskTenants),
            'high_risk_tenants' => $highRiskTenants,
          ],
          'leads' => [
            'top_leads_count' => count($topLeads),
            'top_leads' => $topLeads,
          ],
          'anomalies' => [
            'recent_count' => count($recentAnomalies),
            'recent' => $recentAnomalies,
          ],
          'retention' => $retentionMetrics,
          'forecast' => [
            'latest_mrr' => $mrrForecasts[0] ?? NULL,
          ],
        ],
        'meta' => [
          'generated_at' => date('Y-m-d\TH:i:s'),
        ],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => (string) new TranslatableMarkup('Error al obtener las metricas del dashboard.'),
      ], 500);
    }
  }

}
