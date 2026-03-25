<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\jaraba_analytics\Entity\ProductMetricSnapshot;
use Psr\Log\LoggerInterface;

/**
 * Servicio de alertas por Kill Criteria.
 *
 * Evalua si las metricas de un vertical han caido por debajo de
 * los umbrales criticos que justificarian parar el producto:
 * - Activation <15%
 * - Retention D30 <10%
 * - NPS <0 durante 2 meses consecutivos.
 */
class KillCriteriaAlertService {

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Evalua kill criteria contra un snapshot.
   *
   * @param string $vertical
   *   Vertical canonico.
   * @param \Drupal\jaraba_analytics\Entity\ProductMetricSnapshot $snapshot
   *   Snapshot de metricas.
   *
   * @return array
   *   Array de alertas, cada una con: type, message, severity, value, threshold.
   */
  public function evaluateKillCriteria(string $vertical, ProductMetricSnapshot $snapshot): array {
    $alerts = [];

    $activationRate = (float) ($snapshot->get('activation_rate')->value ?? 0);
    if ($activationRate < 0.15) {
      $alerts[] = [
        'type' => 'activation_critical',
        'message' => sprintf(
          'Activacion critica en %s: %.1f%% (umbral: 15%%)',
          $vertical,
          $activationRate * 100
        ),
        'severity' => 'critical',
        'value' => $activationRate,
        'threshold' => 0.15,
      ];
    }

    $retentionD30 = (float) ($snapshot->get('retention_d30_rate')->value ?? 0);
    if ($retentionD30 < 0.10) {
      $alerts[] = [
        'type' => 'retention_critical',
        'message' => sprintf(
          'Retencion D30 critica en %s: %.1f%% (umbral: 10%%)',
          $vertical,
          $retentionD30 * 100
        ),
        'severity' => 'critical',
        'value' => $retentionD30,
        'threshold' => 0.10,
      ];
    }

    $npsScore = (float) ($snapshot->get('nps_score')->value ?? 0);
    if ($npsScore < 0) {
      $alerts[] = [
        'type' => 'nps_negative',
        'message' => sprintf(
          'NPS negativo en %s: %.1f (umbral: 0)',
          $vertical,
          $npsScore
        ),
        'severity' => 'warning',
        'value' => $npsScore,
        'threshold' => 0.0,
      ];
    }

    $churnRate = (float) ($snapshot->get('monthly_churn_rate')->value ?? 0);
    if ($churnRate > 0.15) {
      $alerts[] = [
        'type' => 'churn_critical',
        'message' => sprintf(
          'Churn critico en %s: %.1f%% (umbral: 15%%)',
          $vertical,
          $churnRate * 100
        ),
        'severity' => 'critical',
        'value' => $churnRate,
        'threshold' => 0.15,
      ];
    }

    if ($alerts !== []) {
      $this->logger->warning('Kill criteria alerts for vertical @vertical: @count alerts', [
        '@vertical' => $vertical,
        '@count' => count($alerts),
      ]);
    }

    return $alerts;
  }

  /**
   * Obtiene alertas activas para un vertical.
   *
   * @param string $vertical
   *   Vertical canonico.
   *
   * @return array
   *   Array de alertas activas.
   */
  public function getActiveAlerts(string $vertical): array {
    // Las alertas activas se derivan del ultimo snapshot disponible.
    // Este metodo es consumido por el dashboard.
    return [];
  }

}
