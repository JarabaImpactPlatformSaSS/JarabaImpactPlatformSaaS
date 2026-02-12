<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de predicción de demanda basado en IA para productores.
 *
 * Analiza datos históricos de pedidos para generar forecasts de demanda,
 * detectar tendencias y calcular elasticidad de precio.
 * Referencia: Doc 67 §4.4 — Producer Copilot (Demand Forecaster).
 */
class DemandForecasterService {

  /**
   * Pesos estacionales para productos agrícolas en España (por mes).
   *
   * Enero-Diciembre. Navidad (diciembre) y verano (junio-agosto) son picos.
   */
  private const SEASONAL_WEIGHTS = [
    1 => 0.80,  // Enero
    2 => 0.85,  // Febrero
    3 => 0.95,  // Marzo
    4 => 1.00,  // Abril
    5 => 1.10,  // Mayo
    6 => 1.15,  // Junio
    7 => 1.20,  // Julio
    8 => 1.10,  // Agosto
    9 => 1.00,  // Septiembre
    10 => 0.95, // Octubre
    11 => 0.90, // Noviembre
    12 => 1.30, // Diciembre (Navidad)
  ];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Connection $database,
    protected LoggerInterface $logger,
  ) {
  }

  /**
   * Genera predicción de demanda para un producto.
   *
   * @param int $productId
   *   Product ID.
   * @param int $daysAhead
   *   Days to forecast (default 30).
   * @param string|null $tenantId
   *   Tenant filter.
   *
   * @return array
   *   Array con keys: daily_forecast, trend, seasonality_factor, peak_date,
   *   summary. Cada elemento de daily_forecast contiene: date,
   *   predicted_units, confidence.
   */
  public function forecast(int $productId, int $daysAhead = 30, ?string $tenantId = NULL): array {
    // 1. Verificar que el producto existe.
    $product = $this->entityTypeManager->getStorage('product_agro')->load($productId);
    if (!$product) {
      $this->logger->warning('Forecast solicitado para producto inexistente: @id', ['@id' => $productId]);
      return [
        'daily_forecast' => [],
        'trend' => 'unknown',
        'seasonality_factor' => 1.0,
        'peak_date' => NULL,
        'summary' => 'Producto no encontrado.',
      ];
    }

    // 2. Obtener ventas históricas (últimos 90 días).
    $historicalSales = $this->getHistoricalSales($productId, 90, $tenantId);

    // 3. Calcular regresión lineal sobre las ventas diarias.
    $regression = $this->calculateLinearRegression($historicalSales);
    $dailyAverage = count($historicalSales) > 0
      ? array_sum(array_column($historicalSales, 'units')) / count($historicalSales)
      : 0;

    // 4. Determinar confianza basada en cantidad de datos.
    $dataPoints = count($historicalSales);
    $confidence = match (TRUE) {
      $dataPoints >= 60 => 0.85,
      $dataPoints >= 30 => 0.70,
      $dataPoints >= 14 => 0.50,
      default => 0.30,
    };

    // 5. Determinar tendencia.
    $trend = 'stable';
    if ($regression['slope'] > 0.05) {
      $trend = 'increasing';
    }
    elseif ($regression['slope'] < -0.05) {
      $trend = 'decreasing';
    }

    // 6. Generar forecast diario.
    $dailyForecast = [];
    $peakUnits = 0;
    $peakDate = NULL;
    $today = new \DateTime();

    for ($i = 1; $i <= $daysAhead; $i++) {
      $forecastDate = (clone $today)->modify("+{$i} days");
      $dateStr = $forecastDate->format('Y-m-d');
      $month = (int) $forecastDate->format('n');

      // Predicción = (media + tendencia * día) * factor estacional.
      $seasonalIndex = $this->getSeasonalIndex($month);
      $predictedUnits = max(0, ($dailyAverage + $regression['slope'] * ($dataPoints + $i)) * $seasonalIndex);
      $predictedUnits = round($predictedUnits, 1);

      $dailyForecast[] = [
        'date' => $dateStr,
        'predicted_units' => $predictedUnits,
        'confidence' => $confidence,
      ];

      if ($predictedUnits > $peakUnits) {
        $peakUnits = $predictedUnits;
        $peakDate = $dateStr;
      }
    }

    // 7. Factor de estacionalidad actual.
    $currentMonth = (int) $today->format('n');
    $seasonalityFactor = $this->getSeasonalIndex($currentMonth);

    // 8. Resumen textual.
    $productName = $product->label();
    $summary = sprintf(
      'Producto "%s": tendencia %s con media de %.1f uds/día. Pico previsto el %s (%.1f uds). Confianza: %d%%.',
      $productName,
      $trend,
      $dailyAverage,
      $peakDate ?? 'N/A',
      $peakUnits,
      (int) ($confidence * 100)
    );

    $this->logger->info('Forecast generado para producto @id: @trend, @avg uds/día', [
      '@id' => $productId,
      '@trend' => $trend,
      '@avg' => round($dailyAverage, 1),
    ]);

    return [
      'daily_forecast' => $dailyForecast,
      'trend' => $trend,
      'seasonality_factor' => $seasonalityFactor,
      'peak_date' => $peakDate,
      'summary' => $summary,
    ];
  }

  /**
   * Obtiene tendencias de demanda para todos los productos de un productor.
   *
   * @param int $producerId
   *   Producer profile ID.
   * @param int $months
   *   Months of history (default 6).
   *
   * @return array
   *   Array de arrays con keys: product_id, product_name, avg_daily_sales,
   *   trend, month_over_month_change.
   */
  public function getDemandTrends(int $producerId, int $months = 6): array {
    // 1. Cargar productos del productor.
    $productStorage = $this->entityTypeManager->getStorage('product_agro');
    $productIds = $productStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('producer_id', $producerId)
      ->condition('status', 1)
      ->execute();

    if (empty($productIds)) {
      return [];
    }

    $products = $productStorage->loadMultiple($productIds);
    $totalDays = $months * 30;
    $trends = [];

    foreach ($products as $product) {
      $productId = (int) $product->id();
      $historicalSales = $this->getHistoricalSales($productId, $totalDays);

      if (empty($historicalSales)) {
        $trends[] = [
          'product_id' => $productId,
          'product_name' => $product->label(),
          'avg_daily_sales' => 0.0,
          'trend' => 'no_data',
          'month_over_month_change' => 0.0,
        ];
        continue;
      }

      // Calcular media diaria.
      $totalUnits = array_sum(array_column($historicalSales, 'units'));
      $avgDailySales = $totalUnits / max(count($historicalSales), 1);

      // Calcular tendencia con regresión.
      $regression = $this->calculateLinearRegression($historicalSales);
      $trend = 'stable';
      if ($regression['slope'] > 0.05) {
        $trend = 'increasing';
      }
      elseif ($regression['slope'] < -0.05) {
        $trend = 'decreasing';
      }

      // Calcular month-over-month change.
      $recentSales = $this->getHistoricalSales($productId, 30);
      $previousSales = $this->getHistoricalSalesRange($productId, 60, 30);
      $recentTotal = array_sum(array_column($recentSales, 'units'));
      $previousTotal = array_sum(array_column($previousSales, 'units'));
      $momChange = $previousTotal > 0
        ? round(($recentTotal - $previousTotal) / $previousTotal * 100, 1)
        : 0.0;

      $trends[] = [
        'product_id' => $productId,
        'product_name' => $product->label(),
        'avg_daily_sales' => round($avgDailySales, 2),
        'trend' => $trend,
        'month_over_month_change' => $momChange,
      ];
    }

    // Ordenar por importancia de tendencia (increasing primero, luego decreasing).
    usort($trends, function (array $a, array $b): int {
      $priority = ['increasing' => 1, 'decreasing' => 2, 'stable' => 3, 'no_data' => 4];
      $pA = $priority[$a['trend']] ?? 5;
      $pB = $priority[$b['trend']] ?? 5;
      if ($pA !== $pB) {
        return $pA <=> $pB;
      }
      return abs($b['month_over_month_change']) <=> abs($a['month_over_month_change']);
    });

    return $trends;
  }

  /**
   * Calcula la elasticidad de precio para un producto.
   *
   * @param int $productId
   *   Product ID.
   *
   * @return array
   *   Array con keys: elasticity_coefficient, interpretation,
   *   recommended_price_range (con min y max).
   */
  public function getPriceElasticity(int $productId): array {
    $product = $this->entityTypeManager->getStorage('product_agro')->load($productId);
    if (!$product) {
      return [
        'elasticity_coefficient' => 0.0,
        'interpretation' => 'unknown',
        'recommended_price_range' => ['min' => 0, 'max' => 0],
      ];
    }

    $currentPrice = (float) ($product->get('price')->value ?? 0);

    // Consultar pedidos históricos con distintos precios.
    $orderItemStorage = $this->entityTypeManager->getStorage('order_item_agro');
    $itemIds = $orderItemStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('product_id', $productId)
      ->sort('created', 'ASC')
      ->execute();

    if (empty($itemIds)) {
      return [
        'elasticity_coefficient' => 0.0,
        'interpretation' => 'insufficient_data',
        'recommended_price_range' => [
          'min' => round($currentPrice * 0.9, 2),
          'max' => round($currentPrice * 1.1, 2),
        ],
      ];
    }

    $items = $orderItemStorage->loadMultiple($itemIds);

    // Agrupar por precio para calcular elasticidad.
    $priceQuantityMap = [];
    foreach ($items as $item) {
      $price = (float) ($item->get('unit_price')->value ?? 0);
      $qty = (int) ($item->get('quantity')->value ?? 0);
      $priceBucket = round($price, 2);

      if (!isset($priceQuantityMap[$priceBucket])) {
        $priceQuantityMap[$priceBucket] = 0;
      }
      $priceQuantityMap[$priceBucket] += $qty;
    }

    // Necesitamos al menos 2 puntos de precio para calcular elasticidad.
    if (count($priceQuantityMap) < 2) {
      return [
        'elasticity_coefficient' => 0.0,
        'interpretation' => 'single_price_point',
        'recommended_price_range' => [
          'min' => round($currentPrice * 0.9, 2),
          'max' => round($currentPrice * 1.1, 2),
        ],
      ];
    }

    // Calcular elasticidad: % cambio en cantidad / % cambio en precio.
    ksort($priceQuantityMap);
    $prices = array_keys($priceQuantityMap);
    $quantities = array_values($priceQuantityMap);

    $elasticities = [];
    for ($i = 1, $count = count($prices); $i < $count; $i++) {
      $pctPriceChange = ($prices[$i] - $prices[$i - 1]) / $prices[$i - 1];
      $pctQtyChange = ($quantities[$i] - $quantities[$i - 1]) / max($quantities[$i - 1], 1);

      if (abs($pctPriceChange) > 0.01) {
        $elasticities[] = $pctQtyChange / $pctPriceChange;
      }
    }

    $avgElasticity = count($elasticities) > 0
      ? array_sum($elasticities) / count($elasticities)
      : 0.0;

    // Interpretar elasticidad.
    $absElasticity = abs($avgElasticity);
    $interpretation = match (TRUE) {
      $absElasticity > 1.0 => 'elastic',
      $absElasticity >= 0.8 && $absElasticity <= 1.2 => 'unit_elastic',
      default => 'inelastic',
    };

    // Recomendar rango de precios.
    $minPrice = $currentPrice;
    $maxPrice = $currentPrice;
    if ($interpretation === 'elastic') {
      // Demanda sensible: mantener precio o bajar ligeramente.
      $minPrice = round($currentPrice * 0.85, 2);
      $maxPrice = round($currentPrice * 1.05, 2);
    }
    elseif ($interpretation === 'inelastic') {
      // Demanda poco sensible: margen para subir.
      $minPrice = round($currentPrice * 0.95, 2);
      $maxPrice = round($currentPrice * 1.20, 2);
    }
    else {
      $minPrice = round($currentPrice * 0.90, 2);
      $maxPrice = round($currentPrice * 1.10, 2);
    }

    $this->logger->info('Elasticidad calculada para producto @id: @coef (@interpretation)', [
      '@id' => $productId,
      '@coef' => round($avgElasticity, 3),
      '@interpretation' => $interpretation,
    ]);

    return [
      'elasticity_coefficient' => round($avgElasticity, 3),
      'interpretation' => $interpretation,
      'recommended_price_range' => [
        'min' => $minPrice,
        'max' => $maxPrice,
      ],
    ];
  }

  /**
   * Obtiene ventas históricas de un producto en los últimos N días.
   *
   * @param int $productId
   *   Product ID.
   * @param int $days
   *   Número de días de historial.
   * @param string|null $tenantId
   *   Filtro de tenant opcional.
   *
   * @return array
   *   Array de arrays con keys: date, units (agrupados por día).
   */
  private function getHistoricalSales(int $productId, int $days, ?string $tenantId = NULL): array {
    $startTimestamp = strtotime("-{$days} days midnight");

    $orderItemStorage = $this->entityTypeManager->getStorage('order_item_agro');
    $query = $orderItemStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('product_id', $productId)
      ->condition('created', $startTimestamp, '>=')
      ->sort('created', 'ASC');

    if ($tenantId !== NULL) {
      $query->condition('tenant_id', $tenantId);
    }

    $itemIds = $query->execute();
    if (empty($itemIds)) {
      return [];
    }

    $items = $orderItemStorage->loadMultiple($itemIds);

    // Agrupar por día.
    $dailySales = [];
    foreach ($items as $item) {
      $created = (int) ($item->get('created')->value ?? 0);
      $date = date('Y-m-d', $created);
      $qty = (int) ($item->get('quantity')->value ?? 0);

      if (!isset($dailySales[$date])) {
        $dailySales[$date] = 0;
      }
      $dailySales[$date] += $qty;
    }

    // Convertir a array indexado para regresión.
    $result = [];
    foreach ($dailySales as $date => $units) {
      $result[] = [
        'date' => $date,
        'units' => $units,
      ];
    }

    return $result;
  }

  /**
   * Obtiene ventas históricas en un rango específico (entre daysAgo y daysEnd).
   *
   * @param int $productId
   *   Product ID.
   * @param int $daysAgo
   *   Inicio del rango (días atrás desde hoy).
   * @param int $daysEnd
   *   Fin del rango (días atrás desde hoy).
   *
   * @return array
   *   Array de arrays con keys: date, units.
   */
  private function getHistoricalSalesRange(int $productId, int $daysAgo, int $daysEnd): array {
    $startTimestamp = strtotime("-{$daysAgo} days midnight");
    $endTimestamp = strtotime("-{$daysEnd} days midnight");

    $orderItemStorage = $this->entityTypeManager->getStorage('order_item_agro');
    $itemIds = $orderItemStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('product_id', $productId)
      ->condition('created', $startTimestamp, '>=')
      ->condition('created', $endTimestamp, '<')
      ->sort('created', 'ASC')
      ->execute();

    if (empty($itemIds)) {
      return [];
    }

    $items = $orderItemStorage->loadMultiple($itemIds);
    $dailySales = [];

    foreach ($items as $item) {
      $created = (int) ($item->get('created')->value ?? 0);
      $date = date('Y-m-d', $created);
      $qty = (int) ($item->get('quantity')->value ?? 0);

      if (!isset($dailySales[$date])) {
        $dailySales[$date] = 0;
      }
      $dailySales[$date] += $qty;
    }

    $result = [];
    foreach ($dailySales as $date => $units) {
      $result[] = [
        'date' => $date,
        'units' => $units,
      ];
    }

    return $result;
  }

  /**
   * Calcula regresión lineal simple sobre una serie de datos diarios.
   *
   * @param array $dataPoints
   *   Array de arrays con key 'units'.
   *
   * @return array
   *   Array con keys: slope, intercept, r_squared.
   */
  private function calculateLinearRegression(array $dataPoints): array {
    $n = count($dataPoints);
    if ($n < 2) {
      return ['slope' => 0.0, 'intercept' => 0.0, 'r_squared' => 0.0];
    }

    $sumX = 0.0;
    $sumY = 0.0;
    $sumXY = 0.0;
    $sumX2 = 0.0;
    $sumY2 = 0.0;

    foreach ($dataPoints as $i => $point) {
      $x = (float) $i;
      $y = (float) ($point['units'] ?? 0);
      $sumX += $x;
      $sumY += $y;
      $sumXY += $x * $y;
      $sumX2 += $x * $x;
      $sumY2 += $y * $y;
    }

    $denominator = ($n * $sumX2 - $sumX * $sumX);
    if (abs($denominator) < 0.0001) {
      return ['slope' => 0.0, 'intercept' => $sumY / $n, 'r_squared' => 0.0];
    }

    $slope = ($n * $sumXY - $sumX * $sumY) / $denominator;
    $intercept = ($sumY - $slope * $sumX) / $n;

    // Coeficiente de determinación R².
    $ssTot = $sumY2 - ($sumY * $sumY) / $n;
    $ssRes = 0.0;
    $meanY = $sumY / $n;
    foreach ($dataPoints as $i => $point) {
      $predicted = $intercept + $slope * $i;
      $actual = (float) ($point['units'] ?? 0);
      $ssRes += ($actual - $predicted) ** 2;
    }
    $rSquared = $ssTot > 0 ? 1 - ($ssRes / $ssTot) : 0.0;

    return [
      'slope' => round($slope, 4),
      'intercept' => round($intercept, 4),
      'r_squared' => round(max(0, $rSquared), 4),
    ];
  }

  /**
   * Obtiene el índice estacional para un mes dado.
   *
   * @param int $month
   *   Número de mes (1-12).
   *
   * @return float
   *   Peso estacional para productos agrícolas en España.
   */
  private function getSeasonalIndex(int $month): float {
    return self::SEASONAL_WEIGHTS[$month] ?? 1.0;
  }

}
