<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio para gestionar entidades ContentArticle.
 *
 * PROPÓSITO:
 * Centraliza la lógica de negocio para artículos del Content Hub.
 * Proporciona métodos para consultas filtradas, tendencias, creación,
 * publicación, programación, view tracking, artículos relacionados,
 * estadísticas y tags. Es el servicio principal consumido
 * por controladores y APIs del blog.
 *
 * CONSOLIDACIÓN:
 * Backportea funcionalidades de jaraba_blog/BlogService:
 * - getRelatedArticles, getAdjacentArticles, trackView
 * - publishArticle, scheduleArticle, getPopularArticles
 * - calculateReadingTime, getStats, getAllTags
 * - publishScheduledArticles (para cron)
 *
 * ESPECIFICACIÓN: Doc 128 - Platform_AI_Content_Hub_v2
 */
class ArticleService
{

    /**
     * El gestor de tipos de entidad.
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * El usuario actual del sistema.
     */
    protected AccountProxyInterface $currentUser;

    /**
     * El logger para registrar eventos.
     */
    protected LoggerInterface $logger;

    /**
     * La conexión a base de datos.
     */
    protected Connection $database;

    /**
     * Construye un ArticleService.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
     *   El gestor de tipos de entidad.
     * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
     *   Proxy del usuario actual autenticado.
     * @param \Psr\Log\LoggerInterface $logger
     *   El servicio de logging.
     * @param \Drupal\Core\Database\Connection $database
     *   La conexión a base de datos para queries directas.
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        AccountProxyInterface $currentUser,
        LoggerInterface $logger,
        Connection $database,
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->currentUser = $currentUser;
        $this->logger = $logger;
        $this->database = $database;
    }

    // ========================================================================
    // Consultas de artículos publicados
    // ========================================================================

    /**
     * Obtiene artículos publicados con filtros opcionales.
     *
     * @param array $filters
     *   Filtros opcionales:
     *   - 'category': ID de categoría para filtrar.
     *   - 'author': ID de content_author para filtrar.
     *   - 'vertical': Vertical canónica.
     *   - 'is_featured': Solo artículos destacados.
     *   - 'limit': Número máximo de artículos.
     *   - 'offset': Desplazamiento para paginación.
     *
     * @return array
     *   Array de entidades ContentArticle.
     */
    public function getPublishedArticles(array $filters = []): array
    {
        $storage = $this->entityTypeManager->getStorage('content_article');
        $query = $storage->getQuery()
            ->condition('status', 'published')
            ->accessCheck(TRUE)
            ->sort('publish_date', 'DESC');

        if (!empty($filters['category'])) {
            $query->condition('category', $filters['category']);
        }

        if (!empty($filters['author'])) {
            $query->condition('content_author', $filters['author']);
        }

        if (!empty($filters['vertical'])) {
            $query->condition('vertical', $filters['vertical']);
        }

        if (!empty($filters['is_featured'])) {
            $query->condition('is_featured', TRUE);
        }

        if (!empty($filters['limit'])) {
            $query->range($filters['offset'] ?? 0, $filters['limit']);
        }

        $ids = $query->execute();
        return $storage->loadMultiple($ids);
    }

    /**
     * Cuenta el total de artículos publicados.
     *
     * @param array $filters
     *   Filtros opcionales:
     *   - 'category': ID de categoría para filtrar.
     *   - 'author': ID de content_author para filtrar.
     *
     * @return int
     *   Total de artículos publicados.
     */
    public function countPublishedArticles(array $filters = []): int
    {
        $storage = $this->entityTypeManager->getStorage('content_article');
        $query = $storage->getQuery()
            ->condition('status', 'published')
            ->accessCheck(TRUE)
            ->count();

        if (!empty($filters['category'])) {
            $query->condition('category', $filters['category']);
        }

        if (!empty($filters['author'])) {
            $query->condition('content_author', $filters['author']);
        }

        return (int) $query->execute();
    }

    /**
     * Obtiene un artículo por su UUID.
     *
     * @param string $uuid
     *   El UUID único del artículo.
     *
     * @return \Drupal\jaraba_content_hub\Entity\ContentArticleInterface|null
     *   La entidad artículo o NULL si no existe.
     */
    public function getByUuid(string $uuid)
    {
        $storage = $this->entityTypeManager->getStorage('content_article');
        $entities = $storage->loadByProperties(['uuid' => $uuid]);
        return $entities ? reset($entities) : NULL;
    }

    /**
     * Obtiene un artículo por su slug.
     *
     * Backport de BlogService::getPostBySlug().
     *
     * @param string $slug
     *   El slug URL-friendly del artículo.
     *
     * @return \Drupal\jaraba_content_hub\Entity\ContentArticleInterface|null
     *   La entidad artículo o NULL si no existe.
     */
    public function getArticleBySlug(string $slug)
    {
        $storage = $this->entityTypeManager->getStorage('content_article');
        $ids = $storage->getQuery()
            ->condition('slug', $slug)
            ->accessCheck(TRUE)
            ->range(0, 1)
            ->execute();

        if (empty($ids)) {
            return NULL;
        }

        return $storage->load(reset($ids));
    }

    /**
     * Obtiene los artículos más populares (trending por engagement).
     *
     * @param int $limit
     *   Número de artículos a retornar (por defecto 5).
     *
     * @return array
     *   Array de entidades ContentArticle más populares.
     */
    public function getTrendingArticles(int $limit = 5): array
    {
        $storage = $this->entityTypeManager->getStorage('content_article');
        $ids = $storage->getQuery()
            ->condition('status', 'published')
            ->accessCheck(TRUE)
            ->sort('engagement_score', 'DESC')
            ->range(0, $limit)
            ->execute();

        return $storage->loadMultiple($ids);
    }

    /**
     * Obtiene los artículos más recientes.
     *
     * @param int $limit
     *   Número de artículos a retornar (por defecto 5).
     *
     * @return array
     *   Array de entidades ContentArticle más recientes.
     */
    public function getRecentArticles(int $limit = 5): array
    {
        $storage = $this->entityTypeManager->getStorage('content_article');
        $ids = $storage->getQuery()
            ->condition('status', 'published')
            ->accessCheck(TRUE)
            ->sort('publish_date', 'DESC')
            ->range(0, $limit)
            ->execute();

        return $storage->loadMultiple($ids);
    }

    /**
     * Obtiene los artículos más vistos.
     *
     * Backport de BlogService::getPopularPosts().
     *
     * @param int $limit
     *   Número de artículos (por defecto 5).
     *
     * @return array
     *   Array de entidades ContentArticle ordenados por views_count.
     */
    public function getPopularArticles(int $limit = 5): array
    {
        $storage = $this->entityTypeManager->getStorage('content_article');
        $ids = $storage->getQuery()
            ->condition('status', 'published')
            ->accessCheck(TRUE)
            ->sort('views_count', 'DESC')
            ->range(0, $limit)
            ->execute();

        return $storage->loadMultiple($ids);
    }

    /**
     * Obtiene artículos destacados (is_featured = TRUE).
     *
     * @param int $limit
     *   Número de artículos (por defecto 5).
     *
     * @return array
     *   Array de entidades ContentArticle destacados.
     */
    public function getFeaturedArticles(int $limit = 5): array
    {
        $storage = $this->entityTypeManager->getStorage('content_article');
        $ids = $storage->getQuery()
            ->condition('status', 'published')
            ->condition('is_featured', TRUE)
            ->accessCheck(TRUE)
            ->sort('publish_date', 'DESC')
            ->range(0, $limit)
            ->execute();

        return $storage->loadMultiple($ids);
    }

    // ========================================================================
    // Artículos relacionados y navegación
    // ========================================================================

    /**
     * Obtiene artículos relacionados por categoría + recientes.
     *
     * Backport de BlogService::getRelatedPosts().
     * Primero busca por la misma categoría, luego completa con recientes.
     *
     * @param int $articleId
     *   ID del artículo actual.
     * @param int $limit
     *   Número de artículos relacionados (por defecto 4).
     *
     * @return array
     *   Array de entidades ContentArticle relacionadas.
     */
    public function getRelatedArticles(int $articleId, int $limit = 4): array
    {
        $storage = $this->entityTypeManager->getStorage('content_article');
        $article = $storage->load($articleId);
        if (!$article) {
            return [];
        }

        $related = [];

        // Primero por categoría.
        $categoryField = $article->get('category');
        if (!$categoryField->isEmpty()) {
            $categoryId = $categoryField->target_id;
            $ids = $storage->getQuery()
                ->condition('category', $categoryId)
                ->condition('status', 'published')
                ->condition('id', $articleId, '<>')
                ->accessCheck(TRUE)
                ->sort('publish_date', 'DESC')
                ->range(0, $limit)
                ->execute();

            if ($ids) {
                $related = $storage->loadMultiple($ids);
            }
        }

        // Completar con artículos recientes si faltan.
        if (count($related) < $limit) {
            $excludeIds = array_merge([$articleId], array_keys($related));
            $remaining = $limit - count($related);

            $ids = $storage->getQuery()
                ->condition('status', 'published')
                ->condition('id', $excludeIds, 'NOT IN')
                ->accessCheck(TRUE)
                ->sort('publish_date', 'DESC')
                ->range(0, $remaining)
                ->execute();

            if ($ids) {
                $related += $storage->loadMultiple($ids);
            }
        }

        return array_values($related);
    }

    /**
     * Obtiene artículos anterior y siguiente para navegación.
     *
     * Backport de BlogService::getAdjacentPosts().
     *
     * @param int $articleId
     *   ID del artículo actual.
     *
     * @return array
     *   ['prev' => ContentArticle|null, 'next' => ContentArticle|null].
     */
    public function getAdjacentArticles(int $articleId): array
    {
        $storage = $this->entityTypeManager->getStorage('content_article');
        $article = $storage->load($articleId);
        if (!$article) {
            return ['prev' => NULL, 'next' => NULL];
        }

        $publishDate = $article->get('publish_date')->value;
        if (!$publishDate) {
            return ['prev' => NULL, 'next' => NULL];
        }

        // Artículo anterior (más antiguo).
        $prevIds = $storage->getQuery()
            ->condition('status', 'published')
            ->condition('publish_date', $publishDate, '<')
            ->accessCheck(TRUE)
            ->sort('publish_date', 'DESC')
            ->range(0, 1)
            ->execute();

        // Artículo siguiente (más reciente).
        $nextIds = $storage->getQuery()
            ->condition('status', 'published')
            ->condition('publish_date', $publishDate, '>')
            ->accessCheck(TRUE)
            ->sort('publish_date', 'ASC')
            ->range(0, 1)
            ->execute();

        return [
            'prev' => $prevIds ? $storage->load(reset($prevIds)) : NULL,
            'next' => $nextIds ? $storage->load(reset($nextIds)) : NULL,
        ];
    }

    // ========================================================================
    // Consultas por autor
    // ========================================================================

    /**
     * Obtiene artículos publicados de un autor editorial.
     *
     * @param int $authorId
     *   ID del ContentAuthor.
     * @param int $limit
     *   Número máximo de artículos.
     * @param int $offset
     *   Desplazamiento para paginación.
     *
     * @return array
     *   Array de entidades ContentArticle.
     */
    public function getArticlesByAuthor(int $authorId, int $limit = 12, int $offset = 0): array
    {
        $storage = $this->entityTypeManager->getStorage('content_article');
        $ids = $storage->getQuery()
            ->condition('content_author', $authorId)
            ->condition('status', 'published')
            ->accessCheck(TRUE)
            ->sort('publish_date', 'DESC')
            ->range($offset, $limit)
            ->execute();

        return $storage->loadMultiple($ids);
    }

    /**
     * Cuenta artículos publicados de un autor editorial.
     *
     * @param int $authorId
     *   ID del ContentAuthor.
     *
     * @return int
     *   Número de artículos publicados.
     */
    public function countArticlesByAuthor(int $authorId): int
    {
        $storage = $this->entityTypeManager->getStorage('content_article');
        return (int) $storage->getQuery()
            ->condition('content_author', $authorId)
            ->condition('status', 'published')
            ->accessCheck(TRUE)
            ->count()
            ->execute();
    }

    // ========================================================================
    // View tracking
    // ========================================================================

    /**
     * Incrementa el contador de visitas de un artículo.
     *
     * Backport de BlogService::trackView().
     * Usa DB expression para evitar race conditions.
     *
     * @param int $articleId
     *   ID del artículo.
     */
    public function trackView(int $articleId): void
    {
        try {
            $this->database->update('content_article_field_data')
                ->expression('views_count', 'views_count + 1')
                ->condition('id', $articleId)
                ->execute();
        }
        catch (\Exception $e) {
            $this->logger->warning(
                'Failed to track view for article @id: @error',
                ['@id' => $articleId, '@error' => $e->getMessage()]
            );
        }
    }

    // ========================================================================
    // Creación y publicación
    // ========================================================================

    /**
     * Crea un nuevo artículo.
     *
     * @param array $data
     *   Datos del artículo:
     *   - 'title': Título (requerido).
     *   - 'body': Contenido HTML.
     *   - 'excerpt': Extracto para listados.
     *   - 'answer_capsule': Respuesta directa (SEO).
     *   - 'category': ID de categoría.
     *   - 'content_author': ID de ContentAuthor.
     *   - 'tags': Tags comma-separated.
     *   - 'vertical': Vertical canónica.
     *   - 'status': Estado ('draft'|'published').
     *   - 'ai_generated': Si fue generado por IA.
     *
     * @return \Drupal\jaraba_content_hub\Entity\ContentArticleInterface
     *   La entidad artículo creada y guardada.
     */
    public function create(array $data)
    {
        $storage = $this->entityTypeManager->getStorage('content_article');
        $values = [
            'title' => $data['title'] ?? '',
            'body' => $data['body'] ?? '',
            'excerpt' => $data['excerpt'] ?? '',
            'answer_capsule' => $data['answer_capsule'] ?? '',
            'category' => $data['category'] ?? NULL,
            'status' => $data['status'] ?? 'draft',
            'author' => $this->currentUser->id(),
            'ai_generated' => $data['ai_generated'] ?? FALSE,
        ];

        // Campos opcionales backporteados.
        if (!empty($data['content_author'])) {
            $values['content_author'] = $data['content_author'];
        }
        if (!empty($data['tags'])) {
            $values['tags'] = $data['tags'];
        }
        if (!empty($data['vertical'])) {
            $values['vertical'] = $data['vertical'];
        }

        $article = $storage->create($values);
        $article->save();

        $this->logger->info('Artículo creado: @title', ['@title' => $article->label()]);

        return $article;
    }

    /**
     * Publica un artículo existente por UUID.
     *
     * @param string $uuid
     *   El UUID del artículo a publicar.
     *
     * @return bool
     *   TRUE si se publicó exitosamente, FALSE si no existe.
     */
    public function publish(string $uuid): bool
    {
        $article = $this->getByUuid($uuid);
        if (!$article) {
            return FALSE;
        }

        $article->set('status', 'published');
        $article->set('publish_date', date('Y-m-d\TH:i:s'));
        $article->save();

        $this->logger->info('Artículo publicado: @title', ['@title' => $article->label()]);

        return TRUE;
    }

    /**
     * Publica un artículo existente por ID.
     *
     * Backport de BlogService::publishPost().
     *
     * @param int $articleId
     *   ID del artículo.
     *
     * @return bool
     *   TRUE si se publicó, FALSE si no existe.
     */
    public function publishArticle(int $articleId): bool
    {
        $storage = $this->entityTypeManager->getStorage('content_article');
        $article = $storage->load($articleId);
        if (!$article) {
            return FALSE;
        }

        $article->set('status', 'published');
        if (!$article->get('publish_date')->value) {
            $article->set('publish_date', date('Y-m-d\TH:i:s'));
        }
        $article->save();

        $this->logger->info('Artículo publicado: @id (@title)', [
            '@id' => $articleId,
            '@title' => $article->label(),
        ]);

        return TRUE;
    }

    /**
     * Programa la publicación de un artículo.
     *
     * Backport de BlogService::schedulePost().
     *
     * @param int $articleId
     *   ID del artículo.
     * @param string $scheduledAt
     *   Fecha/hora ISO 8601 para auto-publicar.
     *
     * @return bool
     *   TRUE si se programó, FALSE si no existe.
     */
    public function scheduleArticle(int $articleId, string $scheduledAt): bool
    {
        $storage = $this->entityTypeManager->getStorage('content_article');
        $article = $storage->load($articleId);
        if (!$article) {
            return FALSE;
        }

        $article->set('status', 'scheduled');
        $article->set('scheduled_at', $scheduledAt);
        $article->save();

        $this->logger->info('Artículo programado: @id para @date', [
            '@id' => $articleId,
            '@date' => $scheduledAt,
        ]);

        return TRUE;
    }

    /**
     * Publica artículos programados cuya fecha ha pasado.
     *
     * Backport de BlogService::publishScheduledPosts().
     * Invocado desde hook_cron.
     *
     * @return int
     *   Número de artículos publicados.
     */
    public function publishScheduledArticles(): int
    {
        $storage = $this->entityTypeManager->getStorage('content_article');
        $now = date('Y-m-d\TH:i:s');

        $ids = $storage->getQuery()
            ->condition('status', 'scheduled')
            ->condition('scheduled_at', $now, '<=')
            ->accessCheck(FALSE)
            ->execute();

        $count = 0;
        if ($ids) {
            $articles = $storage->loadMultiple($ids);
            foreach ($articles as $article) {
                $article->set('status', 'published');
                $article->set('publish_date', $article->get('scheduled_at')->value);
                $article->save();
                $count++;

                $this->logger->info('Artículo programado publicado: @id (@title)', [
                    '@id' => $article->id(),
                    '@title' => $article->label(),
                ]);
            }
        }

        return $count;
    }

    // ========================================================================
    // Utilidades
    // ========================================================================

    /**
     * Calcula el tiempo de lectura basado en el contenido.
     *
     * Backport de BlogService::calculateReadingTime().
     * Usa una media de 200 palabras por minuto.
     *
     * @param string $content
     *   Contenido HTML del artículo.
     *
     * @return int
     *   Minutos de lectura estimados (mínimo 1).
     */
    public function calculateReadingTime(string $content): int
    {
        $wordCount = str_word_count(strip_tags($content));
        $minutes = (int) ceil($wordCount / 200);

        return max(1, $minutes);
    }

    // ========================================================================
    // Tags
    // ========================================================================

    /**
     * Obtiene todas las tags únicas con conteo.
     *
     * Backport de BlogService::getAllTags().
     *
     * @return array
     *   Associative array [tag => count] ordenado por frecuencia desc.
     */
    public function getAllTags(): array
    {
        try {
            $result = $this->database->select('content_article_field_data', 'a')
                ->fields('a', ['tags'])
                ->condition('a.status', 'published')
                ->isNotNull('a.tags')
                ->execute();

            $allTags = [];
            foreach ($result as $row) {
                $tags = array_map('trim', explode(',', $row->tags));
                foreach ($tags as $tag) {
                    if (!empty($tag)) {
                        $allTags[$tag] = ($allTags[$tag] ?? 0) + 1;
                    }
                }
            }

            arsort($allTags);
            return $allTags;
        }
        catch (\Exception $e) {
            $this->logger->warning('Failed to get tags: @error', [
                '@error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    // ========================================================================
    // Estadísticas
    // ========================================================================

    /**
     * Obtiene estadísticas del Content Hub.
     *
     * Backport de BlogService::getStats().
     *
     * @return array
     *   ['total_articles', 'published', 'draft', 'scheduled',
     *    'total_views', 'categories', 'authors'].
     */
    public function getStats(): array
    {
        $articleStorage = $this->entityTypeManager->getStorage('content_article');

        $total = (int) $articleStorage->getQuery()
            ->accessCheck(FALSE)
            ->count()
            ->execute();

        $published = (int) $articleStorage->getQuery()
            ->condition('status', 'published')
            ->accessCheck(FALSE)
            ->count()
            ->execute();

        $draft = (int) $articleStorage->getQuery()
            ->condition('status', 'draft')
            ->accessCheck(FALSE)
            ->count()
            ->execute();

        $scheduled = (int) $articleStorage->getQuery()
            ->condition('status', 'scheduled')
            ->accessCheck(FALSE)
            ->count()
            ->execute();

        // Total de visualizaciones.
        $totalViews = 0;
        try {
            if ($this->database->schema()->tableExists('content_article_field_data')) {
                $viewsQuery = $this->database->select('content_article_field_data', 'a');
                $viewsQuery->addExpression('SUM(views_count)', 'total_views');
                $totalViews = (int) $viewsQuery->execute()->fetchField();
            }
        }
        catch (\Exception $e) {
            // Tabla no existente aún o campo no disponible.
        }

        // Categorías activas.
        $categories = 0;
        try {
            $categories = (int) $this->entityTypeManager->getStorage('content_category')
                ->getQuery()
                ->accessCheck(FALSE)
                ->count()
                ->execute();
        }
        catch (\Exception $e) {
            // Entity type might not exist yet.
        }

        // Autores activos.
        $authors = 0;
        try {
            $authors = (int) $this->entityTypeManager->getStorage('content_author')
                ->getQuery()
                ->condition('is_active', TRUE)
                ->accessCheck(FALSE)
                ->count()
                ->execute();
        }
        catch (\Exception $e) {
            // Entity type might not exist yet.
        }

        return [
            'total_articles' => $total,
            'published' => $published,
            'draft' => $draft,
            'scheduled' => $scheduled,
            'total_views' => $totalViews,
            'categories' => $categories,
            'authors' => $authors,
        ];
    }

}
