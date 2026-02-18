<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Psr\Log\LoggerInterface;

/**
 * Health score service para el vertical AgroConecta.
 *
 * Calcula un score de salud (0-100) del usuario basado en 5 dimensiones
 * ponderadas y KPIs del vertical. Categoriza usuarios como healthy,
 * neutral, at_risk o critical para intervenciones proactivas.
 *
 * Dimensiones (weights suman 1.0):
 * - catalog_health (0.25): Productos, fotos, descripciones, certificaciones
 * - sales_activity (0.30): Pedidos, revenue, conversion
 * - customer_engagement (0.20): Reviews, rating, tiempo de respuesta
 * - copilot_usage (0.10): Usos, acciones del copiloto
 * - marketplace_presence (0.15): QR, B2B, shipping, social
 *
 * 8 KPIs verticales:
 * - gmv_monthly: GMV mensual (target 50000 EUR)
 * - producer_activation_rate: Tasa activacion productores (target 60%)
 * - order_completion_rate: Tasa finalizacion pedidos (target 85%)
 * - review_response_rate: Tasa respuesta reviews (target 70%)
 * - nps: NPS del marketplace (target 55)
 * - arpu: Revenue medio por usuario (target 35 EUR/mes)
 * - conversion_free_paid: Conversion free a paid (target 15%)
 * - churn_rate: Tasa de abandono (target 5%)
 *
 * Plan Elevacion AgroConecta Clase Mundial v1 — Fase 10.
 *
 * @see \Drupal\ecosistema_jaraba_core\Service\EmployabilityHealthScoreService
 */
class AgroConectaHealthScoreService {

  /**
   * 5 dimensiones ponderadas del health score.
   */
  protected const DIMENSIONS = [
    'catalog_health' => ['weight' => 0.25, 'label' => 'Salud del catalogo'],
    'sales_activity' => ['weight' => 0.30, 'label' => 'Actividad de ventas'],
    'customer_engagement' => ['weight' => 0.20, 'label' => 'Engagement de clientes'],
    'copilot_usage' => ['weight' => 0.10, 'label' => 'Uso del copilot'],
    'marketplace_presence' => ['weight' => 0.15, 'label' => 'Presencia en marketplace'],
  ];

  /**
   * Target values for vertical KPIs.
   */
  protected const KPI_TARGETS = [
    'gmv_monthly' => ['value' => 50000, 'direction' => 'higher'],
    'producer_activation_rate' => ['value' => 60, 'direction' => 'higher'],
    'order_completion_rate' => ['value' => 85, 'direction' => 'higher'],
    'review_response_rate' => ['value' => 70, 'direction' => 'higher'],
    'nps' => ['value' => 55, 'direction' => 'higher'],
    'arpu' => ['value' => 35, 'direction' => 'higher'],
    'conversion_free_paid' => ['value' => 15, 'direction' => 'higher'],
    'churn_rate' => ['value' => 5, 'direction' => 'lower'],
  ];

  /**
   * Constructor.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly Connection $database,
    protected readonly LoggerInterface $logger,
    protected readonly TimeInterface $time,
    protected readonly StateInterface $state,
    protected readonly ?object $agroconectaFeatureGate = NULL,
    protected readonly ?object $npsSurvey = NULL,
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
      'category' => $this->categorize($overallScore),
      'dimensions' => $dimensions,
    ];
  }

  /**
   * Calcula KPIs del vertical AgroConecta.
   *
   * @return array
   *   Array de KPIs con value, target, status, label, unit.
   */
  public function calculateVerticalKpis(): array {
    $kpis = [];

    $kpis['gmv_monthly'] = $this->calculateGmvMonthly();
    $kpis['producer_activation_rate'] = $this->calculateProducerActivationRate();
    $kpis['order_completion_rate'] = $this->calculateOrderCompletionRate();
    $kpis['review_response_rate'] = $this->calculateReviewResponseRate();
    $kpis['nps'] = $this->calculateNps();
    $kpis['arpu'] = $this->calculateArpu();
    $kpis['conversion_free_paid'] = $this->calculateConversionRate();
    $kpis['churn_rate'] = $this->calculateChurnRate();

    return $kpis;
  }

  /**
   * Calcula score de una dimension individual (0-100).
   */
  protected function calculateDimension(int $userId, string $key): int {
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
   * descriptions (max 25) + certifications (max 25).
   */
  protected function calculateCatalogHealth(int $userId): int {
    try {
      $products = $this->entityTypeManager->getStorage('agro_product')
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
      $withCertification = 0;
      foreach ($products as $product) {
        $photo = $product->get('cover_photo')->entity ?? NULL;
        if ($photo !== NULL) {
          $withPhoto++;
        }
        $description = $product->get('description')->value ?? '';
        if (!empty(trim($description))) {
          $withDescription++;
        }
        if ($product->hasField('certification') && !empty($product->get('certification')->value)) {
          $withCertification++;
        }
      }

      $photoRate = $total > 0 ? $withPhoto / $total : 0;
      $photoScore = min(25, (int) ($photoRate * 25));

      $descRate = $total > 0 ? $withDescription / $total : 0;
      $descScore = min(25, (int) ($descRate * 25));

      $certRate = $total > 0 ? $withCertification / $total : 0;
      $certScore = min(25, (int) ($certRate * 25));

      return min(100, $countScore + $photoScore + $descScore + $certScore);
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Sales activity dimension (0-100).
   *
   * Scoring: order count (max 40) + revenue (max 30) + conversion (max 30).
   */
  protected function calculateSalesActivity(int $userId): int {
    try {
      $orders = $this->entityTypeManager->getStorage('agro_order')
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

      // Conversion: recent orders (last 30 days) vs total.
      $now = $this->time->getRequestTime();
      $recentCount = 0;
      foreach ($orders as $order) {
        $created = (int) ($order->get('created')->value ?? 0);
        if (($now - $created) <= (30 * 86400)) {
          $recentCount++;
        }
      }
      $conversionScore = min(30, $recentCount * 10);

      return min(100, $countScore + $revenueScore + $conversionScore);
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Customer engagement dimension (0-100).
   *
   * Scoring: reviews received (max 30) + avg rating (max 40) +
   * response time (max 30).
   */
  protected function calculateCustomerEngagement(int $userId): int {
    try {
      $reviews = $this->entityTypeManager->getStorage('agro_review')
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

      return min(100, $countScore + $ratingScore + $responseScore);
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
  protected function calculateCopilotUsage(int $userId): int {
    try {
      $stateKey = "agroconecta_copilot_uses_{$userId}";
      $uses = (int) $this->state->get($stateKey, 0);
      if ($uses === 0) {
        return 0;
      }

      // Uses: 5 uses = 50 points.
      $usesScore = min(50, $uses * 10);

      // Actions: copilot actions completed.
      $actionsKey = "agroconecta_copilot_actions_{$userId}";
      $actions = (int) $this->state->get($actionsKey, 0);
      $actionsScore = min(50, $actions * 25);

      return min(100, $usesScore + $actionsScore);
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Marketplace presence dimension (0-100).
   *
   * Scoring: QR traceability (max 25) + B2B (max 25) +
   * shipping configured (max 25) + social links (max 25).
   */
  protected function calculateMarketplacePresence(int $userId): int {
    $score = 0;

    try {
      // QR traceability active.
      if ($this->agroconectaFeatureGate) {
        $qrResult = $this->agroconectaFeatureGate->check($userId, 'traceability_qr');
        if (($qrResult->used ?? 0) > 0) {
          $score += 25;
        }
      }
    }
    catch (\Exception $e) {
      // Feature gate may not be available.
    }

    try {
      // B2B channel active.
      $b2bKey = "agroconecta_b2b_active_{$userId}";
      if ($this->state->get($b2bKey, FALSE)) {
        $score += 25;
      }
    }
    catch (\Exception $e) {
      // State may not be available.
    }

    try {
      // Shipping configured.
      $shippingKey = "agroconecta_shipping_configured_{$userId}";
      if ($this->state->get($shippingKey, FALSE)) {
        $score += 25;
      }
    }
    catch (\Exception $e) {
      // State may not be available.
    }

    try {
      // Social links.
      $socialKey = "agroconecta_social_links_{$userId}";
      if ($this->state->get($socialKey, FALSE)) {
        $score += 25;
      }
    }
    catch (\Exception $e) {
      // State may not be available.
    }

    return min(100, $score);
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
      $thirtyDaysAgo = $this->time->getRequestTime() - (30 * 86400);
      $gmv = (float) $this->database->query(
        "SELECT COALESCE(SUM(total), 0) FROM {agro_order} WHERE status = 'completed' AND created > :cutoff",
        [':cutoff' => $thirtyDaysAgo]
      )->fetchField();

      return [
        'value' => round($gmv, 2),
        'unit' => 'EUR',
        'target' => $target['value'],
        'status' => $gmv >= $target['value'] ? 'on_track' : 'behind',
        'label' => 'GMV Mensual',
      ];
    }
    catch (\Exception $e) {
      return ['value' => 0, 'unit' => 'EUR', 'target' => $target['value'], 'status' => 'behind', 'label' => 'GMV Mensual'];
    }
  }

  /**
   * KPI: Tasa de activacion de productores.
   */
  protected function calculateProducerActivationRate(): array {
    $target = self::KPI_TARGETS['producer_activation_rate'];
    try {
      $totalProducers = (int) $this->database->query(
        "SELECT COUNT(DISTINCT owner_id) FROM {agro_product}"
      )->fetchField();

      if ($totalProducers === 0) {
        return ['value' => 0, 'unit' => '%', 'target' => $target['value'], 'status' => 'behind', 'label' => 'Tasa activacion productores'];
      }

      $activeProducers = (int) $this->database->query(
        "SELECT COUNT(DISTINCT seller_id) FROM {agro_order} WHERE status = 'completed'"
      )->fetchField();

      $rate = round(($activeProducers / $totalProducers) * 100, 1);

      return [
        'value' => $rate,
        'unit' => '%',
        'target' => $target['value'],
        'status' => $rate >= $target['value'] ? 'on_track' : 'behind',
        'label' => 'Tasa activacion productores',
      ];
    }
    catch (\Exception $e) {
      return ['value' => 0, 'unit' => '%', 'target' => $target['value'], 'status' => 'behind', 'label' => 'Tasa activacion productores'];
    }
  }

  /**
   * KPI: Tasa de finalizacion de pedidos.
   */
  protected function calculateOrderCompletionRate(): array {
    $target = self::KPI_TARGETS['order_completion_rate'];
    try {
      $totalOrders = (int) $this->database->query(
        "SELECT COUNT(*) FROM {agro_order}"
      )->fetchField();

      if ($totalOrders === 0) {
        return ['value' => 0, 'unit' => '%', 'target' => $target['value'], 'status' => 'behind', 'label' => 'Tasa finalizacion pedidos'];
      }

      $completedOrders = (int) $this->database->query(
        "SELECT COUNT(*) FROM {agro_order} WHERE status = 'completed'"
      )->fetchField();

      $rate = round(($completedOrders / $totalOrders) * 100, 1);

      return [
        'value' => $rate,
        'unit' => '%',
        'target' => $target['value'],
        'status' => $rate >= $target['value'] ? 'on_track' : 'behind',
        'label' => 'Tasa finalizacion pedidos',
      ];
    }
    catch (\Exception $e) {
      return ['value' => 0, 'unit' => '%', 'target' => $target['value'], 'status' => 'behind', 'label' => 'Tasa finalizacion pedidos'];
    }
  }

  /**
   * KPI: Tasa de respuesta a reviews.
   */
  protected function calculateReviewResponseRate(): array {
    $target = self::KPI_TARGETS['review_response_rate'];
    try {
      $totalReviews = (int) $this->database->query(
        "SELECT COUNT(*) FROM {agro_review}"
      )->fetchField();

      if ($totalReviews === 0) {
        return ['value' => 0, 'unit' => '%', 'target' => $target['value'], 'status' => 'behind', 'label' => 'Tasa respuesta reviews'];
      }

      $respondedReviews = (int) $this->database->query(
        "SELECT COUNT(*) FROM {agro_review} WHERE response_status = 'responded'"
      )->fetchField();

      $rate = round(($respondedReviews / $totalReviews) * 100, 1);

      return [
        'value' => $rate,
        'unit' => '%',
        'target' => $target['value'],
        'status' => $rate >= $target['value'] ? 'on_track' : 'behind',
        'label' => 'Tasa respuesta reviews',
      ];
    }
    catch (\Exception $e) {
      return ['value' => 0, 'unit' => '%', 'target' => $target['value'], 'status' => 'behind', 'label' => 'Tasa respuesta reviews'];
    }
  }

  /**
   * KPI: NPS (Net Promoter Score).
   */
  protected function calculateNps(): array {
    $target = self::KPI_TARGETS['nps'];
    if (!$this->npsSurvey) {
      return ['value' => 0, 'unit' => 'score', 'target' => $target['value'], 'status' => 'behind', 'label' => 'NPS Marketplace'];
    }

    try {
      $nps = $this->npsSurvey->getScore('agroconecta');
      $score = $nps ?? 0;

      return [
        'value' => $score,
        'unit' => 'score',
        'target' => $target['value'],
        'status' => $score >= $target['value'] ? 'on_track' : 'behind',
        'label' => 'NPS Marketplace',
      ];
    }
    catch (\Exception $e) {
      return ['value' => 0, 'unit' => 'score', 'target' => $target['value'], 'status' => 'behind', 'label' => 'NPS Marketplace'];
    }
  }

  /**
   * KPI: ARPU (Average Revenue Per User).
   */
  protected function calculateArpu(): array {
    $target = self::KPI_TARGETS['arpu'];
    try {
      $thirtyDaysAgo = $this->time->getRequestTime() - (30 * 86400);

      $totalRevenue = (float) $this->database->query(
        "SELECT COALESCE(SUM(total), 0) FROM {agro_order} WHERE status = 'completed' AND created > :cutoff",
        [':cutoff' => $thirtyDaysAgo]
      )->fetchField();

      $activeUsers = (int) $this->database->query(
        "SELECT COUNT(DISTINCT seller_id) FROM {agro_order} WHERE status = 'completed' AND created > :cutoff",
        [':cutoff' => $thirtyDaysAgo]
      )->fetchField();

      $arpu = $activeUsers > 0 ? round($totalRevenue / $activeUsers, 2) : 0;

      return [
        'value' => $arpu,
        'unit' => 'EUR/mes',
        'target' => $target['value'],
        'status' => $arpu >= $target['value'] ? 'on_track' : 'behind',
        'label' => 'ARPU AgroConecta',
      ];
    }
    catch (\Exception $e) {
      return ['value' => 0, 'unit' => 'EUR/mes', 'target' => $target['value'], 'status' => 'behind', 'label' => 'ARPU AgroConecta'];
    }
  }

  /**
   * KPI: Free to Paid conversion rate.
   */
  protected function calculateConversionRate(): array {
    $target = self::KPI_TARGETS['conversion_free_paid'];
    try {
      $totalTriggers = (int) $this->database->query(
        "SELECT COUNT(*) FROM {upgrade_trigger_log} WHERE vertical = :v",
        [':v' => 'agroconecta']
      )->fetchField();

      $conversions = (int) $this->database->query(
        "SELECT COUNT(*) FROM {upgrade_trigger_log} WHERE vertical = :v AND converted = 1",
        [':v' => 'agroconecta']
      )->fetchField();

      $rate = $totalTriggers > 0 ? round(($conversions / $totalTriggers) * 100, 1) : 0;

      return [
        'value' => $rate,
        'unit' => '%',
        'target' => $target['value'],
        'status' => $rate >= $target['value'] ? 'on_track' : 'behind',
        'label' => 'Conversion Free→Paid',
      ];
    }
    catch (\Exception $e) {
      return ['value' => 0, 'unit' => '%', 'target' => $target['value'], 'status' => 'behind', 'label' => 'Conversion Free→Paid'];
    }
  }

  /**
   * KPI: Churn rate (% paid users cancelling within 30 days).
   */
  protected function calculateChurnRate(): array {
    $target = self::KPI_TARGETS['churn_rate'];
    try {
      $thirtyDaysAgo = $this->time->getRequestTime() - (30 * 86400);

      $cancelled = (int) $this->database->query(
        "SELECT COUNT(*) FROM {tenant_subscription_log} WHERE vertical = :v AND action = 'cancel' AND created > :cutoff",
        [':v' => 'agroconecta', ':cutoff' => $thirtyDaysAgo]
      )->fetchField();

      $totalPaid = (int) $this->database->query(
        "SELECT COUNT(*) FROM {tenant_subscription_log} WHERE vertical = :v AND action = 'subscribe' AND created > :cutoff",
        [':v' => 'agroconecta', ':cutoff' => $thirtyDaysAgo]
      )->fetchField();

      $rate = $totalPaid > 0 ? round(($cancelled / $totalPaid) * 100, 1) : 0;

      return [
        'value' => $rate,
        'unit' => '%',
        'target' => $target['value'],
        'status' => $rate <= $target['value'] ? 'on_track' : 'behind',
        'label' => 'Churn Rate',
      ];
    }
    catch (\Exception $e) {
      return ['value' => 0, 'unit' => '%', 'target' => $target['value'], 'status' => 'on_track', 'label' => 'Churn Rate'];
    }
  }

  /**
   * Categoriza un overall score en health categories.
   */
  protected function categorize(int $score): string {
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

}
