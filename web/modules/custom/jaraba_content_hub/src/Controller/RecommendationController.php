<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_content_hub\Service\RecommendationService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;

/**
 * Controller for article recommendation API endpoints.
 */
class RecommendationController extends ControllerBase
{

    /**
     * Recommendation service.
     */
    protected RecommendationService $recommendationService;

    /**
     * Constructor.
     */
    public function __construct(RecommendationService $recommendation_service)
    {
        $this->recommendationService = $recommendation_service;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_content_hub.recommendation_service'),
            $container->get('ecosistema_jaraba_core.tenant_context'), // AUDIT-CONS-N10: Proper DI for tenant context.
        );
    }

    /**
     * Gets related articles for a given article.
     *
     * @param int $article_id
     *   The article ID.
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The request object.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON response with related articles.
     */
    public function getRelated(int $article_id, Request $request): JsonResponse
    {
        $limit = (int) $request->query->get('limit', 5);
        $limit = min(max($limit, 1), 20);

        $tenantId = 0;
        if ($this->tenantContext !== NULL) {
            $tenantContext = $this->tenantContext;
            $tenantId = $tenantContext->getCurrentTenantId() ?? 0;
        }

        try {
            $articles = $this->recommendationService->getRelatedArticles($article_id, $limit, $tenantId);

            $results = [];
            foreach ($articles as $article) {
                $results[] = [
                    'id' => (int) $article->id(),
                    'uuid' => $article->uuid(),
                    'title' => $article->label(),
                    'slug' => $article->hasField('slug') ? $article->get('slug')->value : '',
                    'excerpt' => $article->hasField('excerpt') ? $article->get('excerpt')->value : '',
                    'category' => $article->hasField('category') && $article->get('category')->entity
                        ? $article->get('category')->entity->label()
                        : '',
                    'reading_time' => $article->hasField('reading_time') ? (int) $article->get('reading_time')->value : 0,
                    'featured_image' => $this->getImageUrl($article),
                ];
            }

            return new JsonResponse([
                'success' => TRUE,
                'data' => $results,
                'meta' => [
                    'source_article' => $article_id,
                    'count' => count($results),
                ],
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'Failed to get recommendations',
            ], 500);
        }
    }

    /**
     * Indexes an article (admin only).
     *
     * @param int $article_id
     *   The article ID to index.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON response with indexing result.
     */
    public function indexArticle(int $article_id): JsonResponse
    {
        try {
            $result = $this->recommendationService->indexArticle($article_id);

            return new JsonResponse([
                'success' => $result,
                'message' => $result ? 'Article indexed successfully' : 'Indexing failed',
                'article_id' => $article_id,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reindexes all published articles (admin only).
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON response with reindex statistics.
     */
    public function reindexAll(): JsonResponse
    {
        try {
            $stats = $this->recommendationService->reindexAll();

            return new JsonResponse([
                'success' => TRUE,
                'stats' => $stats,
                'message' => sprintf(
                    'Indexed %d/%d articles (%d failed)',
                    $stats['indexed'],
                    $stats['total'],
                    $stats['failed']
                ),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Gets the featured image URL for an article.
     */
    protected function getImageUrl($article): ?string
    {
        if (!$article->hasField('featured_image')) {
            return NULL;
        }

        $imageField = $article->get('featured_image');
        if ($imageField->isEmpty()) {
            return NULL;
        }

        $file = $imageField->entity;
        if (!$file) {
            return NULL;
        }

        return \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
    }

}
