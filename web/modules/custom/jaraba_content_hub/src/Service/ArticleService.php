<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio para gestionar entidades ContentArticle.
 *
 * PROPÓSITO:
 * Centraliza la lógica de negocio para artículos del Content Hub.
 * Proporciona métodos para consultas filtradas, tendencias, creación
 * y publicación de contenido. Es el servicio principal consumido
 * por controladores y APIs del blog.
 *
 * ESPECIFICACIÓN: Doc 128 - Platform_AI_Content_Hub_v2
 */
class ArticleService
{

    /**
     * El gestor de tipos de entidad.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * El usuario actual del sistema.
     *
     * @var \Drupal\Core\Session\AccountProxyInterface
     */
    protected AccountProxyInterface $currentUser;

    /**
     * El logger para registrar eventos.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Construye un ArticleService.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
     *   El gestor de tipos de entidad.
     * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
     *   Proxy del usuario actual autenticado.
     * @param \Psr\Log\LoggerInterface $logger
     *   El servicio de logging.
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        AccountProxyInterface $currentUser,
        LoggerInterface $logger,
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->currentUser = $currentUser;
        $this->logger = $logger;
    }

    /**
     * Obtiene artículos publicados con filtros opcionales.
     *
     * Retorna artículos con status 'published' ordenados por fecha
     * de publicación descendente. Soporta filtrado por categoría
     * y paginación.
     *
     * @param array $filters
     *   Filtros opcionales:
     *   - 'category': ID de categoría para filtrar.
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

        if (!empty($filters['limit'])) {
            $query->range($filters['offset'] ?? 0, $filters['limit']);
        }

        $ids = $query->execute();
        return $storage->loadMultiple($ids);
    }

    /**
     * Cuenta el total de artículos publicados.
     *
     * Útil para calcular paginación sin cargar entidades.
     *
     * @param array $filters
     *   Filtros opcionales:
     *   - 'category': ID de categoría para filtrar.
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
     * Obtiene los artículos más populares (trending).
     *
     * Ordena por engagement_score descendente para mostrar
     * el contenido con mayor interacción de usuarios.
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
     * Ordena por fecha de publicación descendente.
     * Útil para widgets de "últimas noticias" y feeds.
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
     * Crea un nuevo artículo.
     *
     * Genera una entidad ContentArticle con los datos proporcionados
     * y la guarda en la base de datos. Asigna automáticamente
     * el autor actual si no se especifica.
     *
     * @param array $data
     *   Datos del artículo:
     *   - 'title': Título (requerido).
     *   - 'body': Contenido HTML.
     *   - 'excerpt': Extracto para listados.
     *   - 'answer_capsule': Respuesta directa (SEO).
     *   - 'category': ID de categoría.
     *   - 'status': Estado ('draft'|'published').
     *   - 'ai_generated': Si fue generado por IA.
     *
     * @return \Drupal\jaraba_content_hub\Entity\ContentArticleInterface
     *   La entidad artículo creada y guardada.
     */
    public function create(array $data)
    {
        $storage = $this->entityTypeManager->getStorage('content_article');
        $article = $storage->create([
            'title' => $data['title'] ?? '',
            'body' => $data['body'] ?? '',
            'excerpt' => $data['excerpt'] ?? '',
            'answer_capsule' => $data['answer_capsule'] ?? '',
            'category' => $data['category'] ?? NULL,
            'status' => $data['status'] ?? 'draft',
            'author' => $this->currentUser->id(),
            'ai_generated' => $data['ai_generated'] ?? FALSE,
        ]);

        $article->save();

        $this->logger->info('Artículo creado: @title', ['@title' => $article->label()]);

        return $article;
    }

    /**
     * Publica un artículo existente.
     *
     * Cambia el status a 'published' y establece la fecha de publicación
     * al momento actual. Solo funciona con artículos en estado draft.
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

}
