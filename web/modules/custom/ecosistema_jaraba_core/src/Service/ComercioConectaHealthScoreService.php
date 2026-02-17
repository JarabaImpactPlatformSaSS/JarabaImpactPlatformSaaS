<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\State\StateInterface;
use Psr\Log\LoggerInterface;

/**
 * Health score service para el vertical ComercioConecta.
 *
 * Calcula un score de salud (0-100) del usuario basado en 5 dimensiones
 * ponderadas y KPIs del vertical. Categoriza usuarios como healthy,
 * neutral, at_risk o critical para intervenciones proactivas.
 *
 * Dimensiones (weights suman 1.0):
 * - catalog_health (0.25): Productos, fotos, descripciones, categorias
 * - sales_activity (0.30): Pedidos, revenue, conversion
 * - customer_engagement (0.20): Resenas, rating, tiempo de respuesta
 * - copilot_usage (0.10): Usos, acciones del copiloto
 * - marketplace_presence (0.15): SEO local, ofertas flash, redes sociales
 *
 * 8 KPIs verticales:
 * - gmv_monthly: GMV mensual (target 30000 EUR)
 * - merchant_activation_rate: Tasa activacion comerciantes (target 60%)
 * - order_completion_rate: Tasa completado pedidos (target 85%)
 * - review_response_rate: Tasa respuesta resenas (target 70%)
 * - nps: NPS del marketplace (target 55)
 * - arpu: Revenue medio por usuario (target 25 EUR/mes)
 * - conversion_free_paid: Conversion free a paid (target 15%)
 * - churn_rate: Tasa de abandono (target 5%)
 *
 * Plan Elevacion ComercioConecta Clase Mundial v1 — Fase 16.
 *
 * @see \Drupal\ecosistema_jaraba_core\Service\AgroConectaHealthScoreService
 */
class ComercioConectaHealthScoreService {

  /**
   * 5 dimensiones ponderadas del health score.
   */
  protected const DIMENSIONS = [
    'catalog_health' => ['weight' => 0.25, 'label' => 'Salud del Catalogo'],
    'sales_activity' => ['weight' => 0.30, 'label' => 'Actividad de Ventas'],
    'customer_engagement' => ['weight' => 0.20, 'label' => 'Engagement de Clientes'],
    'copilot_usage' => ['weight' => 0.10, 'label' => 'Uso del Copiloto'],
    'marketplace_presence' => ['weight' => 0.15, 'label' => 'Presencia en Marketplace'],
  ];

  /**
   * Target values for vertical KPIs.
   */
  protected const KPI_TARGETS = [
    'gmv_monthly' => ['value' => 30000, 'direction' => 'higher_better', 'label' => 'GMV Mensual'],
    'merchant_activation_rate' => ['value' => 60, 'direction' => 'higher_better', 'label' => 'Tasa Activacion Comerciantes'],
    'order_completion_rate' => ['value' => 85, 'direction' => 'higher_better', 'label' => 'Tasa Completado Pedidos'],
    'review_response_rate' => ['value' => 70, 'direction' => 'higher_better', 'label' => 'Tasa Respuesta Resenas'],
    'nps' => ['value' => 55, 'direction' => 'higher_better', 'label' => 'Net Promoter Score'],
    'arpu' => ['value' => 25, 'direction' => 'higher_better', 'label' => 'ARPU'],
    'conversion_free_paid' => ['value' => 15, 'direction' => 'higher_better', 'label' => 'Conversion Free a Paid'],
    'churn_rate' => ['value' => 5, 'direction' => 'lower_better', 'label' => 'Tasa de Churn'],
  ];

  /**
   * Constructor.
   */
  public function __construct(
    protected readonly StateInterface $state,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Calcula el health score de un usuario.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return array
   *   Array con user_id, overall_score (0-100), category y dimensions.
   */
  public function calculateUserHealth(int $userId): array {
    $dimensions = [];
    $overallScore = 0;

    foreach (self::DIMENSIONS as $key => $meta) {
      $score = $this->calculateDimension($userId, $key);
      $weightedScore = $score * $meta['weight'];
      $overallScore += $weightedScore;

      $dimensions[$key] = [
        'score' => $score,
        'weight' => $meta['weight'],
        'weighted_score' => round($weightedScore, 1),
        'label' => $meta['label'],
      ];
    }

    $overallScore = (int) round($overallScore);

    return [
      'user_id' => $userId,
      'overall_score' => $overallScore,
      'category' => $this->getCategory((float) $overallScore),
      'dimensions' => $dimensions,
    ];
  }

  /**
   * Calcula KPIs del vertical ComercioConecta.
   *
   * @return array
   *   Array de KPIs con value, target, status, label, unit.
   */
  public function calculateVerticalKpis(): array {
    $kpis = [];

    $kpis['gmv_monthly'] = $this->calculateGmvMonthly();
    $kpis['merchant_activation_rate'] = $this->calculateMerchantActivationRate();
    $kpis['order_completion_rate'] = $this->calculateOrderCompletionRate();
    $kpis['review_response_rate'] = $this->calculateReviewResponseRate();
    $kpis['nps'] = $this->calculateNps();
    $kpis['arpu'] = $this->calculateArpu();
    $kpis['conversion_free_paid'] = $this->calculateConversionFreePaid();
    $kpis['churn_rate'] = $this->calculateChurnRate();

    return $kpis;
  }

  /**
   * Categoriza un overall score en health categories.
   *
   * @param float $score
   *   Score de 0 a 100.
   *
   * @return string
   *   Categoria: healthy, neutral, at_risk o critical.
   */
  public function getCategory(float $score): string {
    if ($score >= 80) {
      return 'healthy';
    }
    if ($score >= 60) {
      return 'neutral';
    }
    if ($score >= 40) {
      return 'at_risk';
    }
    return 'critical';
  }

  /**
   * Calcula score de una dimension individual (0-100).
   */
  protected function calculateDimension(int $userId, string $key): float {
    return match ($key) {
      'catalog_health' => $this->calculateCatalogHealth($userId),
      'sales_activity' => $this->calculateSalesActivity($userId),
      'customer_engagement' => $this->calculateCustomerEngagement($userId),
      'copilot_usage' => $this->calculateCopilotUsage($userId),
      'marketplace_presence' => $this->calculateMarketplacePresence($userId),
      default => 0,
    };
  }

  /**
   * Catalog health dimension (0-100).
   *
   * Scoring: product count (max 25) + photos (max 25) +
   * descriptions (max 25) + categories assigned (max 25).
   */
  protected function calculateCatalogHealth(int $userId): float {
    try {
      $products = \Drupal::entityTypeManager()
        ->getStorage('comercio_product')
        ->loadByProperties(['owner_id' => $userId]);

      $total = count($products);
      if ($total === 0) {
        return 0;
      }

      // Product count: 5 products = 25 points.
      $countScore = min(25, $total * 5);

      // Photos: % with cover photo.
      $withPhoto = 0;
      $withDescription = 0;
      $withCategory = 0;
      foreach ($products as $product) {
        $photo = $product->get('cover_photo')->entity ?? NULL;
        if ($photo !== NULL) {
          $withPhoto++;
        }
        $description = $product->get('description')->value ?? '';
        if (!empty(trim($description))) {
          $withDescription++;
        }
        if ($product->hasField('category') && !empty($product->get('category')->value)) {
          $withCategory++;
        }
      }

      $photoRate = $total > 0 ? $withPhoto / $total : 0;
      $photoScore = min(25, (int) ($photoRate * 25));

      $descRate = $total > 0 ? $withDescription / $total : 0;
      $descScore = min(25, (int) ($descRate * 25));

      $catRate = $total > 0 ? $withCategory / $total : 0;
      $catScore = min(25, (int) ($catRate * 25));

      return (float) min(100, $countScore + $photoScore + $descScore + $catScore);
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Sales activity dimension (0-100).
   *
   * Scoring: order count (max 40) + revenue (max 30) + recent orders (max 30).
   */
  protected function calculateSalesActivity(int $userId): float {
    try {
      $orders = \Drupal::entityTypeManager()
        ->getStorage('comercio_order')
        ->loadByProperties(['seller_id' => $userId, 'status' => 'completed']);

      $total = count($orders);
      if ($total === 0) {
        return 0;
      }

      // Order count: 10 orders = 40 points.
      $countScore = min(40, $total * 4);

      // Revenue: calculate total.
      $totalRevenue = 0;
      foreach ($orders as $order) {
        $totalRevenue += (float) ($order->get('total')->value ?? 0);
      }
      // Revenue score: 1000 EUR = 30 points.
      $revenueScore = min(30, (int) ($totalRevenue / 1000 * 30));

      // Recent orders (last 30 days).
      $now = \Drupal::time()->getRequestTime();
      $recentCount = 0;
      foreach ($orders as $order) {
        $created = (int) ($order->get('created')->value ?? 0);
        if (($now - $created) <= (30 * 86400)) {
          $recentCount++;
        }
      }
      $recentScore = min(30, $recentCount * 10);

      return (float) min(100, $countScore + $revenueScore + $recentScore);
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Customer engagement dimension (0-100).
   *
   * Scoring: reviews received (max 30) + avg rating (max 40) +
   * response rate (max 30).
   */
  protected function calculateCustomerEngagement(int $userId): float {
    try {
      $reviews = \Drupal::entityTypeManager()
        ->getStorage('comercio_review')
        ->loadByProperties(['seller_id' => $userId]);

      $total = count($reviews);
      if ($total === 0) {
        return 0;
      }

      // Review count: 10 reviews = 30 points.
      $countScore = min(30, $total * 3);

      // Average rating (1-5): 5.0 = 40 points.
      $totalRating = 0;
      $responded = 0;
      foreach ($reviews as $review) {
        $totalRating += (float) ($review->get('rating')->value ?? 0);
        $responseStatus = $review->get('response_status')->value ?? 'pending';
        if ($responseStatus === 'responded') {
          $responded++;
        }
      }
      $avgRating = $total > 0 ? $totalRating / $total : 0;
      $ratingScore = min(40, (int) ($avgRating / 5 * 40));

      // Response rate: % responded.
      $responseRate = $total > 0 ? $responded / $total : 0;
      $responseScore = min(30, (int) ($responseRate * 30));

      return (float) min(100, $countScore + $ratingScore + $responseScore);
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Copilot usage dimension (0-100).
   *
   * Scoring: total uses (max 50) + actions taken (max 50).
   */
  protected function calculateCopilotUsage(int $userId): float {
    try {
      $stateKey = "comercioconecta_copilot_uses_{$userId}";
      $uses = (int) $this->state->get($stateKey, 0);
      if ($uses === 0) {
        return 0;
      }

      // Uses: 5 uses = 50 points.
      $usesScore = min(50, $uses * 10);

      // Actions: copilot actions completed.
      $actionsKey = "comercioconecta_copilot_actions_{$userId}";
      $actions = (int) $this->state->get($actionsKey, 0);
      $actionsScore = min(50, $actions * 25);

      return (float) min(100, $usesScore + $actionsScore);
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Marketplace presence dimension (0-100).
   *
   * Scoring: SEO local audit (max 25) + flash offers active (max 25) +
   * social links configured (max 25) + delivery options (max 25).
   */
  protected function calculateMarketplacePresence(int $userId): float {
    $score = 0;

    try {
      // SEO local audit completed.
      $seoKey = "comercioconecta_seo_audit_completed_{$userId}";
      if ($this->state->get($seoKey, FALSE)) {
        $score += 25;
      }
    }
    catch (\Exception $e) {
      // State may not be available.
    }

    try {
      // Flash offers active.
      $flashKey = "comercioconecta_flash_offers_active_{$userId}";
      if ((int) $this->state->get($flashKey, 0) > 0) {
        $score += 25;
      }
    }
    catch (\Exception $e) {
      // State may not be available.
    }

    try {
      // Social links configured.
      $socialKey = "comercioconecta_social_links_{$userId}";
      if ($this->state->get($socialKey, FALSE)) {
        $score += 25;
      }
    }
    catch (\Exception $e) {
      // State may not be available.
    }

    try {
      // Delivery options configured.
      $deliveryKey = "comercioconecta_delivery_configured_{$userId}";
      if ($this->state->get($deliveryKey, FALSE)) {
        $score += 25;
      }
    }
    catch (\Exception $e) {
      // State may not be available.
    }

    return (float) min(100, $score);
  }

  // ==================================================================
  // KPI calculators — Vertical level.
  // ==================================================================

  /**
   * KPI: GMV mensual (Gross Merchandise Volume).
   */
  protected function calculateGmvMonthly(): array {
    $target = self::KPI_TARGETS['gmv_monthly'];
    try {
      $stateKey = 'comercioconecta_kpi_gmv_monthly';
      $gmv = (float) $this->state->get($stateKey, 0);

      return [
        'value' => round($gmv, 2),
        'unit' => 'EUR',
        'target' => $target['value'],
        'status' => $gmv >= $target['value'] ? 'on_track' : 'behind',
        'label' => $target['label'],
      ];
    }
    catch (\Exception $e) {
      return ['value' => 0, 'unit' => 'EUR', 'target' => $target['value'], 'status' => 'behind', 'label' => $target['label']];
    }
  }

  /**
   * KPI: Tasa de activacion de comerciantes.
   */
  protected function calculateMerchantActivationRate(): array {
    $target = self::KPI_TARGETS['merchant_activation_rate'];
    try {
      $totalMerchants = (int) \Drupal::entityTypeManager()
        ->getStorage('comercio_product')
        ->getQuery()
        ->accessCheck(FALSE)
        ->count()
        ->execute();

      if ($totalMerchants === 0) {
        return ['value' => 0, 'unit' => '%', 'target' => $target['value'], 'status' => 'behind', 'label' => $target['label']];
      }

      $stateKey = 'comercioconecta_kpi_active_merchants';
      $activeMerchants = (int) $this->state->get($stateKey, 0);

      $rate = round(($activeMerchants / $totalMerchants) * 100, 1);

      return [
        'value' => $rate,
        'unit' => '%',
        'target' => $target['value'],
        'status' => $rate >= $target['value'] ? 'on_track' : 'behind',
        'label' => $target['label'],
      ];
    }
    catch (\Exception $e) {
      return ['value' => 0, 'unit' => '%', 'target' => $target['value'], 'status' => 'behind', 'label' => $target['label']];
    }
  }

  /**
   * KPI: Tasa de completado de pedidos.
   */
  protected function calculateOrderCompletionRate(): array {
    $target = self::KPI_TARGETS['order_completion_rate'];
    try {
      $totalOrders = (int) \Drupal::entityTypeManager()
        ->getStorage('comercio_order')
        ->getQuery()
        ->accessCheck(FALSE)
        ->count()
        ->execute();

      if ($totalOrders === 0) {
        return ['value' => 0, 'unit' => '%', 'target' => $target['value'], 'status' => 'behind', 'label' => $target['label']];
      }

      $completedOrders = (int) \Drupal::entityTypeManager()
        ->getStorage('comercio_order')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 'completed')
        ->count()
        ->execute();

      $rate = round(($completedOrders / $totalOrders) * 100, 1);

      return [
        'value' => $rate,
        'unit' => '%',
        'target' => $target['value'],
        'status' => $rate >= $target['value'] ? 'on_track' : 'behind',
        'label' => $target['label'],
      ];
    }
    catch (\Exception $e) {
      return ['value' => 0, 'unit' => '%', 'target' => $target['value'], 'status' => 'behind', 'label' => $target['label']];
    }
  }

  /**
   * KPI: Tasa de respuesta a resenas.
   */
  protected function calculateReviewResponseRate(): array {
    $target = self::KPI_TARGETS['review_response_rate'];
    try {
      $totalReviews = (int) \Drupal::entityTypeManager()
        ->getStorage('comercio_review')
        ->getQuery()
        ->accessCheck(FALSE)
        ->count()
        ->execute();

      if ($totalReviews === 0) {
        return ['value' => 0, 'unit' => '%', 'target' => $target['value'], 'status' => 'behind', 'label' => $target['label']];
      }

      $respondedReviews = (int) \Drupal::entityTypeManager()
        ->getStorage('comercio_review')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('response_status', 'responded')
        ->count()
        ->execute();

      $rate = round(($respondedReviews / $totalReviews) * 100, 1);

      return [
        'value' => $rate,
        'unit' => '%',
        'target' => $target['value'],
        'status' => $rate >= $target['value'] ? 'on_track' : 'behind',
        'label' => $target['label'],
      ];
    }
    catch (\Exception $e) {
      return ['value' => 0, 'unit' => '%', 'target' => $target['value'], 'status' => 'behind', 'label' => $target['label']];
    }
  }

  /**
   * KPI: NPS (Net Promoter Score).
   */
  protected function calculateNps(): array {
    $target = self::KPI_TARGETS['nps'];
    if (!\Drupal::hasService('jaraba_customer_success.nps_survey')) {
      return ['value' => 0, 'unit' => 'score', 'target' => $target['value'], 'status' => 'behind', 'label' => $target['label']];
    }

    try {
      $nps = \Drupal::service('jaraba_customer_success.nps_survey')
        ->getScore('comercioconecta');
      $score = $nps ?? 0;

      return [
        'value' => $score,
        'unit' => 'score',
        'target' => $target['value'],
        'status' => $score >= $target['value'] ? 'on_track' : 'behind',
        'label' => $target['label'],
      ];
    }
    catch (\Exception $e) {
      return ['value' => 0, 'unit' => 'score', 'target' => $target['value'], 'status' => 'behind', 'label' => $target['label']];
    }
  }

  /**
   * KPI: ARPU (Average Revenue Per User).
   */
  protected function calculateArpu(): array {
    $target = self::KPI_TARGETS['arpu'];
    try {
      $stateKey = 'comercioconecta_kpi_arpu';
      $arpu = (float) $this->state->get($stateKey, 0);

      return [
        'value' => round($arpu, 2),
        'unit' => 'EUR/mes',
        'target' => $target['value'],
        'status' => $arpu >= $target['value'] ? 'on_track' : 'behind',
        'label' => $target['label'],
      ];
    }
    catch (\Exception $e) {
      return ['value' => 0, 'unit' => 'EUR/mes', 'target' => $target['value'], 'status' => 'behind', 'label' => $target['label']];
    }
  }

  /**
   * KPI: Free to Paid conversion rate.
   */
  protected function calculateConversionFreePaid(): array {
    $target = self::KPI_TARGETS['conversion_free_paid'];
    try {
      $stateKey = 'comercioconecta_kpi_conversion_rate';
      $rate = (float) $this->state->get($stateKey, 0);

      return [
        'value' => round($rate, 1),
        'unit' => '%',
        'target' => $target['value'],
        'status' => $rate >= $target['value'] ? 'on_track' : 'behind',
        'label' => $target['label'],
      ];
    }
    catch (\Exception $e) {
      return ['value' => 0, 'unit' => '%', 'target' => $target['value'], 'status' => 'behind', 'label' => $target['label']];
    }
  }

  /**
   * KPI: Churn rate (% paid users cancelling within 30 days).
   */
  protected function calculateChurnRate(): array {
    $target = self::KPI_TARGETS['churn_rate'];
    try {
      $stateKey = 'comercioconecta_kpi_churn_rate';
      $rate = (float) $this->state->get($stateKey, 0);

      return [
        'value' => round($rate, 1),
        'unit' => '%',
        'target' => $target['value'],
        'status' => $rate <= $target['value'] ? 'on_track' : 'behind',
        'label' => $target['label'],
      ];
    }
    catch (\Exception $e) {
      return ['value' => 0, 'unit' => '%', 'target' => $target['value'], 'status' => 'on_track', 'label' => $target['label']];
    }
  }

}
