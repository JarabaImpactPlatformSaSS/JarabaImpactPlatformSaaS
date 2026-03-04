<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_analytics\Entity\ProductMetricSnapshot;

/**
 * Servicio de agregacion de metricas de producto.
 *
 * Genera snapshots diarios combinando datos de activacion, retencion,
 * NPS y churn. Se invoca desde cron despues de AnalyticsAggregatorService.
 */
class ProductMetricsAggregatorService {

  public function __construct(
    protected ActivationTrackingService $activationTracking,
    protected RetentionCalculatorService $retentionCalculator,
    protected NpsSurveyService $npsSurvey,
    protected KillCriteriaAlertService $killCriteriaAlert,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Genera un snapshot diario para un vertical/tenant.
   *
   * @param string $vertical
   *   Vertical canonico.
   * @param string $tenantId
   *   ID del tenant.
   *
   * @return \Drupal\jaraba_analytics\Entity\ProductMetricSnapshot|null
   *   El snapshot creado, o NULL en caso de error.
   */
  public function generateDailySnapshot(string $vertical, string $tenantId): ?ProductMetricSnapshot {
    try {
      $storage = $this->entityTypeManager->getStorage('product_metric_snapshot');

      // Calcular metricas.
      $activationRate = $this->activationTracking->calculateActivationRate($vertical, $tenantId);
      $retentionD7 = $this->retentionCalculator->calculateRetention($vertical, $tenantId, 7);
      $retentionD30 = $this->retentionCalculator->calculateRetention($vertical, $tenantId, 30);
      $npsBreakdown = $this->npsSurvey->getNpsBreakdown($vertical);

      // Estimar churn.
      $churnRate = 1.0 - $retentionD30;

      /** @var \Drupal\jaraba_analytics\Entity\ProductMetricSnapshot $snapshot */
      $snapshot = $storage->create([
        'snapshot_date' => date('Y-m-d'),
        'vertical' => $vertical,
        'tenant_id' => $tenantId,
        'total_users' => 0,
        'activated_users' => 0,
        'activation_rate' => $activationRate,
        'retained_d7' => 0,
        'retained_d30' => 0,
        'retention_d30_rate' => $retentionD30,
        'nps_score' => $npsBreakdown['score'],
        'nps_promoters' => $npsBreakdown['promoters'],
        'nps_detractors' => $npsBreakdown['detractors'],
        'nps_passives' => $npsBreakdown['passives'],
        'monthly_churn_rate' => $churnRate,
        'churned_users' => 0,
        'kill_criteria_triggered' => FALSE,
      ]);

      // Evaluar kill criteria.
      $snapshot->save();
      $alerts = $this->killCriteriaAlert->evaluateKillCriteria($vertical, $snapshot);
      if ($alerts !== []) {
        $snapshot->set('kill_criteria_triggered', TRUE);
        $snapshot->save();
      }

      return $snapshot;
    }
    catch (\Throwable $e) {
      return NULL;
    }
  }

  /**
   * Ejecuta la agregacion diaria para todos los verticales activos.
   *
   * Invocado desde cron.
   */
  public function runDailyAggregation(): void {
    $verticals = [
      'empleabilidad', 'emprendimiento', 'comercioconecta', 'agroconecta',
      'jarabalex', 'serviciosconecta', 'andalucia_ei', 'jaraba_content_hub',
      'formacion', 'demo',
    ];

    foreach ($verticals as $vertical) {
      try {
        // Usar tenant_id '0' como global (sin tenant).
        $this->generateDailySnapshot($vertical, '0');
      }
      catch (\Throwable) {
        // Continue with other verticals on error.
      }
    }
  }

}
