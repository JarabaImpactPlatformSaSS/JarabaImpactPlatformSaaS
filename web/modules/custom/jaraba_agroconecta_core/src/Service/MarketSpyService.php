<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de inteligencia de mercado competitiva para productores.
 *
 * Analiza precios, tendencias del marketplace, posicionamiento competitivo
 * y genera alertas de precios fuera de rango.
 * Referencia: Doc 67 §4.4 — Producer Copilot (Market Intelligence).
 */
class MarketSpyService {

  /**
   * Buckets de distribución de precios (en euros).
   */
  private const PRICE_BUCKETS = [
    ['min' => 0, 'max' => 5, 'label' => '0-5€'],
    ['min' => 5, 'max' => 10, 'label' => '5-10€'],
    ['min' => 10, 'max' => 20, 'label' => '10-20€'],
    ['min' => 20, 'max' => 50, 'label' => '20-50€'],
    ['min' => 50, 'max' => PHP_FLOAT_MAX, 'label' => '50+€'],
  ];

  /**
   * Umbral de desviación para alertas de precio (porcentaje).
   */
  private const PRICE_DEVIATION_THRESHOLD = 20.0;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Connection $database,
    protected LoggerInterface $logger,
  ) {
  }

  /**
   * Obtiene análisis de precios de competidores para una categoría.
   *
   * @param string $category
   *   Category name.
   * @param int|null $excludeProducerId
   *   Exclude this producer's products.
   *
   * @return array
   *   Array con keys: avg_price, median_price, min_price, max_price,
   *   price_distribution (array de {range, count}), sample_size.
   */
  public function getCategoryPriceAnalysis(string $category, ?int $excludeProducerId = NULL): array {
    $productStorage = $this->entityTypeManager->getStorage('product_agro');

    $query = $productStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('category', $category)
      ->condition('status', 1);

    if ($excludeProducerId !== NULL) {
      $query->condition('producer_id', $excludeProducerId, '<>');
    }

    $productIds = $query->execute();

    if (empty($productIds)) {
      return [
        'avg_price' => 0.0,
        'median_price' => 0.0,
        'min_price' => 0.0,
        'max_price' => 0.0,
        'price_distribution' => [],
        'sample_size' => 0,
      ];
    }

    $products = $productStorage->loadMultiple($productIds);
    $prices = [];

    foreach ($products as $product) {
      $price = (float) ($product->get('price')->value ?? 0);
      if ($price > 0) {
        $prices[] = $price;
      }
    }

    if (empty($prices)) {
      return [
        'avg_price' => 0.0,
        'median_price' => 0.0,
        'min_price' => 0.0,
        'max_price' => 0.0,
        'price_distribution' => [],
        'sample_size' => 0,
      ];
    }

    sort($prices);
    $avgPrice = array_sum($prices) / count($prices);
    $medianPrice = $this->getMedian($prices);
    $minPrice = min($prices);
    $maxPrice = max($prices);

    // Distribución por buckets de precio.
    $distribution = [];
    foreach (self::PRICE_BUCKETS as $bucket) {
      $count = 0;
      foreach ($prices as $price) {
        if ($price >= $bucket['min'] && $price < $bucket['max']) {
          $count++;
        }
      }
      $distribution[] = [
        'range' => $bucket['label'],
        'count' => $count,
      ];
    }

    $this->logger->info('Análisis de precios para categoría "@cat": @n productos, media @avg€', [
      '@cat' => $category,
      '@n' => count($prices),
      '@avg' => round($avgPrice, 2),
    ]);

    return [
      'avg_price' => round($avgPrice, 2),
      'median_price' => round($medianPrice, 2),
      'min_price' => round($minPrice, 2),
      'max_price' => round($maxPrice, 2),
      'price_distribution' => $distribution,
      'sample_size' => count($prices),
    ];
  }

  /**
   * Identifica productos tendencia en el marketplace.
   *
   * @param int $limit
   *   Number of trending products.
   * @param string|null $tenantId
   *   Tenant filter.
   *
   * @return array
   *   Array de arrays con keys: product_id, product_name, producer_name,
   *   sales_velocity, review_avg, trend_score.
   */
  public function getTrendingProducts(int $limit = 10, ?string $tenantId = NULL): array {
    // 1. Obtener pedidos recientes (últimos 30 días) agrupados por producto.
    $startTimestamp = strtotime('-30 days midnight');
    $orderItemStorage = $this->entityTypeManager->getStorage('order_item_agro');

    $query = $orderItemStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('created', $startTimestamp, '>=');

    if ($tenantId !== NULL) {
      $query->condition('tenant_id', $tenantId);
    }

    $itemIds = $query->execute();

    if (empty($itemIds)) {
      return [];
    }

    $items = $orderItemStorage->loadMultiple($itemIds);

    // Agrupar ventas por producto.
    $productSales = [];
    foreach ($items as $item) {
      $productId = (int) ($item->get('product_id')->target_id ?? 0);
      if ($productId <= 0) {
        continue;
      }

      if (!isset($productSales[$productId])) {
        $productSales[$productId] = [
          'total_units' => 0,
          'order_count' => 0,
          'last_order_timestamp' => 0,
        ];
      }

      $productSales[$productId]['total_units'] += (int) ($item->get('quantity')->value ?? 0);
      $productSales[$productId]['order_count']++;
      $created = (int) ($item->get('created')->value ?? 0);
      if ($created > $productSales[$productId]['last_order_timestamp']) {
        $productSales[$productId]['last_order_timestamp'] = $created;
      }
    }

    // 2. Calcular métricas y trend_score para cada producto.
    $productStorage = $this->entityTypeManager->getStorage('product_agro');
    $trending = [];
    $now = time();

    foreach ($productSales as $productId => $sales) {
      $product = $productStorage->load($productId);
      if (!$product) {
        continue;
      }

      // Sales velocity: pedidos por día.
      $salesVelocity = $this->getSalesVelocity($productId, 30, $sales['order_count']);

      // Review average.
      $reviewAvg = $this->getAverageReviewScore($productId);

      // Recency factor: más reciente = más puntos (0.0-1.0).
      $daysSinceLastOrder = max(1, ($now - $sales['last_order_timestamp']) / 86400);
      $recencyFactor = max(0, min(1.0, 1.0 - ($daysSinceLastOrder / 30)));

      // Trend score compuesto.
      $trendScore = ($salesVelocity * 0.6) + ($reviewAvg * 0.2) + ($recencyFactor * 0.2);

      // Obtener nombre del productor.
      $producerName = 'Productor';
      $producerId = $product->get('producer_id')->target_id ?? NULL;
      if ($producerId) {
        $producer = $this->entityTypeManager->getStorage('producer_profile')->load($producerId);
        if ($producer) {
          $producerName = $producer->label();
        }
      }

      $trending[] = [
        'product_id' => $productId,
        'product_name' => $product->label(),
        'producer_name' => $producerName,
        'sales_velocity' => round($salesVelocity, 2),
        'review_avg' => round($reviewAvg, 1),
        'trend_score' => round($trendScore, 3),
      ];
    }

    // 3. Ordenar por trend_score DESC.
    usort($trending, fn(array $a, array $b): int => $b['trend_score'] <=> $a['trend_score']);

    return array_slice($trending, 0, $limit);
  }

  /**
   * Analiza la posición competitiva de un productor.
   *
   * @param int $producerId
   *   Producer profile ID.
   *
   * @return array
   *   Array con keys: market_share, price_position, quality_score,
   *   strengths, weaknesses, recommendations.
   */
  public function getCompetitivePosition(int $producerId): array {
    $productStorage = $this->entityTypeManager->getStorage('product_agro');

    // 1. Contar productos del productor vs total del marketplace.
    $producerProductIds = $productStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('producer_id', $producerId)
      ->condition('status', 1)
      ->execute();

    $totalMarketProducts = (int) $productStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 1)
      ->count()
      ->execute();

    $producerProductCount = count($producerProductIds);
    $marketShare = $totalMarketProducts > 0
      ? round(($producerProductCount / $totalMarketProducts) * 100, 2)
      : 0.0;

    // 2. Comparar precio medio del productor vs mercado.
    $producerPrices = [];
    if (!empty($producerProductIds)) {
      $producerProducts = $productStorage->loadMultiple($producerProductIds);
      foreach ($producerProducts as $product) {
        $price = (float) ($product->get('price')->value ?? 0);
        if ($price > 0) {
          $producerPrices[] = $price;
        }
      }
    }

    // Precio medio del mercado (excluyendo este productor).
    $allProductIds = $productStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 1)
      ->condition('producer_id', $producerId, '<>')
      ->execute();

    $marketPrices = [];
    if (!empty($allProductIds)) {
      $allProducts = $productStorage->loadMultiple($allProductIds);
      foreach ($allProducts as $product) {
        $price = (float) ($product->get('price')->value ?? 0);
        if ($price > 0) {
          $marketPrices[] = $price;
        }
      }
    }

    $producerAvgPrice = count($producerPrices) > 0
      ? array_sum($producerPrices) / count($producerPrices)
      : 0.0;
    $marketAvgPrice = count($marketPrices) > 0
      ? array_sum($marketPrices) / count($marketPrices)
      : 0.0;

    // Determinar posición de precio.
    $pricePosition = 'competitive';
    if ($marketAvgPrice > 0) {
      $priceRatio = $producerAvgPrice / $marketAvgPrice;
      if ($priceRatio > 1.15) {
        $pricePosition = 'premium';
      }
      elseif ($priceRatio < 0.85) {
        $pricePosition = 'budget';
      }
    }

    // 3. Calcular quality_score basado en reviews.
    $qualityScores = [];
    foreach ($producerProductIds as $productId) {
      $reviewScore = $this->getAverageReviewScore((int) $productId);
      if ($reviewScore > 0) {
        $qualityScores[] = $reviewScore;
      }
    }
    $qualityScore = count($qualityScores) > 0
      ? round(array_sum($qualityScores) / count($qualityScores), 1)
      : 0.0;

    // 4. Generar fortalezas, debilidades y recomendaciones.
    $strengths = [];
    $weaknesses = [];
    $recommendations = [];

    // Análisis de market share.
    if ($marketShare > 10) {
      $strengths[] = 'Cuota de mercado destacada (' . $marketShare . '%)';
    }
    elseif ($marketShare < 2 && $totalMarketProducts > 10) {
      $weaknesses[] = 'Cuota de mercado reducida (' . $marketShare . '%)';
      $recommendations[] = 'Considerar ampliar catálogo de productos para ganar visibilidad.';
    }

    // Análisis de precio.
    if ($pricePosition === 'premium') {
      if ($qualityScore >= 4.0) {
        $strengths[] = 'Posicionamiento premium respaldado por alta calidad (' . $qualityScore . '/5)';
      }
      else {
        $weaknesses[] = 'Precios premium sin calidad que lo justifique';
        $recommendations[] = 'Mejorar calidad del producto o ajustar precios al mercado.';
      }
    }
    elseif ($pricePosition === 'budget') {
      $weaknesses[] = 'Precios significativamente por debajo del mercado';
      $recommendations[] = 'Evaluar si los precios bajos son sostenibles. Considerar subir precios gradualmente.';
    }
    else {
      $strengths[] = 'Precios competitivos alineados con el mercado';
    }

    // Análisis de calidad.
    if ($qualityScore >= 4.5) {
      $strengths[] = 'Excelente valoración de clientes (' . $qualityScore . '/5)';
    }
    elseif ($qualityScore > 0 && $qualityScore < 3.5) {
      $weaknesses[] = 'Valoraciones de clientes por debajo de la media (' . $qualityScore . '/5)';
      $recommendations[] = 'Priorizar mejora en calidad del producto y atención al cliente.';
    }

    // Análisis de diversificación.
    if ($producerProductCount <= 2) {
      $weaknesses[] = 'Catálogo limitado (' . $producerProductCount . ' productos)';
      $recommendations[] = 'Diversificar oferta para atraer más compradores y reducir riesgo.';
    }
    elseif ($producerProductCount >= 10) {
      $strengths[] = 'Catálogo amplio y diversificado (' . $producerProductCount . ' productos)';
    }

    // Recomendación general si no hay otras.
    if (empty($recommendations)) {
      $recommendations[] = 'Mantener la estrategia actual y monitorizar cambios del mercado.';
    }

    $this->logger->info('Análisis competitivo para productor @id: share @share%, posición @pos', [
      '@id' => $producerId,
      '@share' => $marketShare,
      '@pos' => $pricePosition,
    ]);

    return [
      'market_share' => $marketShare,
      'price_position' => $pricePosition,
      'quality_score' => $qualityScore,
      'strengths' => $strengths,
      'weaknesses' => $weaknesses,
      'recommendations' => $recommendations,
    ];
  }

  /**
   * Obtiene alertas de precios fuera de rango para un productor.
   *
   * @param int $producerId
   *   Producer ID.
   *
   * @return array
   *   Array de arrays con keys: product_id, product_name, current_price,
   *   market_avg, deviation_pct, recommendation.
   */
  public function getPriceAlerts(int $producerId): array {
    $productStorage = $this->entityTypeManager->getStorage('product_agro');

    // 1. Cargar productos del productor.
    $producerProductIds = $productStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('producer_id', $producerId)
      ->condition('status', 1)
      ->execute();

    if (empty($producerProductIds)) {
      return [];
    }

    $producerProducts = $productStorage->loadMultiple($producerProductIds);
    $alerts = [];

    foreach ($producerProducts as $product) {
      $currentPrice = (float) ($product->get('price')->value ?? 0);
      if ($currentPrice <= 0) {
        continue;
      }

      $category = $product->get('category')->value ?? '';
      if (empty($category)) {
        continue;
      }

      // 2. Obtener media de la categoría excluyendo este productor.
      $categoryProductIds = $productStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('category', $category)
        ->condition('status', 1)
        ->condition('producer_id', $producerId, '<>')
        ->execute();

      if (empty($categoryProductIds)) {
        continue;
      }

      $categoryProducts = $productStorage->loadMultiple($categoryProductIds);
      $categoryPrices = [];

      foreach ($categoryProducts as $catProduct) {
        $price = (float) ($catProduct->get('price')->value ?? 0);
        if ($price > 0) {
          $categoryPrices[] = $price;
        }
      }

      if (empty($categoryPrices)) {
        continue;
      }

      $marketAvg = array_sum($categoryPrices) / count($categoryPrices);

      // 3. Calcular desviación.
      $deviationPct = $marketAvg > 0
        ? round((($currentPrice - $marketAvg) / $marketAvg) * 100, 1)
        : 0.0;

      // 4. Generar alerta si desviación supera el umbral.
      if (abs($deviationPct) > self::PRICE_DEVIATION_THRESHOLD) {
        $recommendation = '';
        if ($deviationPct > 0) {
          $recommendation = sprintf(
            'Precio %.1f%% por encima de la media (%.2f€). Considerar ajustar a la baja o justificar el premium con diferenciación.',
            $deviationPct,
            $marketAvg
          );
        }
        else {
          $recommendation = sprintf(
            'Precio %.1f%% por debajo de la media (%.2f€). Puede estar infravalorando el producto. Considerar incremento gradual.',
            abs($deviationPct),
            $marketAvg
          );
        }

        $alerts[] = [
          'product_id' => (int) $product->id(),
          'product_name' => $product->label(),
          'current_price' => round($currentPrice, 2),
          'market_avg' => round($marketAvg, 2),
          'deviation_pct' => $deviationPct,
          'recommendation' => $recommendation,
        ];
      }
    }

    if (!empty($alerts)) {
      $this->logger->info('Generadas @n alertas de precio para productor @id', [
        '@n' => count($alerts),
        '@id' => $producerId,
      ]);
    }

    return $alerts;
  }

  /**
   * Calcula la mediana de un array de valores numéricos.
   *
   * @param array $values
   *   Array de valores numéricos (debe estar ordenado).
   *
   * @return float
   *   Valor mediana.
   */
  private function getMedian(array $values): float {
    $count = count($values);
    if ($count === 0) {
      return 0.0;
    }

    sort($values);
    $middle = (int) floor($count / 2);

    if ($count % 2 === 0) {
      return ($values[$middle - 1] + $values[$middle]) / 2;
    }

    return (float) $values[$middle];
  }

  /**
   * Calcula la velocidad de ventas de un producto (pedidos/día).
   *
   * @param int $productId
   *   Product ID.
   * @param int $days
   *   Período en días.
   * @param int $orderCount
   *   Número de pedidos ya contados (opcional para evitar re-consulta).
   *
   * @return float
   *   Pedidos por día.
   */
  private function getSalesVelocity(int $productId, int $days, int $orderCount = 0): float {
    if ($orderCount > 0) {
      return $orderCount / max($days, 1);
    }

    $startTimestamp = strtotime("-{$days} days midnight");
    $orderItemStorage = $this->entityTypeManager->getStorage('order_item_agro');

    $count = (int) $orderItemStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('product_id', $productId)
      ->condition('created', $startTimestamp, '>=')
      ->count()
      ->execute();

    return $count / max($days, 1);
  }

  /**
   * Obtiene la puntuación media de reviews de un producto.
   *
   * @param int $productId
   *   Product ID.
   *
   * @return float
   *   Puntuación media (0.0-5.0).
   */
  private function getAverageReviewScore(int $productId): float {
    $reviewStorage = $this->entityTypeManager->getStorage('review_agro');
    $reviewIds = $reviewStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('product_id', $productId)
      ->condition('status', 1)
      ->execute();

    if (empty($reviewIds)) {
      return 0.0;
    }

    $reviews = $reviewStorage->loadMultiple($reviewIds);
    $total = 0.0;
    $count = 0;

    foreach ($reviews as $review) {
      $rating = (float) ($review->get('rating')->value ?? 0);
      if ($rating > 0) {
        $total += $rating;
        $count++;
      }
    }

    return $count > 0 ? round($total / $count, 1) : 0.0;
  }

}
