<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\jaraba_content_hub\Entity\ContentArticle;
use Psr\Log\LoggerInterface;

/**
 * Servicio de recomendaciones semánticas para artículos.
 *
 * PROPÓSITO:
 * Implementa el sistema de recomendaciones "artículos relacionados"
 * usando búsqueda semántica basada en embeddings vectoriales y Qdrant.
 *
 * ARQUITECTURA:
 * - Primario: Qdrant para búsqueda por similitud semántica
 * - Fallback: Artículos de la misma categoría ordenados por fecha
 * - Último recurso: Artículos más recientes
 *
 * CARACTERÍSTICAS:
 * - Recomendaciones "relacionados" para páginas de artículos
 * - Recomendaciones personalizadas basadas en historial (futuro)
 * - Indexación automática en Qdrant al publicar
 * - Reindexación batch de todo el catálogo
 *
 * ESPECIFICACIÓN: Doc 128 - Platform_AI_Content_Hub_v2
 */
class RecommendationService
{

    /**
     * El gestor de tipos de entidad.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * El servicio de embeddings de contenido.
     *
     * Genera vectores de embeddings para artículos.
     *
     * @var \Drupal\jaraba_content_hub\Service\ContentEmbeddingService
     */
    protected ContentEmbeddingService $embeddingService;

    /**
     * El cliente de Qdrant para búsqueda vectorial.
     *
     * @var \Drupal\jaraba_content_hub\Service\QdrantContentClient
     */
    protected QdrantContentClient $qdrantClient;

    /**
     * El logger para registrar eventos.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Construye un RecommendationService.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
     *   El gestor de tipos de entidad.
     * @param \Drupal\jaraba_content_hub\Service\ContentEmbeddingService $embedding_service
     *   El servicio de embeddings.
     * @param \Drupal\jaraba_content_hub\Service\QdrantContentClient $qdrant_client
     *   El cliente de Qdrant.
     * @param object $logger_factory
     *   La factoría de loggers.
     */
    public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        ContentEmbeddingService $embedding_service,
        QdrantContentClient $qdrant_client,
        $logger_factory,
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->embeddingService = $embedding_service;
        $this->qdrantClient = $qdrant_client;
        $this->logger = $logger_factory->get('jaraba_content_hub');
    }

    /**
     * Obtiene artículos relacionados a uno dado.
     *
     * Intenta primero usar Qdrant para búsqueda semántica.
     * Si Qdrant no está disponible o no retorna resultados,
     * hace fallback a buscar artículos de la misma categoría.
     *
     * @param int $articleId
     *   ID del artículo base.
     * @param int $limit
     *   Número máximo de recomendaciones.
     * @param int $tenantId
     *   ID del tenant (0 = sin filtro de tenant).
     *
     * @return array
     *   Array de entidades ContentArticle relacionadas.
     */
    public function getRelatedArticles(int $articleId, int $limit = 5, int $tenantId = 0): array
    {
        // Intentar primero con Qdrant para búsqueda semántica.
        if ($this->qdrantClient->isAvailable()) {
            $related = $this->getRelatedViaQdrant($articleId, $limit, $tenantId);
            if (!empty($related)) {
                return $related;
            }
        }

        // Fallback a recomendaciones por categoría.
        return $this->getRelatedByCategory($articleId, $limit, $tenantId);
    }

    /**
     * Obtiene artículos relacionados usando búsqueda semántica en Qdrant.
     *
     * Genera el embedding del artículo actual y busca los más similares
     * en el índice vectorial, excluyendo el artículo original.
     *
     * @param int $articleId
     *   ID del artículo base.
     * @param int $limit
     *   Número máximo de resultados.
     * @param int $tenantId
     *   ID del tenant para filtrar.
     *
     * @return array
     *   Array de entidades ContentArticle ordenadas por similitud.
     */
    protected function getRelatedViaQdrant(int $articleId, int $limit, int $tenantId): array
    {
        try {
            $storage = $this->entityTypeManager->getStorage('content_article');
            $article = $storage->load($articleId);

            if (!$article) {
                return [];
            }

            // Generar embedding del artículo actual.
            $text = $this->embeddingService->getArticleEmbeddingText($article);
            $vector = $this->embeddingService->generate($text);

            if (empty($vector)) {
                $this->logger->warning('No se pudo generar embedding para artículo @id', [
                    '@id' => $articleId,
                ]);
                return [];
            }

            // Buscar artículos similares excluyendo el actual.
            // Umbral de similitud: 0.60 (60% mínimo).
            $results = $this->qdrantClient->searchSimilarArticles(
                $vector,
                $tenantId,
                $limit,
                0.60,
                [$articleId]
            );

            if (empty($results)) {
                return [];
            }

            // Cargar las entidades correspondientes.
            $ids = array_column($results, 'article_id');
            $articles = $storage->loadMultiple($ids);

            // Mantener el orden por score de similitud.
            $ordered = [];
            foreach ($results as $result) {
                $id = $result['article_id'];
                if (isset($articles[$id])) {
                    $ordered[] = $articles[$id];
                }
            }

            return $ordered;
        } catch (\Exception $e) {
            $this->logger->error('Error en recomendación Qdrant: @error', [
                '@error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Obtiene artículos relacionados por categoría (fallback).
     *
     * Busca artículos publicados de la misma categoría,
     * ordenados por fecha de creación descendente.
     *
     * @param int $articleId
     *   ID del artículo base.
     * @param int $limit
     *   Número máximo de resultados.
     * @param int $tenantId
     *   ID del tenant para filtrar.
     *
     * @return array
     *   Array de entidades ContentArticle.
     */
    protected function getRelatedByCategory(int $articleId, int $limit, int $tenantId): array
    {
        try {
            $storage = $this->entityTypeManager->getStorage('content_article');
            $article = $storage->load($articleId);

            if (!$article || !$article->hasField('category')) {
                return [];
            }

            $categoryId = $article->get('category')->target_id;
            if (!$categoryId) {
                // Sin categoría asignada, retornar los más recientes.
                return $this->getRecentArticles($limit, $tenantId, [$articleId]);
            }

            // Buscar artículos publicados de la misma categoría.
            $query = $storage->getQuery()
                ->condition('category', $categoryId)
                ->condition('status', 'published')
                ->condition('id', $articleId, '<>')
                ->sort('created', 'DESC')
                ->range(0, $limit)
                ->accessCheck(FALSE);

            if ($tenantId > 0) {
                $query->condition('tenant_id', $tenantId);
            }

            $ids = $query->execute();
            return $storage->loadMultiple($ids);
        } catch (\Exception $e) {
            $this->logger->error('Error en fallback de categoría: @error', [
                '@error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Obtiene los artículos más recientes.
     *
     * Usado como último recurso cuando no hay categoría ni
     * resultados de Qdrant disponibles.
     *
     * @param int $limit
     *   Número máximo de resultados.
     * @param int $tenantId
     *   ID del tenant para filtrar.
     * @param array $excludeIds
     *   IDs de artículos a excluir.
     *
     * @return array
     *   Array de entidades ContentArticle.
     */
    protected function getRecentArticles(int $limit, int $tenantId, array $excludeIds = []): array
    {
        try {
            $storage = $this->entityTypeManager->getStorage('content_article');
            $query = $storage->getQuery()
                ->condition('status', 'published')
                ->sort('created', 'DESC')
                ->range(0, $limit)
                ->accessCheck(FALSE);

            if (!empty($excludeIds)) {
                $query->condition('id', $excludeIds, 'NOT IN');
            }

            if ($tenantId > 0) {
                $query->condition('tenant_id', $tenantId);
            }

            $ids = $query->execute();
            return $storage->loadMultiple($ids);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Indexa un artículo en Qdrant.
     *
     * Genera el embedding del artículo y lo almacena en Qdrant
     * con metadatos para filtrado (tenant, categoría, etc.).
     * Debe llamarse cuando se publica un artículo.
     *
     * @param int $articleId
     *   ID del artículo a indexar.
     *
     * @return bool
     *   TRUE si se indexó correctamente.
     */
    public function indexArticle(int $articleId): bool
    {
        if (!$this->qdrantClient->isAvailable()) {
            $this->logger->info('Qdrant no disponible, omitiendo indexación de artículo');
            return FALSE;
        }

        try {
            $storage = $this->entityTypeManager->getStorage('content_article');
            $article = $storage->load($articleId);

            if (!$article) {
                return FALSE;
            }

            // Generar embedding del contenido.
            $text = $this->embeddingService->getArticleEmbeddingText($article);
            $vector = $this->embeddingService->generate($text);

            if (empty($vector)) {
                $this->logger->warning('Error al generar embedding para artículo @id', [
                    '@id' => $articleId,
                ]);
                return FALSE;
            }

            // Preparar payload con metadatos para filtrado en búsquedas.
            $payload = [
                'title' => $article->label(),
                'status' => $article->get('status')->value ?? 'draft',
                'tenant_id' => $article->hasField('tenant_id') ? (int) $article->get('tenant_id')->target_id : 0,
                'category' => $article->hasField('category') && $article->get('category')->entity
                    ? $article->get('category')->entity->label()
                    : '',
                'category_id' => $article->hasField('category')
                    ? (int) $article->get('category')->target_id
                    : 0,
                'vertical' => $article->hasField('vertical')
                    ? $article->get('vertical')->value
                    : '',
                'created' => (int) $article->get('created')->value,
                'author_id' => (int) $article->getOwnerId(),
            ];

            // Asegurar que la colección existe en Qdrant.
            $this->qdrantClient->ensureCollection();

            // Indexar el artículo.
            $result = $this->qdrantClient->indexArticle($articleId, $vector, $payload);

            if ($result) {
                $this->logger->info('Artículo @id indexado en Qdrant', ['@id' => $articleId]);
            }

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Error al indexar artículo @id: @error', [
                '@id' => $articleId,
                '@error' => $e->getMessage(),
            ]);
            return FALSE;
        }
    }

    /**
     * Elimina un artículo del índice de Qdrant.
     *
     * Debe llamarse cuando se despublica o elimina un artículo.
     *
     * @param int $articleId
     *   ID del artículo a eliminar.
     *
     * @return bool
     *   TRUE si se eliminó correctamente.
     */
    public function removeArticle(int $articleId): bool
    {
        if (!$this->qdrantClient->isAvailable()) {
            return FALSE;
        }

        return $this->qdrantClient->deleteArticle($articleId);
    }

    /**
     * Gets personalized article recommendations based on reading history (FIX-047).
     *
     * Analyzes the user's read articles to build a preference profile,
     * then finds similar unread articles via Qdrant or category fallback.
     *
     * @param int $userId
     *   The user ID.
     * @param int $limit
     *   Maximum recommendations.
     * @param int $tenantId
     *   Tenant ID (0 = no filter).
     *
     * @return array
     *   Array of recommended ContentArticle entities.
     */
    public function getPersonalizedRecommendations(int $userId, int $limit = 5, int $tenantId = 0): array
    {
        try {
            // Get recently read article IDs from reading history.
            $readArticleIds = $this->getReadingHistory($userId, 20);

            if (empty($readArticleIds)) {
                // No history — return trending/recent.
                return $this->getRecentArticles($limit, $tenantId);
            }

            // Try semantic approach: average embeddings of read articles.
            if ($this->qdrantClient->isAvailable()) {
                $recommendations = $this->getPersonalizedViaQdrant($readArticleIds, $limit, $tenantId);
                if (!empty($recommendations)) {
                    return $recommendations;
                }
            }

            // Fallback: recommend from most-read categories.
            return $this->getPersonalizedByCategory($readArticleIds, $limit, $tenantId);
        } catch (\Exception $e) {
            $this->logger->error('Error in personalized recommendations: @error', [
                '@error' => $e->getMessage(),
            ]);
            return $this->getRecentArticles($limit, $tenantId);
        }
    }

    /**
     * Gets personalized recommendations via Qdrant semantic search (FIX-047).
     *
     * Generates a centroid embedding from read articles and searches
     * for similar unread articles.
     *
     * @param array $readArticleIds
     *   IDs of articles the user has read.
     * @param int $limit
     *   Maximum results.
     * @param int $tenantId
     *   Tenant ID.
     *
     * @return array
     *   Array of ContentArticle entities.
     */
    protected function getPersonalizedViaQdrant(array $readArticleIds, int $limit, int $tenantId): array
    {
        $storage = $this->entityTypeManager->getStorage('content_article');

        // Use the most recent 5 read articles for the preference vector.
        $recentReadIds = array_slice($readArticleIds, 0, 5);
        $articles = $storage->loadMultiple($recentReadIds);

        $vectors = [];
        foreach ($articles as $article) {
            $text = $this->embeddingService->getArticleEmbeddingText($article);
            $vector = $this->embeddingService->generate($text);
            if (!empty($vector)) {
                $vectors[] = $vector;
            }
        }

        if (empty($vectors)) {
            return [];
        }

        // Calculate centroid (average) of read article vectors.
        $dimensions = count($vectors[0]);
        $centroid = array_fill(0, $dimensions, 0.0);

        foreach ($vectors as $vector) {
            for ($i = 0; $i < $dimensions; $i++) {
                $centroid[$i] += $vector[$i];
            }
        }
        $count = count($vectors);
        for ($i = 0; $i < $dimensions; $i++) {
            $centroid[$i] /= $count;
        }

        // Search for similar articles excluding already-read ones.
        $results = $this->qdrantClient->searchSimilarArticles(
            $centroid,
            $tenantId,
            $limit,
            0.55,
            $readArticleIds
        );

        if (empty($results)) {
            return [];
        }

        $ids = array_column($results, 'article_id');
        $recommended = $storage->loadMultiple($ids);

        // Preserve order by score.
        $ordered = [];
        foreach ($results as $result) {
            $id = $result['article_id'];
            if (isset($recommended[$id])) {
                $ordered[] = $recommended[$id];
            }
        }

        return $ordered;
    }

    /**
     * Gets personalized recommendations by favorite categories (FIX-047).
     *
     * @param array $readArticleIds
     *   IDs of articles already read.
     * @param int $limit
     *   Maximum results.
     * @param int $tenantId
     *   Tenant ID.
     *
     * @return array
     *   Array of ContentArticle entities.
     */
    protected function getPersonalizedByCategory(array $readArticleIds, int $limit, int $tenantId): array
    {
        $storage = $this->entityTypeManager->getStorage('content_article');
        $readArticles = $storage->loadMultiple($readArticleIds);

        // Count categories from reading history.
        $categoryCounts = [];
        foreach ($readArticles as $article) {
            if ($article->hasField('category')) {
                $catId = $article->get('category')->target_id;
                if ($catId) {
                    $categoryCounts[$catId] = ($categoryCounts[$catId] ?? 0) + 1;
                }
            }
        }

        if (empty($categoryCounts)) {
            return $this->getRecentArticles($limit, $tenantId, $readArticleIds);
        }

        // Sort by frequency descending.
        arsort($categoryCounts);
        $topCategories = array_slice(array_keys($categoryCounts), 0, 3);

        // Query unread articles from favorite categories.
        $query = $storage->getQuery()
            ->condition('category', $topCategories, 'IN')
            ->condition('status', 'published')
            ->condition('id', $readArticleIds, 'NOT IN')
            ->sort('created', 'DESC')
            ->range(0, $limit)
            ->accessCheck(FALSE);

        if ($tenantId > 0) {
            $query->condition('tenant_id', $tenantId);
        }

        $ids = $query->execute();
        return $storage->loadMultiple($ids);
    }

    /**
     * Gets reading history for a user (FIX-047).
     *
     * @param int $userId
     *   The user ID.
     * @param int $limit
     *   Maximum articles to retrieve.
     *
     * @return array
     *   Array of article IDs ordered by most recent read.
     */
    protected function getReadingHistory(int $userId, int $limit = 20): array
    {
        try {
            // Try reading_history entity if available.
            if ($this->entityTypeManager->hasDefinition('reading_history')) {
                $storage = $this->entityTypeManager->getStorage('reading_history');
                $ids = $storage->getQuery()
                    ->accessCheck(FALSE)
                    ->condition('user_id', $userId)
                    ->sort('read_at', 'DESC')
                    ->range(0, $limit)
                    ->execute();

                $articleIds = [];
                foreach ($storage->loadMultiple($ids) as $entry) {
                    $articleIds[] = (int) ($entry->get('article_id')->target_id ?? $entry->get('article_id')->value);
                }
                return array_filter($articleIds);
            }
        } catch (\Exception $e) {
            $this->logger->notice('reading_history entity not available: @msg', [
                '@msg' => $e->getMessage(),
            ]);
        }

        return [];
    }

    /**
     * Reindexea todos los artículos publicados en Qdrant.
     *
     * Útil para inicialización del índice o recuperación después
     * de pérdida de datos. Procesa en batches para evitar
     * sobrecarga de memoria.
     *
     * @param int $batchSize
     *   Tamaño del batch para procesamiento.
     *
     * @return array
     *   Estadísticas de la operación:
     *   - 'total': Total de artículos a indexar.
     *   - 'indexed': Artículos indexados exitosamente.
     *   - 'failed': Artículos con error.
     */
    public function reindexAll(int $batchSize = 50): array
    {
        $stats = [
            'total' => 0,
            'indexed' => 0,
            'failed' => 0,
        ];

        if (!$this->qdrantClient->isAvailable()) {
            return $stats;
        }

        try {
            $storage = $this->entityTypeManager->getStorage('content_article');
            $ids = $storage->getQuery()
                ->condition('status', 'published')
                ->accessCheck(FALSE)
                ->execute();

            $stats['total'] = count($ids);

            // Procesar en batches.
            foreach (array_chunk($ids, $batchSize) as $batch) {
                $articles = $storage->loadMultiple($batch);
                foreach ($articles as $article) {
                    if ($this->indexArticle((int) $article->id())) {
                        $stats['indexed']++;
                    } else {
                        $stats['failed']++;
                    }
                }
            }

            $this->logger->info('Reindexados @indexed/@total artículos', [
                '@indexed' => $stats['indexed'],
                '@total' => $stats['total'],
            ]);

            return $stats;
        } catch (\Exception $e) {
            $this->logger->error('Error en reindexación: @error', [
                '@error' => $e->getMessage(),
            ]);
            return $stats;
        }
    }

}
