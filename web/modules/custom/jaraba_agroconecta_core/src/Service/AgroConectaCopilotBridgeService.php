<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\AgroConectaFeatureGateService;
use Psr\Log\LoggerInterface;

/**
 * Servicio puente entre el Copilot central y el contexto AgroConecta.
 *
 * Inyecta datos de marketplace, catalogo y metricas del productor
 * en las respuestas del copilot para enriquecer la experiencia.
 *
 * Plan Elevacion AgroConecta Clase Mundial v1 — Fase 2
 */
class AgroConectaCopilotBridgeService {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AgroConectaFeatureGateService $featureGate,
    protected readonly LoggerInterface $logger,
  ) {
  }

  /**
   * Obtiene contexto relevante del productor para el copilot.
   */
  public function getRelevantContext(int $userId): array {
    $context = [
      'vertical' => 'agroconecta',
      'user_plan' => $this->featureGate->getUserPlan($userId),
    ];

    try {
      // Productos del usuario.
      $productStorage = $this->entityTypeManager->getStorage('product_agro');
      $productIds = $productStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->condition('status', 1)
        ->count()
        ->execute();
      $context['active_products'] = (int) $productIds;

      // Pedidos recientes (ultimos 30 dias).
      $orderStorage = $this->entityTypeManager->getStorage('suborder_agro');
      $thirtyDaysAgo = strtotime('-30 days');
      $orderCount = $orderStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('producer_id', $userId)
        ->condition('created', $thirtyDaysAgo, '>=')
        ->count()
        ->execute();
      $context['orders_last_30_days'] = (int) $orderCount;

      // Resenas sin responder.
      $reviewStorage = $this->entityTypeManager->getStorage('review_agro');
      $unansweredReviews = $reviewStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('producer_id', $userId)
        ->condition('response', NULL, 'IS NULL')
        ->count()
        ->execute();
      $context['unanswered_reviews'] = (int) $unansweredReviews;

      // Remaining feature usage.
      $context['remaining_copilot_uses'] = $this->featureGate->getRemainingUsage($userId, 'copilot_uses_per_month');
      $context['remaining_products'] = $this->featureGate->getRemainingUsage($userId, 'products');

    }
    catch (\Exception $e) {
      $this->logger->warning('Error building copilot context for user @user: @error', [
        '@user' => $userId,
        '@error' => $e->getMessage(),
      ]);
    }

    return $context;
  }

  /**
   * Genera una sugerencia suave de upgrade si aplica.
   */
  public function getSoftSuggestion(int $userId): ?array {
    $plan = $this->featureGate->getUserPlan($userId);
    if ($plan !== 'free') {
      return NULL;
    }

    try {
      $productCount = (int) $this->entityTypeManager->getStorage('product_agro')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->condition('status', 1)
        ->count()
        ->execute();

      // Si tiene 4+ productos (80% del limite free de 5).
      if ($productCount >= 4) {
        return [
          'plan' => 'starter',
          'message' => 'Con Starter tendrias 25 productos, 50 pedidos/mes y comision del 10%.',
          'trigger' => 'agro_products_limit_reached',
        ];
      }
    }
    catch (\Exception $e) {
      // Fail silently.
    }

    return NULL;
  }

  /**
   * Obtiene insights de mercado para el copilot.
   */
  public function getMarketInsights(int $userId): array {
    $insights = [];

    try {
      $productStorage = $this->entityTypeManager->getStorage('product_agro');

      // Total de productos en el marketplace.
      $totalProducts = (int) $productStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 1)
        ->count()
        ->execute();
      $insights['total_marketplace_products'] = $totalProducts;

      // Productos del usuario.
      $userProducts = (int) $productStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->condition('status', 1)
        ->count()
        ->execute();
      $insights['user_products'] = $userProducts;
      $insights['market_share_pct'] = $totalProducts > 0
        ? round(($userProducts / $totalProducts) * 100, 1)
        : 0;
    }
    catch (\Exception $e) {
      $this->logger->warning('Error building market insights: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $insights;
  }

}
