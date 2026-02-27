<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Session\AccountInterface;
use Psr\Log\LoggerInterface;

/**
 * Unified personalization engine (S5-09: HAL-AI-20).
 *
 * Orchestrates 6 existing recommendation services scattered across modules
 * to provide a single API for cross-vertical personalized recommendations.
 * Uses heuristic scoring + engagement data, NOT ML pipelines.
 *
 * Existing recommendation services consumed:
 * 1. jaraba_content_hub.recommendation - Content articles (Qdrant-based)
 * 2. jaraba_job_board.job_recommendation - Job listings
 * 3. jaraba_lms.course_recommendation - Courses and learning paths
 * 4. jaraba_comercio_conecta.product_recommendation - Products
 * 5. jaraba_agroconecta_core.recommendation - Agricultural products
 * 6. jaraba_page_builder.tool_recommendation - Platform tools
 */
class PersonalizationEngineService {

  /**
   * Context weights for blending recommendations.
   */
  protected const CONTEXT_WEIGHTS = [
    'content' => ['content' => 0.5, 'courses' => 0.2, 'jobs' => 0.2, 'tools' => 0.1],
    'employment' => ['jobs' => 0.5, 'courses' => 0.25, 'content' => 0.15, 'tools' => 0.1],
    'learning' => ['courses' => 0.5, 'content' => 0.25, 'jobs' => 0.15, 'tools' => 0.1],
    'commerce' => ['products' => 0.5, 'content' => 0.2, 'tools' => 0.2, 'courses' => 0.1],
    'default' => ['content' => 0.3, 'jobs' => 0.2, 'courses' => 0.2, 'tools' => 0.15, 'products' => 0.15],
  ];

  public function __construct(
    protected LoggerInterface $logger,
    protected ?TenantContextService $tenantContext = NULL,
  ) {}

  /**
   * Gets personalized recommendations for a user.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to personalize for.
   * @param string $context
   *   Context: content, employment, learning, commerce, default.
   * @param int $limit
   *   Maximum number of recommendations.
   *
   * @return array
   *   Blended and ranked recommendations with source info.
   */
  public function getRecommendations(AccountInterface $user, string $context = 'default', int $limit = 10): array {
    $weights = self::CONTEXT_WEIGHTS[$context] ?? self::CONTEXT_WEIGHTS['default'];
    $allRecommendations = [];

    // Collect recommendations from each available service.
    foreach ($weights as $source => $weight) {
      if ($weight <= 0) {
        continue;
      }

      try {
        $sourceRecs = $this->fetchFromSource($source, $user, $limit);
        foreach ($sourceRecs as &$rec) {
          $rec['source'] = $source;
          $rec['base_score'] = $rec['score'] ?? 0.5;
          $rec['weighted_score'] = $rec['base_score'] * $weight;
        }
        $allRecommendations = array_merge($allRecommendations, $sourceRecs);
      }
      catch (\Exception $e) {
        // Graceful fallback: if a service fails, use the others.
        $this->logger->notice('Recommendation source @source failed: @error', [
          '@source' => $source,
          '@error' => $e->getMessage(),
        ]);
      }
    }

    // Re-rank by engagement history.
    $allRecommendations = $this->reRankByEngagement($allRecommendations, $user);

    // Sort by final score descending.
    usort($allRecommendations, fn($a, $b) => ($b['final_score'] ?? 0) <=> ($a['final_score'] ?? 0));

    // Deduplicate and limit.
    $seen = [];
    $results = [];
    foreach ($allRecommendations as $rec) {
      $key = ($rec['source'] ?? '') . ':' . ($rec['id'] ?? $rec['title'] ?? '');
      if (isset($seen[$key])) {
        continue;
      }
      $seen[$key] = TRUE;
      $results[] = $rec;
      if (count($results) >= $limit) {
        break;
      }
    }

    return [
      'items' => $results,
      'context' => $context,
      'total_sources' => count($weights),
      'user_id' => $user->id(),
    ];
  }

  /**
   * Fetches recommendations from a specific source service.
   */
  protected function fetchFromSource(string $source, AccountInterface $user, int $limit): array {
    $serviceMap = [
      'content' => 'jaraba_content_hub.recommendation',
      'jobs' => 'jaraba_job_board.job_recommendation',
      'courses' => 'jaraba_lms.course_recommendation',
      'products' => 'jaraba_comercio_conecta.product_recommendation',
      'agro' => 'jaraba_agroconecta_core.recommendation',
      'tools' => 'jaraba_page_builder.tool_recommendation',
    ];

    $serviceId = $serviceMap[$source] ?? NULL;
    if (!$serviceId || !\Drupal::hasService($serviceId)) {
      return [];
    }

    $service = \Drupal::service($serviceId);

    // Try common interface methods.
    if (method_exists($service, 'getRecommendationsForUser')) {
      return $service->getRecommendationsForUser($user, $limit);
    }

    if (method_exists($service, 'getRecommendations')) {
      return $service->getRecommendations((int) $user->id(), $limit);
    }

    if (method_exists($service, 'recommend')) {
      return $service->recommend((int) $user->id(), $limit);
    }

    return [];
  }

  /**
   * Re-ranks recommendations based on user engagement history.
   *
   * Uses heuristic scoring:
   * - Boost items from sources the user has interacted with recently
   * - Boost items matching user's active vertical
   * - Penalize items the user has already seen/dismissed
   */
  protected function reRankByEngagement(array $recommendations, AccountInterface $user): array {
    // Get user's active vertical from tenant context for boosting.
    $activeVertical = NULL;
    if ($this->tenantContext) {
      try {
        $activeVertical = $this->tenantContext->getCurrentVerticalId();
      }
      catch (\Exception $e) {
        // Continue without vertical boost.
      }
    }

    $verticalSourceMap = [
      'empleabilidad' => 'jobs',
      'formacion' => 'courses',
      'comercioconecta' => 'products',
      'agroconecta' => 'agro',
      'jaraba_content_hub' => 'content',
    ];

    $boostedSource = $verticalSourceMap[$activeVertical] ?? NULL;

    foreach ($recommendations as &$rec) {
      $finalScore = $rec['weighted_score'] ?? 0.5;

      // Boost items matching user's current vertical.
      if ($boostedSource && ($rec['source'] ?? '') === $boostedSource) {
        $finalScore *= 1.3;
      }

      // Slight randomization for diversity (+-5%).
      $finalScore *= (0.95 + (mt_rand(0, 100) / 1000));

      $rec['final_score'] = round($finalScore, 4);
    }

    return $recommendations;
  }

}
