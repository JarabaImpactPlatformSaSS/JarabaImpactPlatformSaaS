<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Psr\Log\LoggerInterface;

/**
 * Journey progression service para el vertical ComercioConecta.
 *
 * Evalua reglas proactivas que determinan cuando y como el copiloto
 * debe intervenir para guiar al usuario en su recorrido marketplace.
 * Las reglas se basan en el estado del journey y la actividad del usuario.
 *
 * Reglas proactivas (8 merchant + 2 consumer):
 * - inactivity_discovery: Sin actividad 3 dias (merchant, discovery)
 * - incomplete_catalog: Menos de 3 productos (merchant, activation)
 * - first_sale_nudge: 0 ventas (merchant, activation)
 * - review_response: Resenas sin responder (merchant, engagement)
 * - pricing_optimization: Precios fuera de rango (merchant, engagement)
 * - flash_offer_opportunity: Stock alto sin oferta (merchant, engagement)
 * - upgrade_suggestion: Plan free con 8+ productos (merchant, conversion)
 * - pos_integration: Mas de 50 ventas sin TPV (merchant, retention)
 * - cart_abandoned: Carrito inactivo 1h (buyer, engagement)
 * - reorder_nudge: Sin compra 30 dias (buyer, retention)
 *
 * Plan Elevacion ComercioConecta Clase Mundial v1 — Fase 16.
 *
 * @see \Drupal\ecosistema_jaraba_core\Service\AgroConectaJourneyProgressionService
 */
class ComercioConectaJourneyProgressionService {

  /**
   * Vertical identifier.
   */
  protected const VERTICAL = 'comercioconecta';

  /**
   * Reglas proactivas de intervencion.
   */
  protected const PROACTIVE_RULES = [
    // =============================================
    // MERCHANT RULES (8).
    // =============================================
    'inactivity_discovery' => [
      'state' => 'discovery',
      'role' => 'merchant',
      'condition' => 'no_activity_3_days',
      'message' => 'Tu tienda esta casi lista. Sube tu primer producto para empezar a vender.',
      'cta_label' => 'Añadir producto',
      'cta_url' => '/mi-comercio/productos/nuevo',
      'channel' => 'copilot_fab',
      'mode' => 'nudge',
      'priority' => 90,
    ],
    'incomplete_catalog' => [
      'state' => 'activation',
      'role' => 'merchant',
      'condition' => 'less_than_3_products',
      'message' => 'Un catalogo variado atrae mas clientes. Añade al menos 3 productos para destacar.',
      'cta_label' => 'Completar catalogo',
      'cta_url' => '/mi-comercio/productos/nuevo',
      'channel' => 'copilot_fab',
      'mode' => 'nudge',
      'priority' => 85,
    ],
    'first_sale_nudge' => [
      'state' => 'activation',
      'role' => 'merchant',
      'condition' => 'zero_sales',
      'message' => 'Comparte tu tienda en redes sociales para conseguir tu primera venta.',
      'cta_label' => 'Compartir tienda',
      'cta_url' => '/mi-comercio/compartir',
      'channel' => 'copilot_fab',
      'mode' => 'nudge',
      'priority' => 80,
    ],
    'review_response' => [
      'state' => 'engagement',
      'role' => 'merchant',
      'condition' => 'unanswered_reviews',
      'message' => 'Tienes reseñas sin responder. Responde para mejorar tu reputacion.',
      'cta_label' => 'Ver reseñas',
      'cta_url' => '/mi-comercio/resenas',
      'channel' => 'copilot_fab',
      'mode' => 'nudge',
      'priority' => 75,
    ],
    'pricing_optimization' => [
      'state' => 'engagement',
      'role' => 'merchant',
      'condition' => 'prices_out_of_range',
      'message' => 'Algunos de tus precios estan fuera del rango del mercado. Optimizalos para vender mas.',
      'cta_label' => 'Revisar precios',
      'cta_url' => '/mi-comercio/productos',
      'channel' => 'copilot_fab',
      'mode' => 'suggestion',
      'priority' => 70,
    ],
    'flash_offer_opportunity' => [
      'state' => 'engagement',
      'role' => 'merchant',
      'condition' => 'high_stock_items',
      'message' => 'Tienes productos con stock alto. Crea una oferta flash para impulsarlos.',
      'cta_label' => 'Crear oferta flash',
      'cta_url' => '/mi-comercio/ofertas/nueva',
      'channel' => 'copilot_fab',
      'mode' => 'suggestion',
      'priority' => 65,
    ],
    'upgrade_suggestion' => [
      'state' => 'conversion',
      'role' => 'merchant',
      'condition' => 'free_with_8_plus_products',
      'message' => 'Estas cerca del limite de tu plan gratuito. Desbloquea productos ilimitados.',
      'cta_label' => 'Ver planes',
      'cta_url' => '/upgrade?vertical=comercioconecta',
      'channel' => 'copilot_fab',
      'mode' => 'upsell',
      'priority' => 95,
    ],
    'pos_integration' => [
      'state' => 'retention',
      'role' => 'merchant',
      'condition' => 'more_than_50_sales',
      'message' => 'Con mas de 50 ventas, conecta tu TPV para sincronizar inventario automaticamente.',
      'cta_label' => 'Conectar TPV',
      'cta_url' => '/mi-comercio/integraciones/tpv',
      'channel' => 'copilot_fab',
      'mode' => 'suggestion',
      'priority' => 60,
    ],
    // =============================================
    // CONSUMER RULES (2).
    // =============================================
    'cart_abandoned' => [
      'state' => 'engagement',
      'role' => 'buyer',
      'condition' => 'cart_inactive_1h',
      'message' => 'Tienes productos esperandote en tu carrito. Completa tu compra antes de que se agoten.',
      'cta_label' => 'Ver carrito',
      'cta_url' => '/comercio-local/carrito',
      'channel' => 'copilot_fab',
      'mode' => 'nudge',
      'priority' => 85,
    ],
    'reorder_nudge' => [
      'state' => 'retention',
      'role' => 'buyer',
      'condition' => 'no_purchase_30_days',
      'message' => 'Hace un mes que no compras. Descubre las novedades de tus comercios favoritos.',
      'cta_label' => 'Explorar tiendas',
      'cta_url' => '/comercio-local',
      'channel' => 'copilot_fab',
      'mode' => 'nudge',
      'priority' => 55,
    ],
  ];

  /**
   * Constructor.
   */
  public function __construct(
    protected readonly StateInterface $state,
    protected readonly LoggerInterface $logger,
    protected readonly TimeInterface $time,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ?object $comercioconectaFeatureGate = NULL,
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
    uasort($sortedRules, fn(array $a, array $b) => $b['priority'] <=> $a['priority']);

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
    $stateKey = "comercioconecta_proactive_pending_{$userId}";
    $cached = $this->state->get($stateKey);

    if ($cached) {
      $age = $this->time->getRequestTime() - ($cached['evaluated_at'] ?? 0);
      if ($age < 3600) {
        return $cached['action'];
      }
    }

    $action = $this->evaluate($userId);
    $this->state->set($stateKey, [
      'action' => $action,
      'evaluated_at' => $this->time->getRequestTime(),
    ]);

    return $action;
  }

  /**
   * Descarta una regla para un usuario.
   */
  public function dismissAction(int $userId, string $ruleKey): void {
    $key = "comercioconecta_proactive_dismissed_{$userId}";
    $dismissed = $this->state->get($key, []);
    if (!in_array($ruleKey, $dismissed, TRUE)) {
      $dismissed[] = $ruleKey;
      $this->state->set($key, $dismissed);
    }

    $this->state->delete("comercioconecta_proactive_pending_{$userId}");

    $this->logger->info('ComercioConecta proactive rule @rule dismissed by user @uid', [
      '@rule' => $ruleKey,
      '@uid' => $userId,
    ]);
  }

  /**
   * Evalua reglas en batch (para cron).
   *
   * @param array $userIds
   *   Array de IDs de usuario a evaluar.
   *
   * @return array
   *   Array asociativo userId => resultado de evaluacion.
   */
  public function evaluateBatch(array $userIds): array {
    $results = [];
    try {
      foreach (array_slice($userIds, 0, 100) as $userId) {
        $results[(int) $userId] = $this->getPendingAction((int) $userId);
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('ComercioConecta journey batch evaluation failed: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $results;
  }

  /**
   * Verifica una condicion de regla.
   */
  protected function checkCondition(int $userId, string $condition): bool {
    return match ($condition) {
      'no_activity_3_days' => $this->checkNoActivity3Days($userId),
      'less_than_3_products' => $this->checkLessThan3Products($userId),
      'zero_sales' => $this->checkZeroSales($userId),
      'unanswered_reviews' => $this->checkUnansweredReviews($userId),
      'prices_out_of_range' => $this->checkPricesOutOfRange($userId),
      'high_stock_items' => $this->checkHighStockItems($userId),
      'free_with_8_plus_products' => $this->checkFreeWith8PlusProducts($userId),
      'more_than_50_sales' => $this->checkMoreThan50Sales($userId),
      'cart_inactive_1h' => $this->checkCartInactive1h($userId),
      'no_purchase_30_days' => $this->checkNoPurchase30Days($userId),
      default => FALSE,
    };
  }

  /**
   * Sin actividad durante 3 dias.
   */
  protected function checkNoActivity3Days(int $userId): bool {
    try {
      $stateKey = "comercioconecta_last_activity_{$userId}";
      $lastActivity = (int) $this->state->get($stateKey, 0);
      if ($lastActivity === 0) {
        return FALSE;
      }
      $threshold = $this->time->getRequestTime() - (3 * 86400);
      return $lastActivity < $threshold;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Menos de 3 productos publicados.
   */
  protected function checkLessThan3Products(int $userId): bool {
    try {
      $productCount = (int) $this->entityTypeManager
        ->getStorage('comercio_product')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('owner_id', $userId)
        ->condition('status', 1)
        ->count()
        ->execute();

      return $productCount > 0 && $productCount < 3;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Cero ventas completadas.
   */
  protected function checkZeroSales(int $userId): bool {
    try {
      $products = (int) $this->entityTypeManager
        ->getStorage('comercio_product')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('owner_id', $userId)
        ->condition('status', 1)
        ->count()
        ->execute();

      if ($products === 0) {
        return FALSE;
      }

      $orders = (int) $this->entityTypeManager
        ->getStorage('comercio_order')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('seller_id', $userId)
        ->condition('status', 'completed')
        ->count()
        ->execute();

      return $orders === 0;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Resenas sin responder.
   */
  protected function checkUnansweredReviews(int $userId): bool {
    try {
      $unanswered = (int) $this->entityTypeManager
        ->getStorage('comercio_review')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('seller_id', $userId)
        ->condition('response_status', 'pending')
        ->count()
        ->execute();

      return $unanswered > 0;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Precios fuera del rango de mercado.
   */
  protected function checkPricesOutOfRange(int $userId): bool {
    try {
      $stateKey = "comercioconecta_prices_out_of_range_{$userId}";
      $outOfRange = (int) $this->state->get($stateKey, 0);
      return $outOfRange > 0;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Productos con stock alto sin oferta flash activa.
   */
  protected function checkHighStockItems(int $userId): bool {
    try {
      $stateKey = "comercioconecta_high_stock_items_{$userId}";
      $highStock = (int) $this->state->get($stateKey, 0);
      return $highStock > 0;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Plan free con 8 o mas productos (cerca del limite).
   */
  protected function checkFreeWith8PlusProducts(int $userId): bool {
    try {
      if (!$this->comercioconectaFeatureGate) {
        return FALSE;
      }
      $plan = $this->comercioconectaFeatureGate->getUserPlan($userId);
      if ($plan !== 'free') {
        return FALSE;
      }

      $result = $this->comercioconectaFeatureGate->check($userId, 'products');
      if ($result->limit <= 0) {
        return FALSE;
      }
      return ($result->used ?? 0) >= 8;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Mas de 50 ventas completadas sin TPV conectado.
   */
  protected function checkMoreThan50Sales(int $userId): bool {
    try {
      $orderCount = (int) $this->entityTypeManager
        ->getStorage('comercio_order')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('seller_id', $userId)
        ->condition('status', 'completed')
        ->count()
        ->execute();

      if ($orderCount < 50) {
        return FALSE;
      }

      $stateKey = "comercioconecta_pos_connected_{$userId}";
      $posConnected = (bool) $this->state->get($stateKey, FALSE);
      return !$posConnected;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Carrito inactivo durante mas de 1 hora.
   */
  protected function checkCartInactive1h(int $userId): bool {
    try {
      $stateKey = "comercioconecta_cart_updated_{$userId}";
      $cartUpdated = (int) $this->state->get($stateKey, 0);
      if ($cartUpdated === 0) {
        return FALSE;
      }
      $threshold = $this->time->getRequestTime() - 3600;
      return $cartUpdated < $threshold;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Sin compra durante mas de 30 dias.
   */
  protected function checkNoPurchase30Days(int $userId): bool {
    try {
      $orders = $this->entityTypeManager
        ->getStorage('comercio_order')
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

      $order = $this->entityTypeManager
        ->getStorage('comercio_order')
        ->load(reset($orders));
      $created = (int) ($order->get('created')->value ?? 0);
      $threshold = $this->time->getRequestTime() - (30 * 86400);
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
    return $this->state->get("comercioconecta_proactive_dismissed_{$userId}", []);
  }

}
