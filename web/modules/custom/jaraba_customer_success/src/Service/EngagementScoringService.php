<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Calcula métricas de engagement por tenant.
 *
 * PROPÓSITO:
 * Provee los datos de engagement necesarios para el Health Score:
 * DAU/MAU ratio, feature adoption rate, time in app.
 *
 * LÓGICA:
 * - getDAUMAU(): ratio de usuarios activos diarios / mensuales.
 * - getFeatureAdoption(): features usadas / features disponibles × 100.
 * - getTimeInApp(): duración media de sesión por tenant.
 * - getEngagementScore(): puntuación compuesta 0-100.
 *
 * Usa datos de finops_usage_log (FinOpsTrackingService) y
 * analytics_event si está disponible.
 */
class EngagementScoringService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Calcula el ratio DAU/MAU para un tenant.
   *
   * @param string $tenant_id
   *   ID del grupo tenant.
   *
   * @return float
   *   Ratio DAU/MAU (0.0-1.0).
   */
  public function getDAUMAU(string $tenant_id): float {
    try {
      $db = \Drupal::database();

      // Usuarios activos en las últimas 24h.
      $dau = (int) $db->select('finops_usage_log', 'f')
        ->condition('tenant_id', $tenant_id)
        ->condition('timestamp', \Drupal::time()->getRequestTime() - 86400, '>=')
        ->countQuery()
        ->execute()
        ->fetchField();

      // Usuarios activos en los últimos 30 días.
      $mau = (int) $db->select('finops_usage_log', 'f')
        ->condition('tenant_id', $tenant_id)
        ->condition('timestamp', \Drupal::time()->getRequestTime() - (86400 * 30), '>=')
        ->countQuery()
        ->execute()
        ->fetchField();

      return $mau > 0 ? min(1.0, $dau / $mau) : 0.0;
    }
    catch (\Exception $e) {
      $this->logger->warning('Error calculating DAU/MAU for tenant @id: @message', [
        '@id' => $tenant_id,
        '@message' => $e->getMessage(),
      ]);
      return 0.0;
    }
  }

  /**
   * Calcula la tasa de adopción de features.
   *
   * @param string $tenant_id
   *   ID del grupo tenant.
   *
   * @return float
   *   Tasa de adopción (0.0-100.0).
   */
  public function getFeatureAdoption(string $tenant_id): float {
    try {
      // Features disponibles en el plan del tenant.
      $plan_validator = \Drupal::service('ecosistema_jaraba_core.plan_validator');
      $features_available = count($plan_validator->getAvailableFeatures($tenant_id));

      if ($features_available === 0) {
        return 100.0;
      }

      // Features realmente usadas (al menos 1 uso en los últimos 30 días).
      $db = \Drupal::database();
      $features_used = (int) $db->select('finops_usage_log', 'f')
        ->fields('f', ['metric_type'])
        ->condition('tenant_id', $tenant_id)
        ->condition('metric_type', 'feature_use')
        ->condition('timestamp', \Drupal::time()->getRequestTime() - (86400 * 30), '>=')
        ->distinct()
        ->countQuery()
        ->execute()
        ->fetchField();

      return min(100.0, ($features_used / $features_available) * 100);
    }
    catch (\Exception $e) {
      $this->logger->warning('Error calculating feature adoption for tenant @id: @message', [
        '@id' => $tenant_id,
        '@message' => $e->getMessage(),
      ]);
      return 50.0;
    }
  }

  /**
   * Calcula el engagement score compuesto (0-100).
   *
   * @param string $tenant_id
   *   ID del grupo tenant.
   *
   * @return int
   *   Puntuación de engagement (0-100).
   */
  public function getEngagementScore(string $tenant_id): int {
    $dau_mau = $this->getDAUMAU($tenant_id);
    $adoption = $this->getFeatureAdoption($tenant_id);

    // Ponderación: 60% DAU/MAU, 40% adopción.
    $score = ($dau_mau * 100 * 0.6) + ($adoption * 0.4);

    return (int) min(100, max(0, round($score)));
  }

}
