<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Copilot bridge service para el vertical ComercioConecta.
 *
 * Provee contexto relevante del comerciante al copiloto IA para
 * respuestas mas precisas y sugerencias proactivas de upgrade.
 *
 * Plan Elevacion ComercioConecta Clase Mundial v1 — Fase 13.
 *
 * Contexto inyectado:
 * - vertical, user_plan, active_products, orders_last_30,
 *   pending_reviews, remaining_copilot_uses, flash_offers_active
 *
 * @see \Drupal\jaraba_ai_agents\Agent\BaseAgent
 */
class ComercioConectaCopilotBridgeService {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountProxyInterface $currentUser,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Obtiene contexto relevante del comerciante para el copiloto.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return array
   *   Array con vertical, user_plan, active_products, orders_last_30,
   *   pending_reviews, remaining_copilot_uses, flash_offers_active.
   */
  public function getRelevantContext(int $userId): array {
    $context = [
      'vertical' => 'comercioconecta',
      'user_plan' => 'free',
      'active_products' => 0,
      'orders_last_30' => 0,
      'pending_reviews' => 0,
      'remaining_copilot_uses' => 5,
      'flash_offers_active' => 0,
    ];

    // Resolve plan.
    try {
      if (\Drupal::hasService('ecosistema_jaraba_core.comercioconecta_feature_gate')) {
        $featureGate = \Drupal::service('ecosistema_jaraba_core.comercioconecta_feature_gate');
        $context['user_plan'] = $featureGate->getUserPlan($userId);
        $context['remaining_copilot_uses'] = $featureGate->getRemainingUsage($userId, 'copilot_uses_per_month');
      }
    }
    catch (\Exception $e) {
      // Feature gate not available.
    }

    // Active products count.
    try {
      $count = $this->entityTypeManager->getStorage('product_retail')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->condition('status', 'active')
        ->count()
        ->execute();
      $context['active_products'] = (int) $count;
    }
    catch (\Exception $e) {
      // Entity may not exist yet.
    }

    // Orders last 30 days.
    try {
      if ($this->entityTypeManager->hasDefinition('order_retail')) {
        $thirtyDaysAgo = \Drupal::time()->getRequestTime() - (30 * 86400);
        $count = $this->entityTypeManager->getStorage('order_retail')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('merchant_id', $userId)
          ->condition('created', $thirtyDaysAgo, '>=')
          ->count()
          ->execute();
        $context['orders_last_30'] = (int) $count;
      }
    }
    catch (\Exception $e) {
      // Entity may not exist yet.
    }

    return $context;
  }

  /**
   * Genera sugerencia suave de upgrade si aplica.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return array|null
   *   Array con message y cta_url, o NULL si no aplica.
   */
  public function getSoftSuggestion(int $userId): ?array {
    try {
      if (!\Drupal::hasService('ecosistema_jaraba_core.comercioconecta_feature_gate')) {
        return NULL;
      }

      $featureGate = \Drupal::service('ecosistema_jaraba_core.comercioconecta_feature_gate');
      $plan = $featureGate->getUserPlan($userId);

      if ($plan !== 'free') {
        return NULL;
      }

      $result = $featureGate->check($userId, 'products');
      if ($result->limit <= 0) {
        return NULL;
      }

      $usageRatio = ($result->used ?? 0) / $result->limit;
      if ($usageRatio >= 0.8) {
        return [
          'message' => 'Estas cerca del limite de productos en tu plan gratuito. Con Starter publicas sin limite.',
          'cta_url' => '/upgrade?vertical=comercioconecta&source=copilot',
        ];
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('Error in getSoftSuggestion: @error', ['@error' => $e->getMessage()]);
    }

    return NULL;
  }

  /**
   * Obtiene insights del marketplace para contexto del copiloto.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return array
   *   Array con marketplace_products, user_products, market_share_pct.
   */
  public function getMarketInsights(int $userId): array {
    $insights = [
      'marketplace_products' => 0,
      'user_products' => 0,
      'market_share_pct' => 0.0,
    ];

    try {
      $totalProducts = (int) $this->entityTypeManager->getStorage('product_retail')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 'active')
        ->count()
        ->execute();
      $insights['marketplace_products'] = $totalProducts;

      $userProducts = (int) $this->entityTypeManager->getStorage('product_retail')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->condition('status', 'active')
        ->count()
        ->execute();
      $insights['user_products'] = $userProducts;

      if ($totalProducts > 0) {
        $insights['market_share_pct'] = round(($userProducts / $totalProducts) * 100, 1);
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('Error in getMarketInsights: @error', ['@error' => $e->getMessage()]);
    }

    return $insights;
  }

}
