<?php

declare(strict_types=1);

namespace Drupal\jaraba_insights_hub\Service;

/**
 * Servicio agregador central del Insights Hub.
 *
 * Combina datos de todos los subsistemas del Insights Hub
 * (Search Console, Web Vitals, Error Tracking, Uptime) en una
 * vista unificada para el dashboard. Opcionalmente integra datos
 * de jaraba_analytics y jaraba_site_builder si estan disponibles.
 *
 * ARQUITECTURA:
 * - Orquesta los 4 servicios internos del Insights Hub.
 * - Integra servicios opcionales de otros modulos (@? prefijo).
 * - Calcula un Health Score global de 0-100.
 * - Multi-tenant: todos los datos se filtran por tenant_id.
 */
class InsightsAggregatorService {

  /**
   * Peso de cada dimension en el Health Score.
   */
  protected const SCORE_WEIGHTS = [
    'performance' => 0.30,
    'seo' => 0.25,
    'errors' => 0.25,
    'uptime' => 0.20,
  ];

  /**
   * Constructor.
   *
   * @param \Drupal\jaraba_insights_hub\Service\SearchConsoleService $searchConsole
   *   Servicio de Search Console.
   * @param \Drupal\jaraba_insights_hub\Service\WebVitalsAggregatorService $webVitalsAggregator
   *   Servicio de agregacion de Web Vitals.
   * @param \Drupal\jaraba_insights_hub\Service\ErrorTrackingService $errorTracking
   *   Servicio de seguimiento de errores.
   * @param \Drupal\jaraba_insights_hub\Service\UptimeMonitorService $uptimeMonitor
   *   Servicio de monitorizacion de uptime.
   * @param mixed $analyticsService
   *   Servicio de analytics (opcional, puede ser NULL).
   * @param mixed $seoManager
   *   Servicio de SEO manager (opcional, puede ser NULL).
   */
  public function __construct(
    protected SearchConsoleService $searchConsole,
    protected WebVitalsAggregatorService $webVitalsAggregator,
    protected ErrorTrackingService $errorTracking,
    protected UptimeMonitorService $uptimeMonitor,
    protected mixed $analyticsService = NULL,
    protected mixed $seoManager = NULL,
  ) {}

  /**
   * Obtiene un resumen unificado de todas las metricas para el dashboard.
   *
   * Combina datos de SEO (Search Console), rendimiento (Web Vitals),
   * errores y uptime en una estructura unica para el frontend.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param string $dateRange
   *   Rango de fechas: '7d', '14d', '28d', '90d'.
   *
   * @return array
   *   Array con claves: seo, performance, errors, uptime, health_score.
   */
  public function getSummary(int $tenantId, string $dateRange = '7d'): array {
    $days = $this->parseDateRange($dateRange);
    $dateFrom = date('Y-m-d', strtotime("-{$days} days"));
    $dateTo = date('Y-m-d');

    return [
      'seo' => $this->getSeoSummary($tenantId, $dateFrom, $dateTo),
      'performance' => $this->getPerformanceSummary($tenantId, $days),
      'errors' => $this->getErrorsSummary($tenantId),
      'uptime' => $this->getUptimeSummary($tenantId, $days),
      'health_score' => $this->getHealthScore($tenantId),
      'date_range' => $dateRange,
      'date_from' => $dateFrom,
      'date_to' => $dateTo,
    ];
  }

  /**
   * Calcula el Health Score global del tenant (0-100).
   *
   * El score se calcula como media ponderada de 4 dimensiones:
   * - Performance (30%): Basado en ratings de Core Web Vitals p75.
   * - SEO (25%): Basado en CTR y posicion media de Search Console.
   * - Errors (25%): Basado en cantidad de errores abiertos.
   * - Uptime (20%): Basado en porcentaje de disponibilidad.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return int
   *   Score de 0 a 100.
   */
  public function getHealthScore(int $tenantId): int {
    $scores = [
      'performance' => $this->calculatePerformanceScore($tenantId),
      'seo' => $this->calculateSeoScore($tenantId),
      'errors' => $this->calculateErrorsScore($tenantId),
      'uptime' => $this->calculateUptimeScore($tenantId),
    ];

    $weightedSum = 0.0;
    foreach (self::SCORE_WEIGHTS as $dimension => $weight) {
      $weightedSum += $scores[$dimension] * $weight;
    }

    return (int) round($weightedSum);
  }

  /**
   * Obtiene resumen de datos SEO.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param string $dateFrom
   *   Fecha inicio.
   * @param string $dateTo
   *   Fecha fin.
   *
   * @return array
   *   Resumen SEO con top_queries, top_pages y totales.
   */
  protected function getSeoSummary(int $tenantId, string $dateFrom, string $dateTo): array {
    $data = $this->searchConsole->getDataForTenant($tenantId, $dateFrom, $dateTo);
    $topQueries = $this->searchConsole->getTopQueries($tenantId, 5);
    $topPages = $this->searchConsole->getTopPages($tenantId, 5);

    $totalClicks = 0;
    $totalImpressions = 0;

    foreach ($data as $row) {
      $totalClicks += $row['clicks'];
      $totalImpressions += $row['impressions'];
    }

    $summary = [
      'total_clicks' => $totalClicks,
      'total_impressions' => $totalImpressions,
      'avg_ctr' => $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0,
      'top_queries' => $topQueries,
      'top_pages' => $topPages,
      'data_points' => count($data),
    ];

    // Enriquecer con datos de SEO manager si esta disponible.
    if ($this->seoManager !== NULL && method_exists($this->seoManager, 'getAuditSummary')) {
      try {
        $summary['audit'] = $this->seoManager->getAuditSummary($tenantId);
      }
      catch (\Exception $e) {
        $summary['audit'] = NULL;
      }
    }

    return $summary;
  }

  /**
   * Obtiene resumen de rendimiento (Web Vitals).
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param int $days
   *   Numero de dias.
   *
   * @return array
   *   Resumen con p75 de cada metrica Core Web Vital.
   */
  protected function getPerformanceSummary(int $tenantId, int $days): array {
    $metrics = ['LCP', 'INP', 'CLS', 'FCP', 'TTFB'];
    $summary = [];

    foreach ($metrics as $metric) {
      $summary[$metric] = $this->webVitalsAggregator->getP75ByMetric($tenantId, $metric, $days);
    }

    // Calcular resumen general de ratings.
    $ratingCounts = ['good' => 0, 'needs-improvement' => 0, 'poor' => 0];
    foreach ($summary as $metricData) {
      if ($metricData['sample_count'] > 0) {
        $ratingCounts[$metricData['rating']]++;
      }
    }

    $summary['overall'] = [
      'rating_counts' => $ratingCounts,
      'all_good' => $ratingCounts['poor'] === 0 && $ratingCounts['needs-improvement'] === 0,
    ];

    return $summary;
  }

  /**
   * Obtiene resumen de errores.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return array
   *   Resumen con estadisticas y errores recientes.
   */
  protected function getErrorsSummary(int $tenantId): array {
    $stats = $this->errorTracking->getErrorStats($tenantId);
    $recentErrors = $this->errorTracking->getErrorsForTenant($tenantId, 'open', 5);

    return [
      'stats' => $stats,
      'recent' => $recentErrors,
    ];
  }

  /**
   * Obtiene resumen de uptime.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param int $days
   *   Numero de dias.
   *
   * @return array
   *   Resumen con porcentaje de uptime, checks recientes e incidentes.
   */
  protected function getUptimeSummary(int $tenantId, int $days): array {
    return [
      'uptime_percentage' => $this->uptimeMonitor->calculateUptime($tenantId, $days),
      'recent_checks' => $this->uptimeMonitor->getChecksForTenant($tenantId, 5),
      'active_incidents' => $this->uptimeMonitor->getActiveIncidents($tenantId),
    ];
  }

  /**
   * Calcula score de rendimiento (0-100).
   *
   * Basado en los ratings p75 de las 3 Core Web Vitals principales
   * (LCP, INP, CLS). Cada metrica contribuye: good=100, needs-improvement=50, poor=0.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return float
   *   Score de 0 a 100.
   */
  protected function calculatePerformanceScore(int $tenantId): float {
    $coreMetrics = ['LCP', 'INP', 'CLS'];
    $totalScore = 0.0;
    $metricsWithData = 0;

    foreach ($coreMetrics as $metric) {
      $data = $this->webVitalsAggregator->getP75ByMetric($tenantId, $metric);
      if ($data['sample_count'] > 0) {
        $totalScore += $this->ratingToScore($data['rating']);
        $metricsWithData++;
      }
    }

    // Si no hay datos, asumir score neutral.
    if ($metricsWithData === 0) {
      return 75.0;
    }

    return $totalScore / $metricsWithData;
  }

  /**
   * Calcula score de SEO (0-100).
   *
   * Basado en CTR medio y posicion media de Search Console.
   * CTR > 5% = 100, CTR > 2% = 75, CTR > 1% = 50, CTR < 1% = 25.
   * Posicion < 5 = 100, < 10 = 75, < 20 = 50, >= 20 = 25.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return float
   *   Score de 0 a 100.
   */
  protected function calculateSeoScore(int $tenantId): float {
    $topQueries = $this->searchConsole->getTopQueries($tenantId, 50);

    if (empty($topQueries)) {
      return 75.0;
    }

    $totalCtr = 0.0;
    $totalPosition = 0.0;
    $count = count($topQueries);

    foreach ($topQueries as $query) {
      $totalCtr += $query['avg_ctr'];
      $totalPosition += $query['avg_position'];
    }

    $avgCtr = $totalCtr / $count;
    $avgPosition = $totalPosition / $count;

    // Score de CTR.
    if ($avgCtr > 0.05) {
      $ctrScore = 100;
    }
    elseif ($avgCtr > 0.02) {
      $ctrScore = 75;
    }
    elseif ($avgCtr > 0.01) {
      $ctrScore = 50;
    }
    else {
      $ctrScore = 25;
    }

    // Score de posicion.
    if ($avgPosition < 5) {
      $positionScore = 100;
    }
    elseif ($avgPosition < 10) {
      $positionScore = 75;
    }
    elseif ($avgPosition < 20) {
      $positionScore = 50;
    }
    else {
      $positionScore = 25;
    }

    return ($ctrScore + $positionScore) / 2;
  }

  /**
   * Calcula score de errores (0-100).
   *
   * 0 errores abiertos = 100, 1-5 = 80, 6-20 = 50, 21-50 = 25, 50+ = 0.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return float
   *   Score de 0 a 100.
   */
  protected function calculateErrorsScore(int $tenantId): float {
    $stats = $this->errorTracking->getErrorStats($tenantId);
    $openErrors = $stats['total_open'];

    if ($openErrors === 0) {
      return 100.0;
    }
    if ($openErrors <= 5) {
      return 80.0;
    }
    if ($openErrors <= 20) {
      return 50.0;
    }
    if ($openErrors <= 50) {
      return 25.0;
    }

    return 0.0;
  }

  /**
   * Calcula score de uptime (0-100).
   *
   * Directamente el porcentaje de uptime.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return float
   *   Score de 0 a 100.
   */
  protected function calculateUptimeScore(int $tenantId): float {
    return $this->uptimeMonitor->calculateUptime($tenantId, 30);
  }

  /**
   * Convierte un rating de Web Vitals a score numerico.
   *
   * @param string $rating
   *   Rating: 'good', 'needs-improvement' o 'poor'.
   *
   * @return float
   *   Score: 100, 50 o 0.
   */
  protected function ratingToScore(string $rating): float {
    return match ($rating) {
      'good' => 100.0,
      'needs-improvement' => 50.0,
      'poor' => 0.0,
      default => 50.0,
    };
  }

  /**
   * Parsea un string de rango de fechas a numero de dias.
   *
   * @param string $dateRange
   *   Rango en formato: '7d', '14d', '28d', '90d'.
   *
   * @return int
   *   Numero de dias.
   */
  protected function parseDateRange(string $dateRange): int {
    $days = (int) filter_var($dateRange, FILTER_SANITIZE_NUMBER_INT);
    return max(1, min($days, 365));
  }

}
