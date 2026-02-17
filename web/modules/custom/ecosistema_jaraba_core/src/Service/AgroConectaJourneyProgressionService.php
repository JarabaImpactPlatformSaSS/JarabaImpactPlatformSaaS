<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Psr\Log\LoggerInterface;

/**
 * Journey progression service para el vertical AgroConecta.
 *
 * Evalua reglas proactivas que determinan cuando y como el copiloto
 * debe intervenir para guiar al usuario en su recorrido marketplace.
 * Las reglas se basan en el estado del journey y la actividad del usuario.
 *
 * Reglas proactivas (8 productor + 2 consumidor):
 * - inactivity_discovery: Sin actividad 3 dias (productor, discovery)
 * - incomplete_catalog: 1 producto sin foto de portada (productor, activation)
 * - first_sale_nudge: Productos publicados 0 pedidos 7 dias (productor, activation)
 * - review_response: Reviews sin responder >3 dias (productor, engagement)
 * - pricing_optimization: 5+ ventas sin ajuste de precio (productor, engagement)
 * - traceability_opportunity: Plan Starter >10 ventas sin QR (productor, conversion)
 * - upgrade_suggestion: Plan free 4 productos 80% limite (productor, conversion)
 * - b2b_expansion: >50 ventas rating >4.5 (productor, retention)
 * - cart_abandoned: Carrito sin checkout 24h (consumidor, activation)
 * - reorder_nudge: Ultimo pedido >30 dias (consumidor, retention)
 *
 * Plan Elevacion AgroConecta Clase Mundial v1 — Fase 9.
 *
 * @see \Drupal\ecosistema_jaraba_core\Service\AndaluciaEiJourneyProgressionService
 */
class AgroConectaJourneyProgressionService {

  /**
   * Reglas proactivas de intervencion.
   */
  protected const PROACTIVE_RULES = [
    // =============================================
    // PRODUCTOR RULES (8).
    // =============================================
    'inactivity_discovery' => [
      'state' => 'discovery',
      'role' => 'productor',
      'condition' => 'no_activity_3_days',
      'message' => 'Llevas unos dias sin actividad en tu tienda. Puedo ayudarte a publicar tu primer producto o mejorar tu catalogo.',
      'cta_label' => 'Publicar producto',
      'cta_url' => '/agroconecta/products/add',
      'channel' => 'fab_dot',
      'mode' => 'agro_copilot',
      'priority' => 10,
    ],
    'incomplete_catalog' => [
      'state' => 'activation',
      'role' => 'productor',
      'condition' => 'product_no_cover_photo',
      'message' => 'Tienes un producto sin foto de portada. Los productos con foto se venden hasta 3x mas rapido.',
      'cta_label' => 'Anadir foto',
      'cta_url' => '/agroconecta/products',
      'channel' => 'fab_expand',
      'mode' => 'agro_copilot',
      'priority' => 12,
    ],
    'first_sale_nudge' => [
      'state' => 'activation',
      'role' => 'productor',
      'condition' => 'products_published_no_orders_7_days',
      'message' => 'Tus productos estan publicados pero aun no has recibido pedidos. Te doy ideas para aumentar tu visibilidad.',
      'cta_label' => 'Mejorar visibilidad',
      'cta_url' => '/agroconecta/dashboard',
      'channel' => 'fab_expand',
      'mode' => 'agro_copilot',
      'priority' => 15,
    ],
    'review_response' => [
      'state' => 'engagement',
      'role' => 'productor',
      'condition' => 'unanswered_reviews_3_days',
      'message' => 'Tienes reviews de clientes sin responder. Responder mejora tu reputacion y posicionamiento.',
      'cta_label' => 'Responder reviews',
      'cta_url' => '/agroconecta/reviews',
      'channel' => 'fab_dot',
      'mode' => 'agro_copilot',
      'priority' => 8,
    ],
    'pricing_optimization' => [
      'state' => 'engagement',
      'role' => 'productor',
      'condition' => '5_sales_no_price_adjustment',
      'message' => 'Llevas 5 ventas sin ajustar precios. Puedo analizar tu competencia y sugerirte un precio optimo.',
      'cta_label' => 'Optimizar precios',
      'cta_url' => '/agroconecta/pricing',
      'channel' => 'fab_expand',
      'mode' => 'agro_copilot',
      'priority' => 18,
    ],
    'traceability_opportunity' => [
      'state' => 'conversion',
      'role' => 'productor',
      'condition' => 'starter_10_sales_no_qr',
      'message' => 'Con mas de 10 ventas, activar trazabilidad QR aumenta la confianza del comprador y tu diferenciacion.',
      'cta_label' => 'Activar QR',
      'cta_url' => '/agroconecta/traceability',
      'channel' => 'fab_expand',
      'mode' => 'agro_copilot',
      'priority' => 6,
    ],
    'upgrade_suggestion' => [
      'state' => 'conversion',
      'role' => 'productor',
      'condition' => 'free_plan_80_percent_products',
      'message' => 'Has alcanzado el 80% del limite de productos en tu plan gratuito. Con el plan Starter publicas sin limite.',
      'cta_label' => 'Ver plan Starter',
      'cta_url' => '/upgrade?vertical=agroconecta&source=journey',
      'channel' => 'fab_expand',
      'mode' => 'agro_copilot',
      'priority' => 5,
    ],
    'b2b_expansion' => [
      'state' => 'retention',
      'role' => 'productor',
      'condition' => '50_sales_rating_4_5',
      'message' => 'Con mas de 50 ventas y una valoracion excelente, puedes acceder al canal B2B y vender a hosteleria y tiendas.',
      'cta_label' => 'Explorar B2B',
      'cta_url' => '/agroconecta/b2b',
      'channel' => 'fab_dot',
      'mode' => 'agro_copilot',
      'priority' => 20,
    ],
    // =============================================
    // CONSUMIDOR RULES (2).
    // =============================================
    'cart_abandoned' => [
      'state' => 'activation',
      'role' => 'consumidor',
      'condition' => 'cart_no_checkout_24h',
      'message' => 'Dejaste productos en tu carrito. Completar tu pedido ahora asegura disponibilidad y frescura.',
      'cta_label' => 'Completar pedido',
      'cta_url' => '/agroconecta/cart',
      'channel' => 'fab_dot',
      'mode' => 'agro_copilot',
      'priority' => 7,
    ],
    'reorder_nudge' => [
      'state' => 'retention',
      'role' => 'consumidor',
      'condition' => 'last_order_30_days_ago',
      'message' => 'Hace mas de 30 dias de tu ultimo pedido. Descubre los productos de temporada disponibles ahora.',
      'cta_label' => 'Ver productos',
      'cta_url' => '/agroconecta/marketplace',
      'channel' => 'fab_dot',
      'mode' => 'agro_copilot',
      'priority' => 22,
    ],
  ];

  /**
   * Constructor.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly StateInterface $state,
    protected readonly Connection $database,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Evalua reglas proactivas para un usuario.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return array|null
   *   Primera regla que aplica con 'rule_id' anadido, o NULL.
   */
  public function evaluate(int $userId): ?array {
    $dismissed = $this->getDismissedRules($userId);

    $sortedRules = self::PROACTIVE_RULES;
    uasort($sortedRules, fn(array $a, array $b) => $a['priority'] <=> $b['priority']);

    foreach ($sortedRules as $ruleId => $rule) {
      if (in_array($ruleId, $dismissed, TRUE)) {
        continue;
      }
      if ($this->checkCondition($userId, $rule['condition'])) {
        return array_merge($rule, ['rule_id' => $ruleId]);
      }
    }

    return NULL;
  }

  /**
   * Obtiene la accion pendiente cacheada (1h TTL).
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return array|null
   *   Regla pendiente o NULL.
   */
  public function getPendingAction(int $userId): ?array {
    $stateKey = "agroconecta_proactive_pending_{$userId}";
    $cached = $this->state->get($stateKey);

    if ($cached) {
      $age = \Drupal::time()->getRequestTime() - ($cached['evaluated_at'] ?? 0);
      if ($age < 3600) {
        return $cached['action'];
      }
    }

    $action = $this->evaluate($userId);
    $this->state->set($stateKey, [
      'action' => $action,
      'evaluated_at' => \Drupal::time()->getRequestTime(),
    ]);

    return $action;
  }

  /**
   * Descarta una regla para un usuario.
   */
  public function dismissAction(int $userId, string $ruleId): void {
    $key = "agroconecta_proactive_dismissed_{$userId}";
    $dismissed = $this->state->get($key, []);
    if (!in_array($ruleId, $dismissed, TRUE)) {
      $dismissed[] = $ruleId;
      $this->state->set($key, $dismissed);
    }

    $this->state->delete("agroconecta_proactive_pending_{$userId}");

    $this->logger->info('AgroConecta proactive rule @rule dismissed by user @uid', [
      '@rule' => $ruleId,
      '@uid' => $userId,
    ]);
  }

  /**
   * Evalua reglas en batch (para cron).
   *
   * @return int
   *   Numero de usuarios procesados.
   */
  public function evaluateBatch(): int {
    $processed = 0;
    try {
      $userIds = $this->entityTypeManager->getStorage('user')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 1)
        ->execute();

      foreach (array_slice(array_values($userIds), 0, 100) as $userId) {
        $this->getPendingAction((int) $userId);
        $processed++;
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('AgroConecta journey batch evaluation failed: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $processed;
  }

  /**
   * Verifica una condicion de regla.
   */
  protected function checkCondition(int $userId, string $condition): bool {
    return match ($condition) {
      'no_activity_3_days' => $this->checkNoActivity($userId, 3),
      'product_no_cover_photo' => $this->checkIncompleteCatalog($userId),
      'products_published_no_orders_7_days' => $this->checkFirstSaleNudge($userId),
      'unanswered_reviews_3_days' => $this->checkUnansweredReviews($userId),
      '5_sales_no_price_adjustment' => $this->checkPricingOptimization($userId),
      'starter_10_sales_no_qr' => $this->checkTraceabilityOpportunity($userId),
      'free_plan_80_percent_products' => $this->checkUpgradeSuggestion($userId),
      '50_sales_rating_4_5' => $this->checkB2bExpansion($userId),
      'cart_no_checkout_24h' => $this->checkCartAbandoned($userId),
      'last_order_30_days_ago' => $this->checkReorderNudge($userId),
      default => FALSE,
    };
  }

  /**
   * Sin actividad durante N dias.
   */
  protected function checkNoActivity(int $userId, int $days): bool {
    $stateKey = "agroconecta_last_activity_{$userId}";
    $lastActivity = (int) $this->state->get($stateKey, 0);
    if ($lastActivity === 0) {
      return FALSE;
    }
    $threshold = \Drupal::time()->getRequestTime() - ($days * 86400);
    return $lastActivity < $threshold;
  }

  /**
   * 1 producto sin foto de portada.
   */
  protected function checkIncompleteCatalog(int $userId): bool {
    try {
      $products = $this->entityTypeManager->getStorage('agro_product')
        ->loadByProperties(['owner_id' => $userId]);
      if (empty($products)) {
        return FALSE;
      }
      foreach ($products as $product) {
        $photo = $product->get('cover_photo')->entity ?? NULL;
        if ($photo === NULL) {
          return TRUE;
        }
      }
      return FALSE;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Productos publicados pero 0 pedidos en 7 dias.
   */
  protected function checkFirstSaleNudge(int $userId): bool {
    try {
      $products = $this->entityTypeManager->getStorage('agro_product')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('owner_id', $userId)
        ->condition('status', 1)
        ->count()
        ->execute();

      if ((int) $products === 0) {
        return FALSE;
      }

      $orders = $this->entityTypeManager->getStorage('agro_order')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('seller_id', $userId)
        ->count()
        ->execute();

      if ((int) $orders > 0) {
        return FALSE;
      }

      // Check first product was published at least 7 days ago.
      $firstProduct = $this->entityTypeManager->getStorage('agro_product')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('owner_id', $userId)
        ->condition('status', 1)
        ->sort('created', 'ASC')
        ->range(0, 1)
        ->execute();

      if (empty($firstProduct)) {
        return FALSE;
      }

      $product = $this->entityTypeManager->getStorage('agro_product')
        ->load(reset($firstProduct));
      $created = (int) ($product->get('created')->value ?? 0);
      $threshold = \Drupal::time()->getRequestTime() - (7 * 86400);
      return $created < $threshold;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Reviews sin responder durante >3 dias.
   */
  protected function checkUnansweredReviews(int $userId): bool {
    try {
      $reviews = $this->entityTypeManager->getStorage('agro_review')
        ->loadByProperties([
          'seller_id' => $userId,
          'response_status' => 'pending',
        ]);
      if (empty($reviews)) {
        return FALSE;
      }
      $threshold = \Drupal::time()->getRequestTime() - (3 * 86400);
      foreach ($reviews as $review) {
        $created = (int) ($review->get('created')->value ?? 0);
        if ($created < $threshold) {
          return TRUE;
        }
      }
      return FALSE;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * 5+ ventas sin ajuste de precio.
   */
  protected function checkPricingOptimization(int $userId): bool {
    try {
      $orderCount = (int) $this->entityTypeManager->getStorage('agro_order')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('seller_id', $userId)
        ->condition('status', 'completed')
        ->count()
        ->execute();

      if ($orderCount < 5) {
        return FALSE;
      }

      $stateKey = "agroconecta_last_price_adjustment_{$userId}";
      $lastAdjustment = (int) $this->state->get($stateKey, 0);
      return $lastAdjustment === 0;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Plan Starter, >10 ventas, sin QR trazabilidad.
   */
  protected function checkTraceabilityOpportunity(int $userId): bool {
    try {
      if (!\Drupal::hasService('ecosistema_jaraba_core.agroconecta_feature_gate')) {
        return FALSE;
      }
      $featureGate = \Drupal::service('ecosistema_jaraba_core.agroconecta_feature_gate');
      $plan = $featureGate->getUserPlan($userId);
      if ($plan !== 'starter') {
        return FALSE;
      }

      $orderCount = (int) $this->entityTypeManager->getStorage('agro_order')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('seller_id', $userId)
        ->condition('status', 'completed')
        ->count()
        ->execute();

      if ($orderCount < 10) {
        return FALSE;
      }

      $result = $featureGate->check($userId, 'traceability_qr');
      return ($result->used ?? 0) === 0;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Plan free con 4 productos (80% del limite de 5).
   */
  protected function checkUpgradeSuggestion(int $userId): bool {
    try {
      if (!\Drupal::hasService('ecosistema_jaraba_core.agroconecta_feature_gate')) {
        return FALSE;
      }
      $featureGate = \Drupal::service('ecosistema_jaraba_core.agroconecta_feature_gate');
      $plan = $featureGate->getUserPlan($userId);
      if ($plan !== 'free') {
        return FALSE;
      }

      $result = $featureGate->check($userId, 'products');
      if ($result->limit <= 0) {
        return FALSE;
      }
      return (($result->used ?? 0) / $result->limit) >= 0.8;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * >50 ventas completadas y rating >4.5.
   */
  protected function checkB2bExpansion(int $userId): bool {
    try {
      $orderCount = (int) $this->entityTypeManager->getStorage('agro_order')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('seller_id', $userId)
        ->condition('status', 'completed')
        ->count()
        ->execute();

      if ($orderCount < 50) {
        return FALSE;
      }

      $stateKey = "agroconecta_seller_rating_{$userId}";
      $rating = (float) $this->state->get($stateKey, 0);
      return $rating >= 4.5;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Carrito creado sin checkout en 24h.
   */
  protected function checkCartAbandoned(int $userId): bool {
    try {
      $carts = $this->entityTypeManager->getStorage('agro_cart')
        ->loadByProperties([
          'user_id' => $userId,
          'status' => 'active',
        ]);
      if (empty($carts)) {
        return FALSE;
      }
      $threshold = \Drupal::time()->getRequestTime() - 86400;
      foreach ($carts as $cart) {
        $created = (int) ($cart->get('created')->value ?? 0);
        if ($created > 0 && $created < $threshold) {
          return TRUE;
        }
      }
      return FALSE;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Ultimo pedido hace >30 dias.
   */
  protected function checkReorderNudge(int $userId): bool {
    try {
      $orders = $this->entityTypeManager->getStorage('agro_order')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('buyer_id', $userId)
        ->condition('status', 'completed')
        ->sort('created', 'DESC')
        ->range(0, 1)
        ->execute();

      if (empty($orders)) {
        return FALSE;
      }

      $order = $this->entityTypeManager->getStorage('agro_order')
        ->load(reset($orders));
      $created = (int) ($order->get('created')->value ?? 0);
      $threshold = \Drupal::time()->getRequestTime() - (30 * 86400);
      return $created < $threshold;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Obtiene reglas descartadas.
   */
  protected function getDismissedRules(int $userId): array {
    return $this->state->get("agroconecta_proactive_dismissed_{$userId}", []);
  }

}
