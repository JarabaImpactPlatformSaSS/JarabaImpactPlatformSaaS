<?php

declare(strict_types=1);

namespace Drupal\jaraba_predictive\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_billing\Service\TenantMeteringService;
use Psr\Log\LoggerInterface;

/**
 * Servicio de deteccion de anomalias en metricas de la plataforma.
 *
 * ESTRUCTURA:
 *   Detector estadistico que identifica valores atipicos en series
 *   temporales de metricas usando el metodo de desviacion estandar.
 *   Analiza datos historicos y marca como anomalias los valores que
 *   superan 2 desviaciones estandar de la media.
 *
 * LOGICA:
 *   1. Recopila puntos de datos del periodo de lookback.
 *   2. Calcula media (mu) y desviacion estandar (sigma).
 *   3. Umbral de anomalia = mu +/- 2*sigma.
 *   4. Marca como anomalia todo valor que supera el umbral.
 *   Los tipos de anomalia se clasifican en:
 *     - 'spike' (valor > mu + 2*sigma)
 *     - 'drop' (valor < mu - 2*sigma)
 *
 * RELACIONES:
 *   - Consume: entity_type.manager (Forecast storage para datos historicos).
 *   - Produce: Arrays de anomalias detectadas (no persistido como entidad).
 */
class AnomalyDetectorService {

  /**
   * Factor de desviacion estandar para umbral de anomalia.
   */
  protected const SIGMA_THRESHOLD = 2.5;

  /**
   * Construye el servicio de deteccion de anomalias.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad para acceso a almacenamiento.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del canal jaraba_predictive.
   * @param \Drupal\jaraba_billing\Service\TenantMeteringService $meteringService
   *   Servicio de medición de uso.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
    protected readonly TenantMeteringService $meteringService,
  ) {}

  /**
   * Detecta anomalias en una metrica durante un periodo de lookback.
   *
   * ESTRUCTURA:
   *   Metodo principal de deteccion que analiza datos historicos
   *   y retorna los puntos anomalos identificados.
   *
   * LOGICA:
   *   1. Carga Forecast entities con actual_value para la metrica.
   *   2. Calcula media y desviacion estandar.
   *   3. Identifica valores fuera de mu +/- 2*sigma.
   *   4. Clasifica cada anomalia como 'spike' o 'drop'.
   *
   * RELACIONES:
   *   - Lee: forecast entities (datos historicos con actual_value).
   *
   * @param string $metric
   *   Metrica a analizar: 'mrr', 'arr', 'revenue', 'users'.
   * @param int $lookbackDays
   *   Numero de dias hacia atras para el analisis (default: 30).
   *
   * @return array
   *   Array con claves:
   *   - 'anomalies': array de anomalias detectadas.
   *   - 'mean': float media de los datos.
   *   - 'std_dev': float desviacion estandar.
   *   - 'threshold': float umbral de deteccion.
   *   - 'data_points_analyzed': int cantidad de puntos analizados.
   */
  public function detectAnomalies(string $metric, int $lookbackDays = 30): array {
    $storage = $this->entityTypeManager->getStorage('forecast');
    $since = strtotime("-{$lookbackDays} days");

    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('forecast_type', $metric)
      ->condition('actual_value', 0, '>')
      ->condition('created', $since, '>=')
      ->sort('created', 'ASC')
      ->execute();

    if (empty($ids)) {
      $this->logger->info('No data points found for anomaly detection on @metric (last @days days).', [
        '@metric' => $metric,
        '@days' => $lookbackDays,
      ]);

      return [
        'anomalies' => [],
        'mean' => 0.0,
        'std_dev' => 0.0,
        'threshold' => 0.0,
        'data_points_analyzed' => 0,
      ];
    }

    $forecasts = $storage->loadMultiple($ids);

    // --- Recopilar valores ---
    $dataPoints = [];
    foreach ($forecasts as $forecast) {
      $value = (float) ($forecast->get('actual_value')->value ?? 0);
      if ($value > 0) {
        $dataPoints[] = [
          'id' => (int) $forecast->id(),
          'value' => $value,
          'date' => $forecast->get('calculated_at')->value ?? $forecast->get('created')->value ?? NULL,
          'forecast_type' => $forecast->get('forecast_type')->value ?? $metric,
        ];
      }
    }

    $values = array_column($dataPoints, 'value');
    $n = count($values);

    if ($n < 3) {
      return [
        'anomalies' => [],
        'mean' => $n > 0 ? array_sum($values) / $n : 0.0,
        'std_dev' => 0.0,
        'threshold' => 0.0,
        'data_points_analyzed' => $n,
      ];
    }

    // --- Calcular media y desviacion estandar ---
    $mean = array_sum($values) / $n;
    $variance = 0.0;
    foreach ($values as $value) {
      $variance += ($value - $mean) ** 2;
    }
    $stdDev = sqrt($variance / ($n - 1));

    $upperThreshold = $mean + (self::SIGMA_THRESHOLD * $stdDev);
    $lowerThreshold = $mean - (self::SIGMA_THRESHOLD * $stdDev);

    // --- Detectar anomalias ---
    $anomalies = [];
    foreach ($dataPoints as $point) {
      $isAnomaly = FALSE;
      $anomalyType = '';

      if ($point['value'] > $upperThreshold) {
        $isAnomaly = TRUE;
        $anomalyType = 'spike';
      }
      elseif ($point['value'] < $lowerThreshold) {
        $isAnomaly = TRUE;
        $anomalyType = 'drop';
      }

      if ($isAnomaly) {
        $deviation = ($point['value'] - $mean) / ($stdDev > 0 ? $stdDev : 1.0);
        $anomalies[] = [
          'forecast_id' => $point['id'],
          'value' => $point['value'],
          'date' => $point['date'],
          'type' => $anomalyType,
          'deviation_sigma' => round($deviation, 2),
          'expected_range' => [
            'low' => round($lowerThreshold, 2),
            'high' => round($upperThreshold, 2),
          ],
        ];
      }
    }

    if (!empty($anomalies)) {
      $this->logger->warning('Detected @count anomalies in @metric over last @days days.', [
        '@count' => count($anomalies),
        '@metric' => $metric,
        '@days' => $lookbackDays,
      ]);
    }

    return [
      'anomalies' => $anomalies,
      'mean' => round($mean, 2),
      'std_dev' => round($stdDev, 2),
      'threshold' => round(self::SIGMA_THRESHOLD * $stdDev, 2),
      'data_points_analyzed' => $n,
    ];
  }

  /**
   * Obtiene las anomalias detectadas mas recientes.
   *
   * ESTRUCTURA:
   *   Metodo de consulta que ejecuta deteccion en todas las metricas
   *   y devuelve las anomalias mas recientes.
   *
   * LOGICA:
   *   Itera sobre las metricas disponibles (mrr, arr, revenue, users),
   *   ejecuta deteccion en cada una y agrega las anomalias encontradas.
   *   Ordena por fecha descendente y limita resultados.
   *
   * RELACIONES:
   *   - Usa: self::detectAnomalies() para cada metrica.
   *
   * @param int $limit
   *   Numero maximo de anomalias a retornar (default: 10).
   *
   * @return array
   *   Array de anomalias detectadas con metadatos de metrica.
   */
  public function getRecentAnomalies(int $limit = 10): array {
    $metrics = ['mrr', 'arr', 'revenue', 'users'];
    $allAnomalies = [];

    foreach ($metrics as $metric) {
      $result = $this->detectAnomalies($metric, 30);

      foreach ($result['anomalies'] as $anomaly) {
        $anomaly['metric'] = $metric;
        $anomaly['detection_context'] = [
          'mean' => $result['mean'],
          'std_dev' => $result['std_dev'],
        ];
        $allAnomalies[] = $anomaly;
      }
    }

    // Ordenar por fecha descendente.
    usort($allAnomalies, function (array $a, array $b): int {
      return ($b['date'] ?? '') <=> ($a['date'] ?? '');
    });

    return array_slice($allAnomalies, 0, $limit);
  }

  /**
   * Detecta si hay un pico de consumo de tokens sospechoso.
   */
  public function detectAiUsageAnomaly(int $tenantId): array {
    // 1. Obtener historial de consumo diario de los últimos 15 días.
    $history = $this->meteringService->getHistoricalUsage((string) $tenantId, 15);
    
    $tokenSeries = [];
    foreach ($history as $period => $metrics) {
      $tokenSeries[] = $metrics[TenantMeteringService::METRIC_AI_TOKENS] ?? 0.0;
    }

    if (count($tokenSeries) < 5) {
      return ['is_anomaly' => FALSE];
    }

    // 2. Cálculo estadístico.
    $n = count($tokenSeries);
    $mean = array_sum($tokenSeries) / $n;
    $variance = 0.0;
    foreach ($tokenSeries as $v) {
      $variance += ($v - $mean) ** 2;
    }
    $stdDev = sqrt($variance / ($n - 1));

    // 3. Evaluar uso actual.
    $usageData = $this->meteringService->getUsage((string) $tenantId);
    $currentUsage = $usageData['metrics'][TenantMeteringService::METRIC_AI_TOKENS]['total'] ?? 0;
    
    $threshold = $mean + (self::SIGMA_THRESHOLD * $stdDev);

    if ($currentUsage > $threshold && $currentUsage > 5000) { // Ignorar ruidos pequeños.
      $this->logger->critical('ALERTA DE SEGURIDAD IA: Tenant @id consumiendo @cur (Media: @mean)', [
        '@id' => $tenantId,
        '@cur' => $currentUsage,
        '@mean' => $mean
      ]);
      
      return [
        'is_anomaly' => TRUE,
        'type' => 'ai_token_spike',
        'severity' => 'critical',
        'current_value' => $currentUsage,
        'expected_max' => $threshold,
      ];
    }

    return ['is_anomaly' => FALSE];
  }

}
