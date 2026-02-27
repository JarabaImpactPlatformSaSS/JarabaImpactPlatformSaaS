<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_ai_agents\Service\CausalAnalyticsService;
use Drupal\jaraba_ai_agents\Service\FederatedInsightService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * GAP-L5-H/I: Causal Analytics & Federated Insights Dashboard.
 *
 * Admin page showing federated insights, causal analyses, and analytics.
 */
class CausalAnalyticsDashboardController extends ControllerBase {

  /**
   * Constructor.
   */
  public function __construct(
    protected FederatedInsightService $federatedInsight,
    protected CausalAnalyticsService $causalAnalytics,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_ai_agents.federated_insight'),
      $container->get('jaraba_ai_agents.causal_analytics'),
    );
  }

  /**
   * Renders the causal analytics dashboard.
   *
   * @return array
   *   Render array.
   */
  public function dashboard(): array {
    $insights = $this->federatedInsight->getInsights('', 10);
    $analyses = $this->causalAnalytics->getRecentAnalyses('', 10);

    // Build insight summaries.
    $insightData = [];
    foreach ($insights as $insight) {
      $insightData[] = [
        'id' => $insight->id(),
        'type' => $insight->getInsightType(),
        'title' => $insight->getTitle(),
        'summary' => $insight->getSummary(),
        'contributing_tenants' => $insight->getContributingTenants(),
        'confidence_score' => round($insight->getConfidenceScore(), 2),
        'vertical' => $insight->get('vertical')->value ?? 'all',
        'meets_k_anonymity' => $insight->meetsKAnonymity(),
      ];
    }

    // Build analysis summaries.
    $analysisData = [];
    foreach ($analyses as $analysis) {
      $result = $analysis->getAnalysisResult();
      $analysisData[] = [
        'id' => $analysis->id(),
        'query' => $analysis->getQuery(),
        'query_type' => $analysis->getQueryType(),
        'summary' => $result['summary'] ?? '',
        'confidence_score' => round($analysis->getConfidenceScore(), 2),
        'cost' => round($analysis->getCost(), 4),
        'factors_count' => count($analysis->getCausalFactors()),
        'recommendations_count' => count($analysis->getRecommendations()),
      ];
    }

    return [
      '#theme' => 'causal_analytics_dashboard',
      '#insights' => $insightData,
      '#analyses' => $analysisData,
      '#k_anonymity_threshold' => FederatedInsightService::K_ANONYMITY_THRESHOLD,
    ];
  }

}
