<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestión de artículos de la base de conocimiento.
 *
 * PROPÓSITO:
 * Centraliza la lógica de negocio para artículos KB: listados paginados,
 * búsqueda por slug, métricas de feedback y artículos populares.
 *
 * DIRECTRICES:
 * - Patrón try-catch con logger en todos los métodos públicos
 * - Solo muestra artículos publicados en métodos públicos
 * - Traducciones con t() en etiquetas
 */
class KbArticleManagerService {

  /**
   * Constructor del servicio de gestión de artículos KB.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de log.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Obtiene artículos publicados con paginación.
   *
   * @param int|null $tenantId
   *   ID del tenant para filtrar, o NULL para todos.
   * @param int|null $categoryId
   *   ID de categoría para filtrar, o NULL para todas.
   * @param int $page
   *   Número de página (0-indexed).
   * @param int $limit
   *   Número de artículos por página.
   *
   * @return array
   *   Array con keys: articles (array de datos), total (int), page (int), limit (int).
   */
  public function getPublishedArticles(?int $tenantId, ?int $categoryId = NULL, int $page = 0, int $limit = 20): array {
    try {
      $storage = $this->entityTypeManager->getStorage('kb_article');

      // Contar total.
      $countQuery = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('article_status', 'published');

      if ($tenantId !== NULL) {
        $countQuery->condition('tenant_id', $tenantId);
      }
      if ($categoryId !== NULL) {
        $countQuery->condition('category_id', $categoryId);
      }

      $total = (int) $countQuery->count()->execute();

      // Obtener página.
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('article_status', 'published')
        ->sort('created', 'DESC')
        ->range($page * $limit, $limit);

      if ($tenantId !== NULL) {
        $query->condition('tenant_id', $tenantId);
      }
      if ($categoryId !== NULL) {
        $query->condition('category_id', $categoryId);
      }

      $ids = $query->execute();
      $articles = $ids ? $storage->loadMultiple($ids) : [];

      $result = [];
      foreach ($articles as $article) {
        /** @var \Drupal\jaraba_tenant_knowledge\Entity\KbArticle $article */
        $result[] = [
          'id' => (int) $article->id(),
          'title' => $article->getTitle(),
          'slug' => $article->getSlug(),
          'summary' => $article->getSummary() ?: $this->truncate($article->getBody(), 200),
          'category_id' => $article->getCategoryId(),
          'view_count' => $article->getViewCount(),
          'helpful_count' => $article->getHelpfulCount(),
          'not_helpful_count' => $article->getNotHelpfulCount(),
          'created' => $article->get('created')->value,
        ];
      }

      return [
        'articles' => $result,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo artículos KB publicados: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [
        'articles' => [],
        'total' => 0,
        'page' => $page,
        'limit' => $limit,
      ];
    }
  }

  /**
   * Obtiene un artículo por su slug.
   *
   * @param string $slug
   *   Slug del artículo.
   * @param int|null $tenantId
   *   ID del tenant para filtrar, o NULL para todos.
   *
   * @return array|null
   *   Datos del artículo o NULL si no existe.
   */
  public function getArticleBySlug(string $slug, ?int $tenantId): ?array {
    try {
      $storage = $this->entityTypeManager->getStorage('kb_article');

      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('slug', $slug)
        ->condition('article_status', 'published')
        ->range(0, 1);

      if ($tenantId !== NULL) {
        $query->condition('tenant_id', $tenantId);
      }

      $ids = $query->execute();

      if (empty($ids)) {
        return NULL;
      }

      /** @var \Drupal\jaraba_tenant_knowledge\Entity\KbArticle $article */
      $article = $storage->load(reset($ids));

      if (!$article) {
        return NULL;
      }

      // Cargar categoría si existe.
      $categoryName = '';
      $categorySlug = '';
      if ($article->getCategoryId()) {
        $category = $this->entityTypeManager->getStorage('kb_category')
          ->load($article->getCategoryId());
        if ($category) {
          $categoryName = $category->getName();
          $categorySlug = $category->getSlug();
        }
      }

      // Cargar vídeos vinculados.
      $videoQuery = $this->entityTypeManager->getStorage('kb_video')->getQuery()
        ->accessCheck(FALSE)
        ->condition('article_id', $article->id())
        ->condition('video_status', 'published');
      $videoIds = $videoQuery->execute();
      $videos = [];
      if (!empty($videoIds)) {
        $videoEntities = $this->entityTypeManager->getStorage('kb_video')
          ->loadMultiple($videoIds);
        foreach ($videoEntities as $video) {
          /** @var \Drupal\jaraba_tenant_knowledge\Entity\KbVideo $video */
          $videos[] = [
            'id' => (int) $video->id(),
            'title' => $video->getTitle(),
            'video_url' => $video->getVideoUrl(),
            'thumbnail_url' => $video->getThumbnailUrl(),
            'duration' => $video->getFormattedDuration(),
          ];
        }
      }

      return [
        'id' => (int) $article->id(),
        'title' => $article->getTitle(),
        'slug' => $article->getSlug(),
        'body' => $article->getBody(),
        'summary' => $article->getSummary(),
        'category_id' => $article->getCategoryId(),
        'category_name' => $categoryName,
        'category_slug' => $categorySlug,
        'author_id' => $article->get('author_id')->target_id ? (int) $article->get('author_id')->target_id : NULL,
        'view_count' => $article->getViewCount(),
        'helpful_count' => $article->getHelpfulCount(),
        'not_helpful_count' => $article->getNotHelpfulCount(),
        'tags' => $article->getTagsArray(),
        'videos' => $videos,
        'created' => $article->get('created')->value,
        'changed' => $article->get('changed')->value,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo artículo KB por slug "@slug": @error', [
        '@slug' => $slug,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Incrementa el contador de vistas de un artículo.
   *
   * @param int $articleId
   *   ID del artículo.
   */
  public function incrementViewCount(int $articleId): void {
    try {
      $storage = $this->entityTypeManager->getStorage('kb_article');
      /** @var \Drupal\jaraba_tenant_knowledge\Entity\KbArticle|null $article */
      $article = $storage->load($articleId);

      if ($article) {
        $currentCount = $article->getViewCount();
        $article->set('view_count', $currentCount + 1);
        $article->save();
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error incrementando vistas del artículo KB @id: @error', [
        '@id' => $articleId,
        '@error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Registra feedback (útil/no útil) para un artículo.
   *
   * @param int $articleId
   *   ID del artículo.
   * @param bool $helpful
   *   TRUE si fue útil, FALSE si no.
   */
  public function recordFeedback(int $articleId, bool $helpful): void {
    try {
      $storage = $this->entityTypeManager->getStorage('kb_article');
      /** @var \Drupal\jaraba_tenant_knowledge\Entity\KbArticle|null $article */
      $article = $storage->load($articleId);

      if ($article) {
        if ($helpful) {
          $article->set('helpful_count', $article->getHelpfulCount() + 1);
        }
        else {
          $article->set('not_helpful_count', $article->getNotHelpfulCount() + 1);
        }
        $article->save();
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error registrando feedback del artículo KB @id: @error', [
        '@id' => $articleId,
        '@error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Obtiene los artículos más populares.
   *
   * @param int|null $tenantId
   *   ID del tenant para filtrar, o NULL para todos.
   * @param int $limit
   *   Número máximo de artículos.
   *
   * @return array
   *   Array de artículos con keys: id, title, slug, summary, view_count.
   */
  public function getPopularArticles(?int $tenantId, int $limit = 5): array {
    try {
      $storage = $this->entityTypeManager->getStorage('kb_article');

      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('article_status', 'published')
        ->sort('view_count', 'DESC')
        ->range(0, $limit);

      if ($tenantId !== NULL) {
        $query->condition('tenant_id', $tenantId);
      }

      $ids = $query->execute();
      $articles = $ids ? $storage->loadMultiple($ids) : [];

      $result = [];
      foreach ($articles as $article) {
        /** @var \Drupal\jaraba_tenant_knowledge\Entity\KbArticle $article */
        $result[] = [
          'id' => (int) $article->id(),
          'title' => $article->getTitle(),
          'slug' => $article->getSlug(),
          'summary' => $article->getSummary() ?: $this->truncate($article->getBody(), 150),
          'view_count' => $article->getViewCount(),
        ];
      }

      return $result;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo artículos KB populares: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Trunca texto al límite especificado sin cortar palabras.
   */
  protected function truncate(string $text, int $limit): string {
    $text = strip_tags($text);
    if (mb_strlen($text) <= $limit) {
      return $text;
    }
    $truncated = mb_substr($text, 0, $limit);
    $lastSpace = mb_strrpos($truncated, ' ');
    if ($lastSpace !== FALSE) {
      $truncated = mb_substr($truncated, 0, $lastSpace);
    }
    return $truncated . '...';
  }

}
