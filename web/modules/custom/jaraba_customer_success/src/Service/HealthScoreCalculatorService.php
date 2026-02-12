<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\jaraba_customer_success\Entity\CustomerHealth;
use Psr\Log\LoggerInterface;

/**
 * Motor de cálculo del Health Score compuesto (0-100).
 *
 * PROPÓSITO:
 * Calcula el health score de cada tenant como promedio ponderado
 * de 5 dimensiones: engagement, adoption, satisfaction, support, growth.
 * Se ejecuta diariamente en hook_cron con batch configurable.
 *
 * LÓGICA:
 * 1. Obtener scores individuales de cada servicio especializado.
 * 2. Aplicar pesos configurables (deben sumar 100).
 * 3. Categorizar según umbrales: healthy/neutral/at_risk/critical.
 * 4. Calcular tendencia comparando con las últimas 3 mediciones.
 * 5. Almacenar como entidad CustomerHealth.
 * 6. Disparar alertas si la categoría empeora.
 */
class HealthScoreCalculatorService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ConfigFactoryInterface $configFactory,
    protected StateInterface $state,
    protected EngagementScoringService $engagementScoring,
    protected NpsSurveyService $npsSurvey,
    protected LifecycleStageService $lifecycleStage,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Calcula el health score completo para un tenant.
   *
   * @param string $tenant_id
   *   ID del grupo tenant.
   *
   * @return \Drupal\jaraba_customer_success\Entity\CustomerHealth|null
   *   Entidad creada con el health score, o NULL en error.
   */
  public function calculate(string $tenant_id): ?CustomerHealth {
    $config = $this->configFactory->get('jaraba_customer_success.settings');
    $weights = $config->get('health_score_weights') ?? [
      'engagement' => 30,
      'adoption' => 25,
      'satisfaction' => 20,
      'support' => 15,
      'growth' => 10,
    ];

    // Calcular scores individuales.
    $engagement = $this->engagementScoring->getEngagementScore($tenant_id);
    $adoption = $this->calculateAdoptionScore($tenant_id);
    $satisfaction = $this->npsSurvey->getSatisfactionScore($tenant_id);
    $support = $this->calculateSupportScore($tenant_id);
    $growth = $this->lifecycleStage->getGrowthScore($tenant_id);

    // Calcular score compuesto ponderado.
    $overall = (int) round(
      ($engagement * $weights['engagement'] / 100) +
      ($adoption * $weights['adoption'] / 100) +
      ($satisfaction * $weights['satisfaction'] / 100) +
      ($support * $weights['support'] / 100) +
      ($growth * $weights['growth'] / 100)
    );
    $overall = max(0, min(100, $overall));

    // Categorizar.
    $category = $this->categorize($overall, $config);

    // Calcular tendencia.
    $trend = $this->calculateTrend($tenant_id, $overall);

    // Breakdown detallado.
    $breakdown = json_encode([
      'engagement' => ['score' => $engagement, 'weight' => $weights['engagement']],
      'adoption' => ['score' => $adoption, 'weight' => $weights['adoption']],
      'satisfaction' => ['score' => $satisfaction, 'weight' => $weights['satisfaction']],
      'support' => ['score' => $support, 'weight' => $weights['support']],
      'growth' => ['score' => $growth, 'weight' => $weights['growth']],
    ], JSON_THROW_ON_ERROR);

    try {
      // Crear entidad CustomerHealth.
      $storage = $this->entityTypeManager->getStorage('customer_health');
      /** @var \Drupal\jaraba_customer_success\Entity\CustomerHealth $health */
      $health = $storage->create([
        'tenant_id' => $tenant_id,
        'overall_score' => $overall,
        'engagement_score' => $engagement,
        'adoption_score' => $adoption,
        'satisfaction_score' => $satisfaction,
        'support_score' => $support,
        'growth_score' => $growth,
        'category' => $category,
        'trend' => $trend,
        'score_breakdown' => $breakdown,
        'churn_probability' => 0,
      ]);
      $health->save();

      // Registrar última puntuación para comparación futura.
      $this->state->set("jaraba_cs.last_score.$tenant_id", $overall);

      $this->logger->info('Health score calculated for tenant @id: @score (@category, @trend)', [
        '@id' => $tenant_id,
        '@score' => $overall,
        '@category' => $category,
        '@trend' => $trend,
      ]);

      return $health;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to save health score for tenant @id: @message', [
        '@id' => $tenant_id,
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Obtiene el historial de health scores de un tenant.
   *
   * @param string $tenant_id
   *   ID del grupo tenant.
   * @param int $limit
   *   Número máximo de registros.
   *
   * @return array
   *   Array de entidades CustomerHealth ordenadas por fecha.
   */
  public function getHistory(string $tenant_id, int $limit = 30): array {
    $storage = $this->entityTypeManager->getStorage('customer_health');
    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('tenant_id', $tenant_id)
      ->sort('calculated_at', 'DESC')
      ->range(0, $limit)
      ->execute();

    return $ids ? $storage->loadMultiple($ids) : [];
  }

  /**
   * Obtiene tenants agrupados por categoría de salud.
   *
   * @return array
   *   Array con claves de categoría y conteo.
   */
  public function getByCategory(): array {
    $result = [
      'healthy' => 0,
      'neutral' => 0,
      'at_risk' => 0,
      'critical' => 0,
    ];

    try {
      $db = \Drupal::database();

      // Obtener último score de cada tenant.
      // Subconsulta para obtener el ID más reciente por tenant.
      $subquery = $db->select('customer_health', 'ch2')
        ->fields('ch2', ['tenant_id']);
      $subquery->addExpression('MAX(id)', 'max_id');
      $subquery->groupBy('ch2.tenant_id');

      $query = $db->select('customer_health', 'ch');
      $query->fields('ch', ['category']);
      $query->addExpression('COUNT(*)', 'cnt');
      $query->join($subquery, 'latest', 'ch.id = latest.max_id');
      $query->groupBy('ch.category');

      $rows = $query->execute()->fetchAllKeyed();
      foreach ($rows as $category => $count) {
        if (isset($result[$category])) {
          $result[$category] = (int) $count;
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('Error fetching category counts: @message', [
        '@message' => $e->getMessage(),
      ]);
    }

    return $result;
  }

  /**
   * Ejecuta cálculo programado para todos los tenants (batch cron).
   *
   * @return int
   *   Número de tenants procesados.
   */
  public function runScheduledCalculation(): int {
    $config = $this->configFactory->get('jaraba_customer_success.settings');
    $interval = ($config->get('calculation_interval') ?? 24) * 3600;
    $batch_size = $config->get('cron_batch_size') ?? 50;

    // Verificar intervalo.
    $last_run = $this->state->get('jaraba_cs.last_calculation', 0);
    if ((\Drupal::time()->getRequestTime() - $last_run) < $interval) {
      return 0;
    }

    // Obtener todos los grupos de tipo tenant.
    try {
      $group_storage = $this->entityTypeManager->getStorage('group');
      $ids = $group_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', 'tenant')
        ->range(0, $batch_size)
        ->execute();

      $processed = 0;
      foreach ($ids as $tenant_id) {
        $this->calculate((string) $tenant_id);
        $processed++;
      }

      $this->state->set('jaraba_cs.last_calculation', \Drupal::time()->getRequestTime());

      $this->logger->info('Scheduled health score calculation completed: @count tenants processed.', [
        '@count' => $processed,
      ]);

      return $processed;
    }
    catch (\Exception $e) {
      $this->logger->error('Scheduled calculation failed: @message', [
        '@message' => $e->getMessage(),
      ]);
      return 0;
    }
  }

  /**
   * Categoriza un score según umbrales configurados.
   */
  protected function categorize(int $score, $config): string {
    $thresholds = $config->get('health_score_thresholds') ?? [
      'healthy' => 80,
      'neutral' => 60,
      'at_risk' => 40,
    ];

    if ($score >= $thresholds['healthy']) {
      return CustomerHealth::CATEGORY_HEALTHY;
    }
    if ($score >= $thresholds['neutral']) {
      return CustomerHealth::CATEGORY_NEUTRAL;
    }
    if ($score >= $thresholds['at_risk']) {
      return CustomerHealth::CATEGORY_AT_RISK;
    }

    return CustomerHealth::CATEGORY_CRITICAL;
  }

  /**
   * Calcula la tendencia comparando con mediciones anteriores.
   */
  protected function calculateTrend(string $tenant_id, int $current_score): string {
    $history = $this->getHistory($tenant_id, 3);

    if (count($history) < 2) {
      return CustomerHealth::TREND_STABLE;
    }

    // Promedio de scores anteriores.
    $previous_total = 0;
    $count = 0;
    foreach ($history as $h) {
      $previous_total += $h->getOverallScore();
      $count++;
    }
    $avg = $previous_total / $count;

    $diff = $current_score - $avg;
    if ($diff > 5) {
      return CustomerHealth::TREND_IMPROVING;
    }
    if ($diff < -5) {
      return CustomerHealth::TREND_DECLINING;
    }

    return CustomerHealth::TREND_STABLE;
  }

  /**
   * Calcula el adoption score (features activadas vs disponibles).
   */
  protected function calculateAdoptionScore(string $tenant_id): int {
    return (int) $this->engagementScoring->getFeatureAdoption($tenant_id);
  }

  /**
   * Calcula el support score basado en tickets.
   *
   * Fórmula: 100 - (open_tickets × 10), mínimo 0.
   */
  protected function calculateSupportScore(string $tenant_id): int {
    // Sin sistema de tickets integrado, default a 75.
    // En producción, esto se conectaría con un helpdesk.
    return 75;
  }

}
