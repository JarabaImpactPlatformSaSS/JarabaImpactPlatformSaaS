<?php

declare(strict_types=1);

namespace Drupal\jaraba_predictive\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_predictive\Service\FeatureStoreService;
use Drupal\jaraba_predictive\Service\RetentionWorkflowService;
use Psr\Log\LoggerInterface;

/**
 * Servicio de prediccion de riesgo de churn para organizaciones.
 *
 * ESTRUCTURA:
 *   Motor heuristico que calcula la probabilidad de abandono (churn)
 *   de un tenant basandose en senales de comportamiento: inactividad,
 *   fallos de pago, tickets de soporte y adopcion de funcionalidades.
 *   Persiste cada calculo como entidad ChurnPrediction (append-only).
 *
 * LOGICA:
 *   Utiliza pesos configurables desde jaraba_predictive.settings
 *   (churn_weights.*) para ponderar cada senal. El risk_score (0-100)
 *   se categoriza en risk_level segun umbrales:
 *     <30 = low, 30-60 = medium, 60-85 = high, >=85 = critical.
 *   Genera acciones recomendadas segun los factores contribuyentes.
 *
 * RELACIONES:
 *   - Consume: entity_type.manager (ChurnPrediction, group storage).
 *   - Consume: config.factory (jaraba_predictive.settings).
 *   - Consume: ecosistema_jaraba_core.tenant_context (resolucion tenant).
 *   - Produce: ChurnPrediction entities (append-only).
 */
class ChurnPredictorService {

  /**
   * Construye el servicio de prediccion de churn.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad para acceso a almacenamiento.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del canal jaraba_predictive.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Fabrica de configuracion para acceder a jaraba_predictive.settings.
   * @param \Drupal\ecosistema_jaraba_core\Service\TenantContextService $tenantContext
   *   Servicio de contexto multi-tenant.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly TenantContextService $tenantContext,
    protected readonly FeatureStoreService $featureStore,
    protected readonly RetentionWorkflowService $retentionWorkflow,
  ) {}

  /**
   * Calcula el riesgo de churn para un tenant.
   *
   * ESTRUCTURA:
   *   Metodo principal que orquesta la recoleccion de senales,
   *   pondera los factores y crea la entidad ChurnPrediction.
   *
   * LOGICA:
   *   1. Carga el grupo (tenant) y valida existencia.
   *   2. Calcula senales: inactividad, fallos de pago, tickets, adopcion.
   *   3. Aplica pesos configurables para obtener risk_score (0-100).
   *   4. Clasifica risk_level segun umbrales.
   *   5. Genera acciones recomendadas segun factores principales.
   *   6. Persiste ChurnPrediction entity (append-only).
   *
   * RELACIONES:
   *   - Lee: group entity (tenant).
   *   - Crea: churn_prediction entity.
   *
   * @param int $tenantId
   *   ID del grupo/organizacion a evaluar.
   *
   * @return array
   *   Array con claves 'prediction' (ChurnPrediction entity) y
   *   'risk_score' (int 0-100).
   *
   * @throws \InvalidArgumentException
   *   Si el tenant no existe.
   */
  public function calculateChurnRisk(int $tenantId): array {
    $groupStorage = $this->entityTypeManager->getStorage('group');
    $tenant = $groupStorage->load($tenantId);

    if (!$tenant) {
      throw new \InvalidArgumentException("Tenant con ID {$tenantId} no encontrado.");
    }

    $config = $this->configFactory->get('jaraba_predictive.settings');
    $weights = $config->get('churn_weights') ?? [];
    $modelVersion = $config->get('model_version') ?? 'heuristic_v2';

    // --- Recolección de señales REALES via FeatureStore ---
    $features = $this->featureStore->getFeatures($tenantId);
    
    $inactivityScore = min(100, ($features['days_since_last_login'] / 30) * 100);
    $paymentScore = min(100, $features['payment_failure_count'] * 33);
    $supportScore = min(100, ($features['support_ticket_count'] / 5) * 100);
    $adoptionScore = (1.0 - $features['feature_adoption_rate']) * 100;
    $contractScore = 20.0; // Fallback heurístico por ahora.

    // --- Ponderación con pesos configurables ---
    $wInactivity = (float) ($weights['inactivity'] ?? 0.35);
    $wPayment = (float) ($weights['payment_failures'] ?? 0.30);
    $wSupport = (float) ($weights['support_tickets'] ?? 0.15);
    $wAdoption = (float) ($weights['feature_adoption'] ?? 0.20);

    $riskScore = (int) round(
      ($inactivityScore * $wInactivity)
      + ($paymentScore * $wPayment)
      + ($supportScore * $wSupport)
      + ($adoptionScore * $wAdoption)
    );

    $riskScore = max(0, min(100, $riskScore));

    // --- Clasificacion de nivel de riesgo ---
    $riskLevel = match (TRUE) {
      $riskScore >= 85 => 'critical',
      $riskScore >= 60 => 'high',
      $riskScore >= 30 => 'medium',
      default => 'low',
    };

    // --- Factores contribuyentes ---
    $contributingFactors = [
      ['factor' => 'inactivity', 'score' => $inactivityScore, 'weight' => $wInactivity],
      ['factor' => 'payment_failures', 'score' => $paymentScore, 'weight' => $wPayment],
      ['factor' => 'support_tickets', 'score' => $supportScore, 'weight' => $wSupport],
      ['factor' => 'feature_adoption', 'score' => $adoptionScore, 'weight' => $wAdoption],
      ['factor' => 'contract_age', 'score' => $contractScore, 'weight' => $wContract],
    ];

    // --- Acciones recomendadas ---
    $recommendedActions = $this->generateRecommendedActions($riskLevel, $contributingFactors);

    // --- Confianza del modelo ---
    $accuracyConfidence = $this->calculateAccuracyConfidence($tenantId);

    // --- Persistir prediccion (append-only) ---
    $predictionStorage = $this->entityTypeManager->getStorage('churn_prediction');
    $prediction = $predictionStorage->create([
      'tenant_id' => $tenantId,
      'risk_score' => $riskScore,
      'risk_level' => $riskLevel,
      'contributing_factors' => json_encode($contributingFactors, JSON_THROW_ON_ERROR),
      'recommended_actions' => json_encode($recommendedActions, JSON_THROW_ON_ERROR),
      'model_version' => $modelVersion,
      'accuracy_confidence' => $accuracyConfidence,
      'features_snapshot' => json_encode([
        'inactivity' => $inactivityScore,
        'payment_failures' => $paymentScore,
        'support_tickets' => $supportScore,
        'feature_adoption' => $adoptionScore,
        'contract_age' => $contractScore,
      ], JSON_THROW_ON_ERROR),
      'calculated_at' => date('Y-m-d\TH:i:s'),
    ]);
    $prediction->save();

    // Disparar flujo de retención proactiva (F189).
    $this->retentionWorkflow->triggerResponse($tenantId, $riskScore, $riskLevel);

    $this->logger->info('Churn prediction calculated for tenant @id: score=@score, level=@level', [
      '@id' => $tenantId,
      '@score' => $riskScore,
      '@level' => $riskLevel,
    ]);

    return [
      'prediction' => $prediction,
      'risk_score' => $riskScore,
    ];
  }

  /**
   * Obtiene la tendencia de churn para un tenant en los ultimos N dias.
   *
   * ESTRUCTURA:
   *   Metodo de consulta que carga el historial de predicciones.
   *
   * LOGICA:
   *   Busca ChurnPrediction entities del tenant con created >= now - days.
   *   Ordena por fecha de creacion ascendente para trazar tendencia.
   *
   * RELACIONES:
   *   - Lee: churn_prediction entities.
   *
   * @param int $tenantId
   *   ID del grupo/organizacion.
   * @param int $days
   *   Numero de dias hacia atras para consultar (default: 90).
   *
   * @return array
   *   Array de arrays con 'date', 'risk_score', 'risk_level' por cada punto.
   */
  public function getChurnTrend(int $tenantId, int $days = 90): array {
    $storage = $this->entityTypeManager->getStorage('churn_prediction');
    $since = strtotime("-{$days} days");

    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('tenant_id', $tenantId)
      ->condition('created', $since, '>=')
      ->sort('created', 'ASC')
      ->execute();

    if (empty($ids)) {
      return [];
    }

    $predictions = $storage->loadMultiple($ids);
    $trend = [];

    foreach ($predictions as $prediction) {
      $trend[] = [
        'id' => (int) $prediction->id(),
        'date' => $prediction->get('created')->value ?? NULL,
        'risk_score' => (int) ($prediction->get('risk_score')->value ?? 0),
        'risk_level' => $prediction->get('risk_level')->value ?? 'low',
      ];
    }

    return $trend;
  }

  /**
   * Obtiene los tenants con mayor riesgo de churn.
   *
   * ESTRUCTURA:
   *   Metodo de consulta que devuelve predicciones de alto riesgo.
   *
   * LOGICA:
   *   Busca ChurnPrediction entities con risk_level 'high' o 'critical'.
   *   Ordena por risk_score descendente. Limita resultados.
   *
   * RELACIONES:
   *   - Lee: churn_prediction entities.
   *
   * @param int $limit
   *   Numero maximo de resultados (default: 20).
   *
   * @return array
   *   Array de arrays con datos de prediccion serializados.
   */
  public function getHighRiskTenants(int $limit = 20): array {
    $storage = $this->entityTypeManager->getStorage('churn_prediction');

    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('risk_level', ['high', 'critical'], 'IN')
      ->sort('risk_score', 'DESC')
      ->range(0, $limit)
      ->execute();

    if (empty($ids)) {
      return [];
    }

    $predictions = $storage->loadMultiple($ids);
    $results = [];

    foreach ($predictions as $prediction) {
      $results[] = [
        'id' => (int) $prediction->id(),
        'tenant_id' => $prediction->get('tenant_id')->target_id ? (int) $prediction->get('tenant_id')->target_id : NULL,
        'risk_score' => (int) ($prediction->get('risk_score')->value ?? 0),
        'risk_level' => $prediction->get('risk_level')->value ?? 'low',
        'contributing_factors' => json_decode($prediction->get('contributing_factors')->value ?? '[]', TRUE),
        'recommended_actions' => json_decode($prediction->get('recommended_actions')->value ?? '[]', TRUE),
        'model_version' => $prediction->get('model_version')->value ?? '',
        'created' => $prediction->get('created')->value ?? NULL,
      ];
    }

    return $results;
  }

  /**
   * Calcula la puntuacion de inactividad de un tenant (0-100).
   *
   * ESTRUCTURA: Metodo interno de calculo de senal individual.
   * LOGICA: Dias desde ultimo login. >30 dias = score 100, 0 dias = score 0.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return float
   *   Puntuacion de inactividad (0-100).
   */
  protected function calculateInactivityScore(int $tenantId): float {
    try {
      $userStorage = $this->entityTypeManager->getStorage('user');
      $ids = $userStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('status', 1)
        ->sort('access', 'DESC')
        ->range(0, 1)
        ->execute();

      if (empty($ids)) {
        return 80.0;
      }

      $users = $userStorage->loadMultiple($ids);
      $lastUser = reset($users);
      $lastAccess = (int) ($lastUser->get('access')->value ?? 0);

      if ($lastAccess === 0) {
        return 90.0;
      }

      $daysSinceLogin = (int) ((time() - $lastAccess) / 86400);

      return min(100.0, ($daysSinceLogin / 30.0) * 100.0);
    }
    catch (\Exception $e) {
      $this->logger->warning('Error calculating inactivity score for tenant @id: @message', [
        '@id' => $tenantId,
        '@message' => $e->getMessage(),
      ]);
      return 50.0;
    }
  }

  /**
   * Calcula la puntuacion de fallos de pago (0-100).
   *
   * ESTRUCTURA: Metodo interno de calculo de senal individual.
   * LOGICA: Numero de fallos de pago en ultimos 90 dias.
   *   0 fallos = 0, 1 fallo = 30, 2 fallos = 60, 3+ = 100.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return float
   *   Puntuacion de fallos de pago (0-100).
   */
  protected function calculatePaymentFailureScore(int $tenantId): float {
    // Heuristico: sin integracion directa con pasarela de pago,
    // se retorna un valor base conservador.
    return 0.0;
  }

  /**
   * Calcula la puntuacion de tickets de soporte (0-100).
   *
   * ESTRUCTURA: Metodo interno de calculo de senal individual.
   * LOGICA: Ratio de tickets abiertos vs resueltos en 30 dias.
   *   Alto volumen de tickets sin resolver = mayor riesgo.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return float
   *   Puntuacion de tickets de soporte (0-100).
   */
  protected function calculateSupportTicketScore(int $tenantId): float {
    // Heuristico: se retorna valor base sin integracion con sistema de tickets.
    return 10.0;
  }

  /**
   * Calcula la puntuacion de adopcion de funcionalidades (0-100).
   *
   * ESTRUCTURA: Metodo interno de calculo de senal individual.
   * LOGICA: Porcentaje de funcionalidades del plan que usa el tenant.
   *   Menor adopcion = mayor riesgo (escala invertida).
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return float
   *   Puntuacion de riesgo por baja adopcion (0-100). 100 = sin adopcion.
   */
  protected function calculateFeatureAdoptionScore(int $tenantId): float {
    // Heuristico: se asume adopcion media sin datos de telemetria.
    return 30.0;
  }

  /**
   * Calcula la puntuacion de antiguedad del contrato (0-100).
   *
   * ESTRUCTURA: Metodo interno de calculo de senal individual.
   * LOGICA: Contratos nuevos (<3 meses) tienen mayor riesgo. Contratos
   *   veteranos (>12 meses) tienen menor riesgo.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return float
   *   Puntuacion de riesgo por antiguedad (0-100).
   */
  protected function calculateContractAgeScore(int $tenantId): float {
    // Heuristico: sin fecha de contrato, se asume riesgo moderado.
    return 20.0;
  }

  /**
   * Genera acciones recomendadas basadas en el nivel de riesgo.
   *
   * ESTRUCTURA: Metodo interno de generacion de recomendaciones.
   * LOGICA: Mapea cada nivel de riesgo a un conjunto de acciones sugeridas.
   *
   * @param string $riskLevel
   *   Nivel de riesgo: low, medium, high, critical.
   * @param array $factors
   *   Factores contribuyentes con sus puntuaciones.
   *
   * @return array
   *   Array de acciones recomendadas con 'action', 'priority', 'description'.
   */
  protected function generateRecommendedActions(string $riskLevel, array $factors): array {
    $actions = [];

    if ($riskLevel === 'critical') {
      $actions[] = [
        'action' => 'executive_outreach',
        'priority' => 'urgent',
        'description' => 'Contacto inmediato por parte de ejecutivo de cuentas.',
      ];
      $actions[] = [
        'action' => 'retention_offer',
        'priority' => 'urgent',
        'description' => 'Preparar oferta de retencion personalizada.',
      ];
    }

    if ($riskLevel === 'high' || $riskLevel === 'critical') {
      $actions[] = [
        'action' => 'csm_call',
        'priority' => 'high',
        'description' => 'Programar llamada con Customer Success Manager.',
      ];
      $actions[] = [
        'action' => 'usage_review',
        'priority' => 'high',
        'description' => 'Revisar patrones de uso y ofrecer onboarding adicional.',
      ];
    }

    if ($riskLevel === 'medium') {
      $actions[] = [
        'action' => 'engagement_campaign',
        'priority' => 'medium',
        'description' => 'Incluir en campana de engagement automatizada.',
      ];
      $actions[] = [
        'action' => 'feature_highlight',
        'priority' => 'medium',
        'description' => 'Enviar comunicacion destacando funcionalidades no utilizadas.',
      ];
    }

    if ($riskLevel === 'low') {
      $actions[] = [
        'action' => 'monitor',
        'priority' => 'low',
        'description' => 'Continuar monitoreo regular sin accion inmediata.',
      ];
    }

    // Agregar acciones especificas basadas en factores principales.
    usort($factors, fn(array $a, array $b) => $b['score'] <=> $a['score']);
    $topFactor = $factors[0] ?? NULL;

    if ($topFactor && $topFactor['score'] > 60) {
      $actions[] = [
        'action' => 'address_' . $topFactor['factor'],
        'priority' => 'high',
        'description' => "Abordar factor principal: {$topFactor['factor']} (score: {$topFactor['score']}).",
      ];
    }

    return $actions;
  }

  /**
   * Calcula la confianza del modelo basada en datos disponibles.
   *
   * ESTRUCTURA: Metodo interno de calculo de confianza.
   * LOGICA: Mayor cantidad de datos historicos = mayor confianza.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return string
   *   Confianza como decimal string (0.00-1.00).
   */
  protected function calculateAccuracyConfidence(int $tenantId): string {
    $storage = $this->entityTypeManager->getStorage('churn_prediction');
    $count = (int) $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('tenant_id', $tenantId)
      ->count()
      ->execute();

    // Mas historial = mayor confianza, maximo 0.85 con heuristico.
    $confidence = min(0.85, 0.40 + ($count * 0.05));

    return number_format($confidence, 2);
  }

}
