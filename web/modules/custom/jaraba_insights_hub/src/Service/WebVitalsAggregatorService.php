<?php

declare(strict_types=1);

namespace Drupal\jaraba_insights_hub\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Servicio de agregacion de metricas Core Web Vitals.
 *
 * Agrega las metricas individuales recolectadas por WebVitalsCollectorService
 * en valores diarios p75 (percentil 75) por tenant, pagina y metrica.
 * Esto permite consultas rapidas en el dashboard sin procesar millones
 * de registros individuales.
 *
 * ARQUITECTURA:
 * - Usa consultas SQL directas al base_table para calcular percentiles.
 * - Los resultados agregados se cachean por dia/tenant/metrica.
 * - Los umbrales de rating siguen los oficiales de Google CWV.
 */
class WebVitalsAggregatorService {

  /**
   * Umbrales de rendimiento por metrica.
   *
   * Formato: [umbral_bueno, umbral_pobre].
   * - Valor <= umbral_bueno: 'good'
   * - Valor <= umbral_pobre: 'needs-improvement'
   * - Valor > umbral_pobre: 'poor'
   */
  protected const THRESHOLDS = [
    'LCP' => [2500, 4000],
    'INP' => [200, 500],
    'CLS' => [0.1, 0.25],
    'FCP' => [1800, 3000],
    'TTFB' => [800, 1800],
  ];

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad.
   * @param \Drupal\Core\Database\Connection $database
   *   Conexion a la base de datos.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Connection $database,
  ) {}

  /**
   * Agrega metricas individuales del dia anterior en valores p75 diarios.
   *
   * Procesa todos los registros de web_vitals_metric del dia anterior,
   * calcula el percentil 75 por tenant/pagina/metrica y almacena
   * los resultados para consulta rapida.
   *
   * @return int
   *   Numero de agregaciones generadas.
   */
  public function aggregateDaily(): int {
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $dayStart = strtotime($yesterday . ' 00:00:00');
    $dayEnd = strtotime($yesterday . ' 23:59:59');

    // Obtener combinaciones unicas de tenant/pagina/metrica del dia anterior.
    $query = $this->database->select('web_vitals_metric', 'wvm')
      ->fields('wvm', ['tenant_id', 'page_url', 'metric_name'])
      ->condition('created', $dayStart, '>=')
      ->condition('created', $dayEnd, '<=')
      ->groupBy('wvm.tenant_id')
      ->groupBy('wvm.page_url')
      ->groupBy('wvm.metric_name');

    $groups = $query->execute()->fetchAll();
    $count = 0;

    foreach ($groups as $group) {
      $p75 = $this->calculateP75(
        (int) $group->tenant_id,
        $group->page_url,
        $group->metric_name,
        $dayStart,
        $dayEnd
      );

      if ($p75 !== NULL) {
        // Almacenar resultado agregado usando State API con clave compuesta.
        $key = sprintf(
          'jaraba_insights_hub.agg.%d.%s.%s.%s',
          $group->tenant_id,
          md5($group->page_url),
          $group->metric_name,
          $yesterday
        );

        \Drupal::state()->set($key, [
          'tenant_id' => (int) $group->tenant_id,
          'page_url' => $group->page_url,
          'metric_name' => $group->metric_name,
          'date' => $yesterday,
          'p75_value' => $p75,
          'rating' => $this->getRating($group->metric_name, $p75),
        ]);

        $count++;
      }
    }

    return $count;
  }

  /**
   * Obtiene metricas agregadas para un tenant y rango de fechas.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param string $dateFrom
   *   Fecha inicio en formato YYYY-MM-DD.
   * @param string $dateTo
   *   Fecha fin en formato YYYY-MM-DD.
   *
   * @return array
   *   Array de metricas agregadas con claves: metric_name, date, p75_value, rating, page_url.
   */
  public function getAggregatedMetrics(int $tenantId, string $dateFrom, string $dateTo): array {
    $results = [];
    $startTs = strtotime($dateFrom . ' 00:00:00');
    $endTs = strtotime($dateTo . ' 23:59:59');

    // Consultar las metricas en el rango usando la tabla directa.
    $query = $this->database->select('web_vitals_metric', 'wvm')
      ->fields('wvm', ['tenant_id', 'page_url', 'metric_name', 'metric_value', 'metric_rating', 'created'])
      ->condition('tenant_id', $tenantId)
      ->condition('created', $startTs, '>=')
      ->condition('created', $endTs, '<=')
      ->orderBy('created', 'DESC');

    $rows = $query->execute()->fetchAll();

    // Agrupar por fecha/metrica/pagina y calcular p75.
    $grouped = [];
    foreach ($rows as $row) {
      $date = date('Y-m-d', (int) $row->created);
      $groupKey = $date . '|' . $row->metric_name . '|' . $row->page_url;

      if (!isset($grouped[$groupKey])) {
        $grouped[$groupKey] = [
          'date' => $date,
          'metric_name' => $row->metric_name,
          'page_url' => $row->page_url,
          'values' => [],
        ];
      }

      $grouped[$groupKey]['values'][] = (float) $row->metric_value;
    }

    foreach ($grouped as $group) {
      $p75 = $this->computePercentile($group['values'], 75);
      $results[] = [
        'date' => $group['date'],
        'metric_name' => $group['metric_name'],
        'page_url' => $group['page_url'],
        'p75_value' => $p75,
        'rating' => $this->getRating($group['metric_name'], $p75),
        'sample_count' => count($group['values']),
      ];
    }

    return $results;
  }

  /**
   * Obtiene el valor p75 para una metrica especifica de un tenant.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param string $metricName
   *   Nombre de la metrica (LCP, INP, CLS, FCP, TTFB).
   * @param int $days
   *   Numero de dias a considerar.
   *
   * @return array
   *   Array con claves: p75_value, rating, sample_count, trend.
   */
  public function getP75ByMetric(int $tenantId, string $metricName, int $days = 28): array {
    $startTs = strtotime("-{$days} days");

    $query = $this->database->select('web_vitals_metric', 'wvm')
      ->fields('wvm', ['metric_value'])
      ->condition('tenant_id', $tenantId)
      ->condition('metric_name', $metricName)
      ->condition('created', $startTs, '>=')
      ->orderBy('metric_value', 'ASC');

    $values = $query->execute()->fetchCol();

    if (empty($values)) {
      return [
        'p75_value' => 0,
        'rating' => 'good',
        'sample_count' => 0,
        'trend' => 'stable',
      ];
    }

    $values = array_map('floatval', $values);
    $p75 = $this->computePercentile($values, 75);

    // Calcular tendencia: comparar p75 primera mitad vs segunda mitad.
    $midpoint = (int) floor(count($values) / 2);
    $firstHalf = array_slice($values, 0, $midpoint);
    $secondHalf = array_slice($values, $midpoint);

    $trend = 'stable';
    if (!empty($firstHalf) && !empty($secondHalf)) {
      $p75First = $this->computePercentile($firstHalf, 75);
      $p75Second = $this->computePercentile($secondHalf, 75);

      $changePercent = $p75First > 0 ? (($p75Second - $p75First) / $p75First) * 100 : 0;

      if ($changePercent < -5) {
        $trend = 'improving';
      }
      elseif ($changePercent > 5) {
        $trend = 'regressing';
      }
    }

    return [
      'p75_value' => round($p75, 3),
      'rating' => $this->getRating($metricName, $p75),
      'sample_count' => count($values),
      'trend' => $trend,
    ];
  }

  /**
   * Determina el rating de una metrica segun los umbrales de Google CWV.
   *
   * @param string $metricName
   *   Nombre de la metrica (LCP, INP, CLS, FCP, TTFB).
   * @param float $value
   *   Valor de la metrica (p75 o individual).
   *
   * @return string
   *   Rating: 'good', 'needs-improvement' o 'poor'.
   */
  public function getRating(string $metricName, float $value): string {
    $thresholds = self::THRESHOLDS[$metricName] ?? [2500, 4000];

    if ($value <= $thresholds[0]) {
      return 'good';
    }

    if ($value <= $thresholds[1]) {
      return 'needs-improvement';
    }

    return 'poor';
  }

  /**
   * Calcula el percentil 75 de los valores de una metrica para una agrupacion.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param string $pageUrl
   *   URL de la pagina.
   * @param string $metricName
   *   Nombre de la metrica.
   * @param int $startTs
   *   Timestamp de inicio.
   * @param int $endTs
   *   Timestamp de fin.
   *
   * @return float|null
   *   Valor p75 o NULL si no hay datos.
   */
  protected function calculateP75(int $tenantId, string $pageUrl, string $metricName, int $startTs, int $endTs): ?float {
    $query = $this->database->select('web_vitals_metric', 'wvm')
      ->fields('wvm', ['metric_value'])
      ->condition('tenant_id', $tenantId)
      ->condition('page_url', $pageUrl)
      ->condition('metric_name', $metricName)
      ->condition('created', $startTs, '>=')
      ->condition('created', $endTs, '<=')
      ->orderBy('metric_value', 'ASC');

    $values = $query->execute()->fetchCol();

    if (empty($values)) {
      return NULL;
    }

    $values = array_map('floatval', $values);
    return $this->computePercentile($values, 75);
  }

  /**
   * Calcula el percentil dado de un array de valores ordenados.
   *
   * @param array $values
   *   Array de valores numericos (puede estar desordenado).
   * @param int $percentile
   *   Percentil a calcular (0-100).
   *
   * @return float
   *   Valor del percentil.
   */
  protected function computePercentile(array $values, int $percentile): float {
    if (empty($values)) {
      return 0.0;
    }

    sort($values);
    $count = count($values);
    $index = ($percentile / 100) * ($count - 1);
    $lower = (int) floor($index);
    $upper = (int) ceil($index);
    $fraction = $index - $lower;

    if ($lower === $upper || $upper >= $count) {
      return (float) $values[$lower];
    }

    return (float) ($values[$lower] + ($values[$upper] - $values[$lower]) * $fraction);
  }

}
