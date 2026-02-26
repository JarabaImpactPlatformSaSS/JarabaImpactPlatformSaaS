<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Service;

use Drupal\ecosistema_jaraba_core\AI\AIIdentityRule;
use Psr\Log\LoggerInterface;

/**
 * GAP-AUD-020: AI-powered demand forecasting for ComercioConecta.
 *
 * Analyzes historical sales data from ComercioAnalyticsService and uses
 * AI to predict future demand trends, seasonal patterns, and stock
 * recommendations per merchant.
 */
class DemandForecastingService {

  /**
   * Constructor.
   *
   * @param \Drupal\jaraba_comercio_conecta\Service\ComercioAnalyticsService $analyticsService
   *   Historical sales data provider.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger.
   * @param object|null $aiAgent
   *   SmartBaseAgent for AI-powered analysis (optional).
   */
  public function __construct(
    protected ComercioAnalyticsService $analyticsService,
    protected LoggerInterface $logger,
    protected ?object $aiAgent = NULL,
  ) {}

  /**
   * Generates demand forecast for a merchant's products.
   *
   * @param int $merchantId
   *   Merchant profile entity ID.
   * @param int $daysAhead
   *   Number of days to forecast (default 30).
   *
   * @return array
   *   Forecast data with predictions and confidence scores.
   */
  public function forecast(int $merchantId, int $daysAhead = 30): array {
    // Gather 90-day historical data.
    $revenueChart = $this->analyticsService->getRevenueChart('90d');
    $topProducts = $this->analyticsService->getTopProducts(20, '90d');
    $topCategories = $this->analyticsService->getTopCategories(10);
    $retentionRate = $this->analyticsService->getCustomerRetentionRate('90d');
    $kpis = $this->analyticsService->getMarketplaceKpis('30d');

    if ($this->aiAgent === NULL) {
      return $this->buildRuleBasedForecast($revenueChart, $topProducts, $kpis, $daysAhead);
    }

    try {
      $prompt = $this->buildForecastPrompt(
        $revenueChart,
        $topProducts,
        $topCategories,
        $retentionRate,
        $kpis,
        $daysAhead
      );

      $result = $this->aiAgent->execute([
        'prompt' => AIIdentityRule::apply($prompt, TRUE),
        'tier' => 'balanced',
        'max_tokens' => 2048,
        'temperature' => 0.3,
      ]);

      $responseText = $result['response'] ?? $result['text'] ?? '';
      $parsed = $this->parseForecastResponse($responseText);

      if (!empty($parsed)) {
        return [
          'merchant_id' => $merchantId,
          'forecast_days' => $daysAhead,
          'predictions' => $parsed['predictions'] ?? [],
          'seasonal_patterns' => $parsed['seasonal_patterns'] ?? [],
          'stock_recommendations' => $parsed['stock_recommendations'] ?? [],
          'growth_rate' => $parsed['growth_rate'] ?? 0.0,
          'confidence' => $parsed['confidence'] ?? 0.7,
          'ai_enhanced' => TRUE,
          'model' => $result['model'] ?? '',
          'generated_at' => date('c'),
        ];
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('AI demand forecast failed, using rule-based fallback: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $this->buildRuleBasedForecast($revenueChart, $topProducts, $kpis, $daysAhead);
  }

  /**
   * Returns stock level recommendations for top products.
   *
   * @param int $merchantId
   *   Merchant profile entity ID.
   *
   * @return array
   *   Stock recommendations per product.
   */
  public function getStockRecommendations(int $merchantId): array {
    $forecast = $this->forecast($merchantId, 14);
    return $forecast['stock_recommendations'] ?? [];
  }

  /**
   * Builds the AI prompt for demand forecasting.
   */
  protected function buildForecastPrompt(
    array $revenueChart,
    array $topProducts,
    array $topCategories,
    float $retentionRate,
    array $kpis,
    int $daysAhead,
  ): string {
    $revenueJson = json_encode(array_slice($revenueChart, -90), JSON_UNESCAPED_UNICODE);
    $productsJson = json_encode($topProducts, JSON_UNESCAPED_UNICODE);
    $categoriesJson = json_encode($topCategories, JSON_UNESCAPED_UNICODE);

    return <<<PROMPT
Analiza los datos historicos de ventas de un marketplace y genera un pronostico de demanda para los proximos {$daysAhead} dias.

**Datos historicos (90 dias):**
Ingresos diarios: {$revenueJson}

**Top productos:** {$productsJson}

**Top categorias:** {$categoriesJson}

**KPIs actuales (30 dias):**
- GMV: {$kpis['gmv']} EUR
- Pedidos totales: {$kpis['total_orders']}
- Ticket medio: {$kpis['avg_order_value']} EUR
- Tasa de retencion: {$retentionRate}%
- Tasa de conversion: {$kpis['conversion_rate']}%

**Responde en JSON estricto:**
{
  "predictions": [
    {"period": "semana_1", "revenue_estimate": 0, "orders_estimate": 0},
    {"period": "semana_2", "revenue_estimate": 0, "orders_estimate": 0}
  ],
  "seasonal_patterns": ["descripcion de patrones estacionales detectados"],
  "stock_recommendations": [
    {"product_name": "...", "action": "increase|maintain|decrease", "reason": "..."}
  ],
  "growth_rate": 0.0,
  "confidence": 0.0
}
PROMPT;
  }

  /**
   * Parses AI forecast response into structured data.
   */
  protected function parseForecastResponse(string $response): array {
    // Extract JSON from response (may be wrapped in markdown code blocks).
    if (preg_match('/\{[\s\S]*\}/u', $response, $matches)) {
      $decoded = json_decode($matches[0], TRUE);
      if (is_array($decoded)) {
        return $decoded;
      }
    }

    return [];
  }

  /**
   * Builds a rule-based forecast when AI is not available.
   */
  protected function buildRuleBasedForecast(
    array $revenueChart,
    array $topProducts,
    array $kpis,
    int $daysAhead,
  ): array {
    // Simple linear projection from last 30 days.
    $recentDays = array_slice($revenueChart, -30);
    $totalRevenue = 0.0;
    $totalOrders = 0;

    foreach ($recentDays as $day) {
      $totalRevenue += (float) ($day['revenue'] ?? 0);
      $totalOrders += (int) ($day['order_count'] ?? 0);
    }

    $dayCount = count($recentDays) ?: 1;
    $dailyRevenue = $totalRevenue / $dayCount;
    $dailyOrders = $totalOrders / $dayCount;

    // Build weekly predictions.
    $predictions = [];
    $weeks = (int) ceil($daysAhead / 7);
    for ($i = 1; $i <= $weeks; $i++) {
      $predictions[] = [
        'period' => "semana_{$i}",
        'revenue_estimate' => round($dailyRevenue * 7, 2),
        'orders_estimate' => (int) round($dailyOrders * 7),
      ];
    }

    // Stock recommendations for top 5 products.
    $stockRecs = [];
    foreach (array_slice($topProducts, 0, 5) as $product) {
      $stockRecs[] = [
        'product_name' => $product['product_name'] ?? 'Desconocido',
        'action' => 'maintain',
        'reason' => 'Proyeccion lineal basada en ventas recientes.',
      ];
    }

    return [
      'merchant_id' => 0,
      'forecast_days' => $daysAhead,
      'predictions' => $predictions,
      'seasonal_patterns' => [],
      'stock_recommendations' => $stockRecs,
      'growth_rate' => 0.0,
      'confidence' => 0.4,
      'ai_enhanced' => FALSE,
      'generated_at' => date('c'),
    ];
  }

}
