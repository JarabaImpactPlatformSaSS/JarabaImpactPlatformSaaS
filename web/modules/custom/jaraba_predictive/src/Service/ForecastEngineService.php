<?php

declare(strict_types=1);

namespace Drupal\jaraba_predictive\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de prevision financiera y de metricas de negocio.
 *
 * ESTRUCTURA:
 *   Motor de forecasting que genera previsiones para metricas clave
 *   (MRR, ARR, tenant_count, active_users) usando regresion lineal
 *   simple sobre datos historicos. Persiste cada prevision como
 *   entidad Forecast (append-only).
 *
 * LOGICA:
 *   Implementa regresion lineal simple (y = mx + b) sobre los ultimos
 *   N puntos de datos historicos. El intervalo de confianza se calcula
 *   como predicted_value +/- (confidence_interval * std_dev).
 *   Requiere un minimo de data points configurables (min_data_points).
 *
 * RELACIONES:
 *   - Consume: entity_type.manager (Forecast storage).
 *   - Consume: config.factory (jaraba_predictive.settings → forecast.*).
 *   - Produce: Forecast entities (append-only).
 */
class ForecastEngineService {

  /**
   * Construye el servicio de forecasting.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad para acceso a almacenamiento.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del canal jaraba_predictive.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Fabrica de configuracion para acceder a jaraba_predictive.settings.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly Connection $database,
    protected readonly LoggerInterface $logger,
    protected readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Genera una prevision para una metrica y periodo dados.
   *
   * ESTRUCTURA:
   *   Metodo principal que recopila datos historicos, aplica regresion
   *   lineal simple y crea la entidad Forecast.
   *
   * LOGICA:
   *   1. Valida la metrica (mrr, arr, revenue, users).
   *   2. Carga datos historicos (Forecast entities anteriores con actual_value).
   *   3. Si hay suficientes puntos, aplica regresion lineal simple.
   *   4. Calcula intervalo de confianza (lower_bound, upper_bound).
   *   5. Persiste Forecast entity (append-only).
   *   6. Si no hay suficientes puntos, genera estimacion heuristica.
   *
   * RELACIONES:
   *   - Lee: forecast entities (datos historicos).
   *   - Crea: forecast entity (nueva prevision).
   *
   * @param string $metric
   *   Metrica a predecir: 'mrr', 'arr', 'revenue', 'users'.
   * @param string $period
   *   Periodo temporal: 'monthly', 'quarterly', 'yearly'.
   *
   * @return array
   *   Array con clave 'forecast' (Forecast entity).
   *
   * @throws \InvalidArgumentException
   *   Si la metrica o el periodo son invalidos.
   */
  public function generateForecast(string $metric, string $period = 'monthly'): array {
    $validMetrics = ['mrr', 'arr', 'revenue', 'users'];
    $validPeriods = ['monthly', 'quarterly', 'yearly'];

    if (!in_array($metric, $validMetrics, TRUE)) {
      throw new \InvalidArgumentException("Metrica invalida: {$metric}. Valores validos: " . implode(', ', $validMetrics));
    }

    if (!in_array($period, $validPeriods, TRUE)) {
      throw new \InvalidArgumentException("Periodo invalido: {$period}. Valores validos: " . implode(', ', $validPeriods));
    }

    $config = $this->configFactory->get('jaraba_predictive.settings');
    $forecastConfig = $config->get('forecast') ?? [];
    $confidenceInterval = (float) ($forecastConfig['confidence_interval'] ?? 0.8);
    $minDataPoints = (int) ($forecastConfig['min_data_points'] ?? 12);
    $modelVersion = $config->get('model_version') ?? 'heuristic_v1';

    // --- Recopilar datos historicos ---
    $historicalData = $this->getHistoricalDataPoints($metric);
    $dataPointsCount = count($historicalData);

    if ($dataPointsCount >= $minDataPoints) {
      // --- Regresion lineal simple ---
      $regression = $this->linearRegression($historicalData);
      $nextX = $dataPointsCount;
      $predictedValue = $regression['slope'] * $nextX + $regression['intercept'];
      $stdDev = $regression['std_dev'];
      $lowerBound = $predictedValue - ($confidenceInterval * $stdDev);
      $upperBound = $predictedValue + ($confidenceInterval * $stdDev);
      $accuracyConfidence = min(0.90, 0.50 + ($dataPointsCount * 0.02));
    }
    else {
      // --- Estimacion heuristica cuando no hay suficientes datos ---
      $predictedValue = $this->heuristicEstimate($metric);
      $stdDev = $predictedValue * 0.15;
      $lowerBound = $predictedValue - $stdDev;
      $upperBound = $predictedValue + $stdDev;
      $accuracyConfidence = 0.35;
    }

    $predictedValue = max(0, round($predictedValue, 2));
    $lowerBound = max(0, round($lowerBound, 2));
    $upperBound = round($upperBound, 2);

    // --- Persistir forecast (append-only) ---
    $forecastStorage = $this->entityTypeManager->getStorage('forecast');
    $forecast = $forecastStorage->create([
      'forecast_type' => $metric,
      'period' => $period,
      'predicted_value' => $predictedValue,
      'confidence_low' => $lowerBound,
      'confidence_high' => $upperBound,
      'model_version' => $modelVersion,
      'calculated_at' => date('Y-m-d\TH:i:s'),
      'forecast_date' => $this->calculateForecastDate($period),
    ]);
    $forecast->save();

    $this->logger->info('Forecast generated for @metric (@period): predicted=@value [low=@low, high=@high]', [
      '@metric' => $metric,
      '@period' => $period,
      '@value' => $predictedValue,
      '@low' => $lowerBound,
      '@high' => $upperBound,
    ]);

    return [
      'forecast' => $forecast,
    ];
  }

  /**
   * Obtiene el historial de forecasts para una metrica.
   *
   * ESTRUCTURA:
   *   Metodo de consulta de forecasts historicos.
   *
   * LOGICA:
   *   Carga Forecast entities filtradas por forecast_type, ordenadas
   *   por created DESC con limite configurable.
   *
   * RELACIONES:
   *   - Lee: forecast entities.
   *
   * @param string $metric
   *   Metrica a consultar: 'mrr', 'arr', 'revenue', 'users'.
   * @param int $limit
   *   Numero maximo de resultados (default: 12).
   *
   * @return array
   *   Array de arrays con datos de Forecast serializados.
   */
  public function getForecastHistory(string $metric, int $limit = 12): array {
    $storage = $this->entityTypeManager->getStorage('forecast');

    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('forecast_type', $metric)
      ->sort('created', 'DESC')
      ->range(0, $limit)
      ->execute();

    if (empty($ids)) {
      return [];
    }

    $forecasts = $storage->loadMultiple($ids);
    $results = [];

    foreach ($forecasts as $forecast) {
      $results[] = $this->serializeForecast($forecast);
    }

    return $results;
  }

  /**
   * Compara valores predichos vs reales para una metrica.
   *
   * ESTRUCTURA:
   *   Metodo de validacion del modelo que compara previsiones con valores
   *   reales observados.
   *
   * LOGICA:
   *   Carga Forecast entities con actual_value no nulo. Calcula MAPE
   *   (Mean Absolute Percentage Error) y devuelve comparacion detallada.
   *
   * RELACIONES:
   *   - Lee: forecast entities.
   *
   * @param string $metric
   *   Metrica a comparar.
   *
   * @return array
   *   Array con 'comparisons' (detalle), 'mape' (error medio),
   *   'total_forecasts', 'forecasts_with_actuals'.
   */
  public function compareActualVsPredicted(string $metric): array {
    $storage = $this->entityTypeManager->getStorage('forecast');

    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('forecast_type', $metric)
      ->condition('actual_value', 0, '>')
      ->sort('created', 'DESC')
      ->execute();

    $comparisons = [];
    $totalError = 0.0;

    if (!empty($ids)) {
      $forecasts = $storage->loadMultiple($ids);

      foreach ($forecasts as $forecast) {
        $predicted = (float) ($forecast->get('predicted_value')->value ?? 0);
        $actual = (float) ($forecast->get('actual_value')->value ?? 0);
        $error = $actual > 0 ? abs($predicted - $actual) / $actual * 100 : 0;
        $totalError += $error;

        $comparisons[] = [
          'id' => (int) $forecast->id(),
          'forecast_date' => $forecast->get('forecast_date')->value ?? NULL,
          'predicted_value' => $predicted,
          'actual_value' => $actual,
          'error_percentage' => round($error, 2),
          'within_confidence' => $actual >= (float) ($forecast->get('confidence_low')->value ?? 0)
            && $actual <= (float) ($forecast->get('confidence_high')->value ?? 0),
        ];
      }
    }

    $forecastsWithActuals = count($comparisons);
    $mape = $forecastsWithActuals > 0 ? round($totalError / $forecastsWithActuals, 2) : 0.0;

    // Total forecasts count for context.
    $totalForecasts = (int) $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('forecast_type', $metric)
      ->count()
      ->execute();

    return [
      'comparisons' => $comparisons,
      'mape' => $mape,
      'total_forecasts' => $totalForecasts,
      'forecasts_with_actuals' => $forecastsWithActuals,
    ];
  }

  /**
   * Predice la demanda de un producto específico para el próximo periodo.
   *
   * Útil para productores de AgroConecta en la planificación de cosechas.
   */
  public function predictProductDemand(int $productId, int $tenantId): array {
    if (!$this->database->schema()->tableExists('agro_order_item')) {
      return ['success' => FALSE, 'message' => 'Módulo AgroConecta no disponible.'];
    }

    $query = $this->database->select('agro_order_item', 'i')
      ->fields('i', ['quantity']);
    $query->join('agro_order', 'o', 'o.id = i.order_id');
    $query->fields('o', ['created'])
      ->condition('i.product_id', $productId)
      ->condition('o.tenant_id', $tenantId)
      ->sort('o.created', 'ASC');

    $results = $query->execute()->fetchAll();
    
    if (count($results) < 6) {
      return ['success' => FALSE, 'message' => 'Insuficientes datos históricos (mínimo 6 meses).'];
    }

    $monthlyData = [];
    foreach ($results as $row) {
      $month = date('Y-m', (int) $row->created);
      $monthlyData[$month] = ($monthlyData[$month] ?? 0) + (float) $row->quantity;
    }

    $regression = $this->linearRegression(array_values($monthlyData));
    $predictedQuantity = $regression['slope'] * count($monthlyData) + $regression['intercept'];

    return [
      'success' => TRUE,
      'product_id' => $productId,
      'predicted_quantity' => max(0, round($predictedQuantity, 2)),
      'confidence' => round($regression['r_squared'] * 100, 2),
      'trend' => $regression['slope'] > 0 ? 'increasing' : 'decreasing',
    ];
  }

  /**
   * Recopila puntos de datos históricos REALES de transacciones.
   *
   * ESTRUCTURA: Metodo interno de recopilacion de datos.
   * LOGICA: Consulta la tabla financial_transaction para obtener la serie temporal.
   */
  protected function getHistoricalDataPoints(string $metric, int $tenantId = NULL): array {
    if (!$this->database->schema()->tableExists('financial_transaction')) {
      return [];
    }

    $query = $this->database->select('financial_transaction', 't')
      ->fields('t', ['amount', 'created'])
      ->condition('t.type', 'credit')
      ->sort('t.created', 'ASC');

    if ($tenantId) {
      $query->condition('t.tenant_id', $tenantId);
    }

    $results = $query->execute()->fetchAll();
    
    if (empty($results)) {
      return [];
    }

    // Agrupamos por mes para generar los data points.
    $monthlyData = [];
    foreach ($results as $row) {
      $month = date('Y-m', (int) $row->created);
      if (!isset($monthlyData[$month])) {
        $monthlyData[$month] = 0.0;
      }
      $monthlyData[$month] += (float) $row->amount;
    }

    return array_values($monthlyData);
  }

  /**
   * Aplica regresion lineal simple a un conjunto de datos.
   *
   * ESTRUCTURA: Metodo interno de calculo estadistico.
   * LOGICA: Calcula slope (m) e intercept (b) para y = mx + b.
   *   Usa metodo de minimos cuadrados. Calcula tambien desviacion estandar.
   *
   * @param array $values
   *   Array de floats con datos historicos.
   *
   * @return array
   *   Array con 'slope', 'intercept', 'std_dev', 'r_squared'.
   */
  protected function linearRegression(array $values): array {
    $n = count($values);

    if ($n < 2) {
      return [
        'slope' => 0.0,
        'intercept' => $values[0] ?? 0.0,
        'std_dev' => 0.0,
        'r_squared' => 0.0,
      ];
    }

    $sumX = 0.0;
    $sumY = 0.0;
    $sumXY = 0.0;
    $sumX2 = 0.0;
    $sumY2 = 0.0;

    for ($i = 0; $i < $n; $i++) {
      $x = (float) $i;
      $y = $values[$i];
      $sumX += $x;
      $sumY += $y;
      $sumXY += $x * $y;
      $sumX2 += $x * $x;
      $sumY2 += $y * $y;
    }

    $denominator = ($n * $sumX2) - ($sumX * $sumX);
    if (abs($denominator) < PHP_FLOAT_EPSILON) {
      return [
        'slope' => 0.0,
        'intercept' => $sumY / $n,
        'std_dev' => 0.0,
        'r_squared' => 0.0,
      ];
    }

    $slope = (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
    $intercept = ($sumY - ($slope * $sumX)) / $n;

    // Desviacion estandar de los residuos.
    $sumResiduals2 = 0.0;
    for ($i = 0; $i < $n; $i++) {
      $predicted = $slope * $i + $intercept;
      $residual = $values[$i] - $predicted;
      $sumResiduals2 += $residual * $residual;
    }
    $stdDev = $n > 2 ? sqrt($sumResiduals2 / ($n - 2)) : 0.0;

    // R-squared.
    $meanY = $sumY / $n;
    $ssTotal = 0.0;
    $ssResidual = 0.0;
    for ($i = 0; $i < $n; $i++) {
      $ssTotal += ($values[$i] - $meanY) ** 2;
      $predicted = $slope * $i + $intercept;
      $ssResidual += ($values[$i] - $predicted) ** 2;
    }
    $rSquared = $ssTotal > 0 ? 1 - ($ssResidual / $ssTotal) : 0.0;

    return [
      'slope' => $slope,
      'intercept' => $intercept,
      'std_dev' => $stdDev,
      'r_squared' => $rSquared,
    ];
  }

  /**
   * Genera una estimacion heuristica cuando no hay datos suficientes.
   *
   * ESTRUCTURA: Metodo interno de fallback heuristico.
   * LOGICA: Retorna valores base razonables segun la metrica.
   *
   * @param string $metric
   *   Metrica a estimar.
   *
   * @return float
   *   Valor estimado.
   */
  protected function heuristicEstimate(string $metric): float {
    return match ($metric) {
      'mrr' => 5000.00,
      'arr' => 60000.00,
      'revenue' => 5500.00,
      'users' => 150.0,
      default => 0.0,
    };
  }

  /**
   * Calcula la fecha objetivo del forecast segun el periodo.
   *
   * ESTRUCTURA: Metodo interno de calculo de fecha.
   * LOGICA: Avanza la fecha actual al siguiente periodo.
   *
   * @param string $period
   *   Periodo: monthly, quarterly, yearly.
   *
   * @return string
   *   Fecha en formato Y-m-d\TH:i:s.
   */
  protected function calculateForecastDate(string $period): string {
    $date = new \DateTime();

    return match ($period) {
      'quarterly' => $date->modify('+3 months')->format('Y-m-d\TH:i:s'),
      'yearly' => $date->modify('+1 year')->format('Y-m-d\TH:i:s'),
      default => $date->modify('+1 month')->format('Y-m-d\TH:i:s'),
    };
  }

  /**
   * Serializa una entidad Forecast para respuesta JSON.
   *
   * ESTRUCTURA: Metodo interno de serializacion.
   * LOGICA: Extrae campos relevantes con tipos correctos.
   *
   * @param object $forecast
   *   Entidad Forecast.
   *
   * @return array
   *   Array asociativo con datos serializados.
   */
  protected function serializeForecast(object $forecast): array {
    return [
      'id' => (int) $forecast->id(),
      'forecast_type' => $forecast->get('forecast_type')->value ?? '',
      'period' => $forecast->get('period')->value ?? '',
      'forecast_date' => $forecast->get('forecast_date')->value ?? NULL,
      'predicted_value' => (float) ($forecast->get('predicted_value')->value ?? 0),
      'confidence_low' => (float) ($forecast->get('confidence_low')->value ?? 0),
      'confidence_high' => (float) ($forecast->get('confidence_high')->value ?? 0),
      'actual_value' => (float) ($forecast->get('actual_value')->value ?? 0),
      'model_version' => $forecast->get('model_version')->value ?? '',
      'calculated_at' => $forecast->get('calculated_at')->value ?? NULL,
      'created' => $forecast->get('created')->value ?? NULL,
    ];
  }

}
