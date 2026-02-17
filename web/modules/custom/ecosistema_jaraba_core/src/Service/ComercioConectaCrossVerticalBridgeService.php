<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\State\StateInterface;
use Psr\Log\LoggerInterface;

/**
 * Cross-vertical value bridges for the ComercioConecta vertical.
 *
 * Evaluates and presents bridges between ComercioConecta and other
 * SaaS verticals (Emprendimiento, Fiscal, Formacion, Empleabilidad,
 * AgroConecta) to maximize customer LTV.
 *
 * Bridges:
 * - emprendimiento: high_sales_and_growth — >50 sales AND rating >4.5 (priority 90)
 * - fiscal: pro_plan_high_volume — plan profesional/enterprise AND high volume (priority 85)
 * - formacion: milestone_100_sales — reached 100 sales (priority 70)
 * - empleabilidad: needs_team — high order volume suggesting need for help (priority 65)
 * - agroconecta: is_food_merchant — merchant category is food-related (priority 75)
 *
 * Plan Elevacion ComercioConecta Clase Mundial v1 — Fase 15.
 *
 * @see \Drupal\ecosistema_jaraba_core\Service\AgroConectaCrossVerticalBridgeService
 */
class ComercioConectaCrossVerticalBridgeService {

  /**
   * Bridge definitions keyed by bridge ID.
   */
  protected const BRIDGES = [
    'emprendimiento' => [
      'id' => 'comercio_to_emprendimiento',
      'vertical' => 'emprendimiento',
      'icon_category' => 'verticals',
      'icon_name' => 'emprendimiento',
      'color' => '#FF6B35',
      'message' => 'Tu negocio esta creciendo. Escala con herramientas de emprendimiento.',
      'cta_label' => 'Escala tu negocio',
      'cta_url' => '/emprendimiento/diagnostico',
      'condition' => 'high_sales_and_growth',
      'priority' => 90,
    ],
    'fiscal' => [
      'id' => 'comercio_to_fiscal',
      'vertical' => 'fiscal',
      'icon_category' => 'verticals',
      'icon_name' => 'fiscal',
      'color' => '#2563EB',
      'message' => 'Con tu volumen de ventas, gestiona tu facturacion de forma profesional.',
      'cta_label' => 'Gestiona tu facturacion',
      'cta_url' => '/fiscal/dashboard',
      'condition' => 'pro_plan_high_volume',
      'priority' => 85,
    ],
    'formacion' => [
      'id' => 'comercio_to_formacion',
      'vertical' => 'formacion',
      'icon_category' => 'verticals',
      'icon_name' => 'formacion',
      'color' => '#7C3AED',
      'message' => 'Felicidades por tus 100 ventas. Mejora tus habilidades de negocio.',
      'cta_label' => 'Mejora tus habilidades',
      'cta_url' => '/formacion/catalogo',
      'condition' => 'milestone_100_sales',
      'priority' => 70,
    ],
    'empleabilidad' => [
      'id' => 'comercio_to_empleabilidad',
      'vertical' => 'empleabilidad',
      'icon_category' => 'verticals',
      'icon_name' => 'empleabilidad',
      'color' => '#059669',
      'message' => 'Tu comercio necesita equipo. Publica ofertas de empleo.',
      'cta_label' => 'Contrata talento',
      'cta_url' => '/empleabilidad/publicar-oferta',
      'condition' => 'needs_team',
      'priority' => 65,
    ],
    'agroconecta' => [
      'id' => 'comercio_to_agroconecta',
      'vertical' => 'agroconecta',
      'icon_category' => 'verticals',
      'icon_name' => 'agroconecta',
      'color' => '#2E7D32',
      'message' => 'Vende productos frescos de productores locales en tu tienda.',
      'cta_label' => 'Vende productos frescos',
      'cta_url' => '/agroconecta',
      'condition' => 'is_food_merchant',
      'priority' => 75,
    ],
  ];

  /**
   * Constructor.
   */
  public function __construct(
    protected readonly StateInterface $state,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Evaluates available bridges for a user based on their current state.
   *
   * @return array
   *   List of bridges (max 2) with: id, vertical, message, cta_url,
   *   cta_label, icon_category, icon_name, color, priority.
   */
  public function evaluateBridges(int $userId): array {
    $bridges = [];

    foreach (self::BRIDGES as $bridgeId => $bridge) {
      if ($this->evaluateCondition($userId, $bridge['condition'])) {
        $dismissed = $this->getDismissedBridges($userId);
        if (in_array($bridgeId, $dismissed, TRUE)) {
          continue;
        }
        $bridges[] = $bridge;
      }
    }

    usort($bridges, fn(array $a, array $b) => $b['priority'] <=> $a['priority']);

    return array_slice($bridges, 0, 2);
  }

  /**
   * Presents a bridge to the user and tracks impression.
   */
  public function presentBridge(int $userId, string $bridgeKey): ?array {
    $bridge = self::BRIDGES[$bridgeKey] ?? NULL;
    if (!$bridge) {
      return NULL;
    }

    $key = "comercioconecta_bridge_impressions_{$userId}";
    $impressions = $this->state->get($key, []);
    $impressions[] = [
      'bridge_id' => $bridgeKey,
      'timestamp' => \Drupal::time()->getRequestTime(),
    ];
    $this->state->set($key, array_slice($impressions, -50));

    return $bridge;
  }

  /**
   * Tracks user response to a bridge (accepted or dismissed).
   */
  public function trackBridgeResponse(int $userId, string $bridgeKey, string $action): void {
    $key = "comercioconecta_bridge_responses_{$userId}";
    $responses = $this->state->get($key, []);
    $responses[] = [
      'bridge_id' => $bridgeKey,
      'action' => $action,
      'timestamp' => \Drupal::time()->getRequestTime(),
    ];
    $this->state->set($key, array_slice($responses, -100));

    if ($action === 'dismissed') {
      $dismissedKey = "comercioconecta_bridge_dismissed_{$userId}";
      $dismissed = $this->state->get($dismissedKey, []);
      if (!in_array($bridgeKey, $dismissed, TRUE)) {
        $dismissed[] = $bridgeKey;
        $this->state->set($dismissedKey, $dismissed);
      }
    }

    $this->logger->info('Bridge @bridge @action by user @uid', [
      '@bridge' => $bridgeKey,
      '@action' => $action,
      '@uid' => $userId,
    ]);
  }

  /**
   * Evaluates a single bridge condition.
   */
  protected function evaluateCondition(int $userId, string $condition): bool {
    return match ($condition) {
      'high_sales_and_growth' => $this->checkHighSalesAndGrowth($userId),
      'pro_plan_high_volume' => $this->checkProPlanHighVolume($userId),
      'milestone_100_sales' => $this->checkMilestone100Sales($userId),
      'needs_team' => $this->checkNeedsTeam($userId),
      'is_food_merchant' => $this->checkIsFoodMerchant($userId),
      default => FALSE,
    };
  }

  /**
   * Checks if merchant has >50 total sales AND rating >4.5.
   */
  protected function checkHighSalesAndGrowth(int $userId): bool {
    try {
      $storage = \Drupal::entityTypeManager()
        ->getStorage('comercioconecta_merchant');
      $merchants = $storage->loadByProperties(['user_id' => $userId]);

      if (empty($merchants)) {
        return FALSE;
      }

      $merchant = reset($merchants);
      $totalSales = (int) ($merchant->get('total_sales')->value ?? 0);
      $avgRating = (float) ($merchant->get('avg_rating')->value ?? 0);

      return $totalSales > 50 && $avgRating > 4.5;
    }
    catch (\Exception $e) {
      $this->logger->warning('Error checking high_sales_and_growth for user @uid: @error', [
        '@uid' => $userId,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Checks if merchant has plan profesional or enterprise AND high volume.
   */
  protected function checkProPlanHighVolume(int $userId): bool {
    try {
      $storage = \Drupal::entityTypeManager()
        ->getStorage('comercioconecta_merchant');
      $merchants = $storage->loadByProperties(['user_id' => $userId]);

      if (empty($merchants)) {
        return FALSE;
      }

      $merchant = reset($merchants);
      $plan = $merchant->get('plan')->value ?? 'gratuito';
      $monthlySales = (int) ($merchant->get('monthly_sales')->value ?? 0);

      return in_array($plan, ['profesional', 'enterprise'], TRUE) && $monthlySales > 20;
    }
    catch (\Exception $e) {
      $this->logger->warning('Error checking pro_plan_high_volume for user @uid: @error', [
        '@uid' => $userId,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Checks if merchant has exactly or recently reached 100 total sales.
   */
  protected function checkMilestone100Sales(int $userId): bool {
    try {
      $storage = \Drupal::entityTypeManager()
        ->getStorage('comercioconecta_merchant');
      $merchants = $storage->loadByProperties(['user_id' => $userId]);

      if (empty($merchants)) {
        return FALSE;
      }

      $merchant = reset($merchants);
      $totalSales = (int) ($merchant->get('total_sales')->value ?? 0);

      return $totalSales >= 100 && $totalSales <= 120;
    }
    catch (\Exception $e) {
      $this->logger->warning('Error checking milestone_100_sales for user @uid: @error', [
        '@uid' => $userId,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Checks if merchant has high order volume suggesting need for team help.
   */
  protected function checkNeedsTeam(int $userId): bool {
    try {
      $storage = \Drupal::entityTypeManager()
        ->getStorage('comercioconecta_merchant');
      $merchants = $storage->loadByProperties(['user_id' => $userId]);

      if (empty($merchants)) {
        return FALSE;
      }

      $merchant = reset($merchants);
      $monthlyOrders = (int) ($merchant->get('monthly_orders')->value ?? 0);
      $teamSize = (int) ($merchant->get('team_size')->value ?? 0);

      return $monthlyOrders > 100 && $teamSize < 2;
    }
    catch (\Exception $e) {
      $this->logger->warning('Error checking needs_team for user @uid: @error', [
        '@uid' => $userId,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Checks if merchant category is food-related.
   */
  protected function checkIsFoodMerchant(int $userId): bool {
    try {
      $storage = \Drupal::entityTypeManager()
        ->getStorage('comercioconecta_merchant');
      $merchants = $storage->loadByProperties(['user_id' => $userId]);

      if (empty($merchants)) {
        return FALSE;
      }

      $merchant = reset($merchants);
      $category = $merchant->get('category')->value ?? '';

      $foodCategories = [
        'alimentacion',
        'restauracion',
        'hosteleria',
        'supermercado',
        'fruteria',
        'carniceria',
        'panaderia',
        'pescaderia',
        'bar_cafeteria',
        'catering',
      ];

      return in_array($category, $foodCategories, TRUE);
    }
    catch (\Exception $e) {
      $this->logger->warning('Error checking is_food_merchant for user @uid: @error', [
        '@uid' => $userId,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Gets list of dismissed bridge IDs for a user.
   */
  protected function getDismissedBridges(int $userId): array {
    return $this->state->get("comercioconecta_bridge_dismissed_{$userId}", []);
  }

}
