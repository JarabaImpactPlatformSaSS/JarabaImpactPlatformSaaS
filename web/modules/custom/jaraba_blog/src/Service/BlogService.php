<?php

declare(strict_types=1);

namespace Drupal\jaraba_blog\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Psr\Log\LoggerInterface;

/**
 * Servicio principal del blog.
 *
 * Operaciones:
 * - CRUD de posts, categorias y autores
 * - Listados paginados con filtros
 * - Posts relacionados por categoria/tags
 * - Calculo de reading time
 * - Tracking de vistas
 * - Estadisticas del blog
 */
class BlogService {

  /**
   * Constructor.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Connection $database,
    protected TenantContextService $tenantContext,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Obtiene el tenant ID actual.
   */
  protected function getTenantId(): int {
    $tenant = $this->tenantContext->getCurrentTenant();
    if (!$tenant) {
      throw new \RuntimeException('No hay tenant activo.');
    }
    return (int) $tenant->id();
  }

  /**
   * Obtiene posts paginados con filtros.
   *
   * @param array $filters
   *   Filtros: category_id, author_id, tag, status, is_featured, search.
   * @param int $page
   *   Numero de pagina (0-indexed).
   * @param int $limit
   *   Posts por pagina.
   *
   * @return array
   *   ['posts' => [], 'total' => int, 'pages' => int].
   */
  public function listPosts(array $filters = [], int $page = 0, int $limit = 12): array {
    $tenantId = $this->getTenantId();
    $storage = $this->entityTypeManager->getStorage('blog_post');

    // Query de conteo.
    $countQuery = $storage->getQuery()
      ->condition('tenant_id', $tenantId)
      ->accessCheck(FALSE)
      ->count();
    $this->applyFilters($countQuery, $filters);
    $total = (int) $countQuery->execute();

    // Query paginada.
    $query = $storage->getQuery()
      ->condition('tenant_id', $tenantId)
      ->accessCheck(FALSE)
      ->sort('created', 'DESC')
      ->range($page * $limit, $limit);
    $this->applyFilters($query, $filters);

    $ids = $query->execute();
    $posts = $ids ? $storage->loadMultiple($ids) : [];

    return [
      'posts' => array_values($posts),
      'total' => $total,
      'pages' => $total > 0 ? (int) ceil($total / $limit) : 0,
    ];
  }

  /**
   * Aplica filtros a una query de posts.
   */
  protected function applyFilters(object $query, array $filters): void {
    if (!empty($filters['status'])) {
      $query->condition('status', $filters['status']);
    }
    if (!empty($filters['category_id'])) {
      $query->condition('category_id', $filters['category_id']);
    }
    if (!empty($filters['author_id'])) {
      $query->condition('author_id', $filters['author_id']);
    }
    if (!empty($filters['is_featured'])) {
      $query->condition('is_featured', TRUE);
    }
    if (!empty($filters['search'])) {
      $group = $query->orConditionGroup()
        ->condition('title', '%' . $filters['search'] . '%', 'LIKE')
        ->condition('body', '%' . $filters['search'] . '%', 'LIKE');
      $query->condition($group);
    }
  }

  /**
   * Obtiene un post por slug.
   */
  public function getPostBySlug(string $slug): ?object {
    $tenantId = $this->getTenantId();
    $storage = $this->entityTypeManager->getStorage('blog_post');

    $ids = $storage->getQuery()
      ->condition('tenant_id', $tenantId)
      ->condition('slug', $slug)
      ->accessCheck(FALSE)
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return NULL;
    }

    return $storage->load(reset($ids));
  }

  /**
   * Obtiene un post por ID.
   */
  public function getPost(int $id): ?object {
    return $this->entityTypeManager->getStorage('blog_post')->load($id);
  }

  /**
   * Obtiene posts relacionados por categoria y tags.
   */
  public function getRelatedPosts(int $postId, int $limit = 4): array {
    $post = $this->getPost($postId);
    if (!$post) {
      return [];
    }

    $tenantId = $post->getTenantId();
    $storage = $this->entityTypeManager->getStorage('blog_post');
    $related = [];

    // Primero por categoria.
    $categoryId = $post->getCategoryId();
    if ($categoryId) {
      $ids = $storage->getQuery()
        ->condition('tenant_id', $tenantId)
        ->condition('category_id', $categoryId)
        ->condition('status', 'published')
        ->condition('id', $postId, '<>')
        ->accessCheck(FALSE)
        ->sort('published_at', 'DESC')
        ->range(0, $limit)
        ->execute();

      if ($ids) {
        $related = $storage->loadMultiple($ids);
      }
    }

    // Completar con posts recientes si faltan.
    if (count($related) < $limit) {
      $excludeIds = array_merge([$postId], array_keys($related));
      $remaining = $limit - count($related);

      $ids = $storage->getQuery()
        ->condition('tenant_id', $tenantId)
        ->condition('status', 'published')
        ->condition('id', $excludeIds, 'NOT IN')
        ->accessCheck(FALSE)
        ->sort('published_at', 'DESC')
        ->range(0, $remaining)
        ->execute();

      if ($ids) {
        $related += $storage->loadMultiple($ids);
      }
    }

    return array_values($related);
  }

  /**
   * Obtiene posts anterior y siguiente para navegacion.
   */
  public function getAdjacentPosts(int $postId): array {
    $post = $this->getPost($postId);
    if (!$post) {
      return ['prev' => NULL, 'next' => NULL];
    }

    $tenantId = $post->getTenantId();
    $publishedAt = $post->get('published_at')->value;
    $storage = $this->entityTypeManager->getStorage('blog_post');

    // Post anterior (mas antiguo).
    $prevIds = $storage->getQuery()
      ->condition('tenant_id', $tenantId)
      ->condition('status', 'published')
      ->condition('published_at', $publishedAt, '<')
      ->accessCheck(FALSE)
      ->sort('published_at', 'DESC')
      ->range(0, 1)
      ->execute();

    // Post siguiente (mas reciente).
    $nextIds = $storage->getQuery()
      ->condition('tenant_id', $tenantId)
      ->condition('status', 'published')
      ->condition('published_at', $publishedAt, '>')
      ->accessCheck(FALSE)
      ->sort('published_at', 'ASC')
      ->range(0, 1)
      ->execute();

    return [
      'prev' => $prevIds ? $storage->load(reset($prevIds)) : NULL,
      'next' => $nextIds ? $storage->load(reset($nextIds)) : NULL,
    ];
  }

  /**
   * Incrementa el contador de visitas de un post.
   */
  public function trackView(int $postId): void {
    $this->database->update('blog_post')
      ->expression('views_count', 'views_count + 1')
      ->condition('id', $postId)
      ->execute();
  }

  /**
   * Publica un post.
   */
  public function publishPost(int $postId): bool {
    $post = $this->getPost($postId);
    if (!$post) {
      return FALSE;
    }

    $post->set('status', 'published');
    if (!$post->get('published_at')->value) {
      $post->set('published_at', date('Y-m-d\TH:i:s'));
    }
    $post->save();

    $this->logger->info('Post publicado: @id (@title)', [
      '@id' => $postId,
      '@title' => $post->getTitle(),
    ]);

    return TRUE;
  }

  /**
   * Programa la publicacion de un post.
   */
  public function schedulePost(int $postId, string $scheduledAt): bool {
    $post = $this->getPost($postId);
    if (!$post) {
      return FALSE;
    }

    $post->set('status', 'scheduled');
    $post->set('scheduled_at', $scheduledAt);
    $post->save();

    $this->logger->info('Post programado: @id para @date', [
      '@id' => $postId,
      '@date' => $scheduledAt,
    ]);

    return TRUE;
  }

  /**
   * Calcula el tiempo de lectura basado en el contenido.
   *
   * Usa una media de 200 palabras por minuto.
   */
  public function calculateReadingTime(string $content): int {
    $wordCount = str_word_count(strip_tags($content));
    $minutes = (int) ceil($wordCount / 200);

    return max(1, $minutes);
  }

  // ========================================================================
  // Categorias
  // ========================================================================

  /**
   * Obtiene todas las categorias activas del tenant.
   */
  public function listCategories(): array {
    $tenantId = $this->getTenantId();
    $storage = $this->entityTypeManager->getStorage('blog_category');

    $ids = $storage->getQuery()
      ->condition('tenant_id', $tenantId)
      ->accessCheck(FALSE)
      ->sort('name', 'ASC')
      ->execute();

    return $ids ? array_values($storage->loadMultiple($ids)) : [];
  }

  /**
   * Obtiene una categoria por slug.
   */
  public function getCategoryBySlug(string $slug): ?object {
    $tenantId = $this->getTenantId();
    $storage = $this->entityTypeManager->getStorage('blog_category');

    $ids = $storage->getQuery()
      ->condition('tenant_id', $tenantId)
      ->condition('slug', $slug)
      ->accessCheck(FALSE)
      ->range(0, 1)
      ->execute();

    return $ids ? $storage->load(reset($ids)) : NULL;
  }

  /**
   * Actualiza el cache de conteo de posts por categoria.
   */
  public function updateCategoryPostsCount(int $categoryId): void {
    $tenantId = $this->getTenantId();
    $storage = $this->entityTypeManager->getStorage('blog_post');

    $count = (int) $storage->getQuery()
      ->condition('tenant_id', $tenantId)
      ->condition('category_id', $categoryId)
      ->condition('status', 'published')
      ->accessCheck(FALSE)
      ->count()
      ->execute();

    $category = $this->entityTypeManager->getStorage('blog_category')->load($categoryId);
    if ($category) {
      // Si la entidad blog_category tiene un campo para el conteo.
      if ($category->hasField('posts_count')) {
        $category->set('posts_count', $count);
        $category->save();
      }
    }
  }

  // ========================================================================
  // Autores
  // ========================================================================

  /**
   * Obtiene todos los autores activos del tenant.
   */
  public function listAuthors(): array {
    $tenantId = $this->getTenantId();
    $storage = $this->entityTypeManager->getStorage('blog_author');

    $ids = $storage->getQuery()
      ->condition('tenant_id', $tenantId)
      ->condition('is_active', TRUE)
      ->accessCheck(FALSE)
      ->sort('display_name', 'ASC')
      ->execute();

    return $ids ? array_values($storage->loadMultiple($ids)) : [];
  }

  /**
   * Obtiene un autor por slug.
   */
  public function getAuthorBySlug(string $slug): ?object {
    $tenantId = $this->getTenantId();
    $storage = $this->entityTypeManager->getStorage('blog_author');

    $ids = $storage->getQuery()
      ->condition('tenant_id', $tenantId)
      ->condition('slug', $slug)
      ->accessCheck(FALSE)
      ->range(0, 1)
      ->execute();

    return $ids ? $storage->load(reset($ids)) : NULL;
  }

  // ========================================================================
  // Estadisticas
  // ========================================================================

  /**
   * Obtiene estadisticas del blog para el tenant actual.
   */
  public function getStats(): array {
    $tenantId = $this->getTenantId();
    $storage = $this->entityTypeManager->getStorage('blog_post');

    $total = (int) $storage->getQuery()
      ->condition('tenant_id', $tenantId)
      ->accessCheck(FALSE)
      ->count()
      ->execute();

    $published = (int) $storage->getQuery()
      ->condition('tenant_id', $tenantId)
      ->condition('status', 'published')
      ->accessCheck(FALSE)
      ->count()
      ->execute();

    $draft = (int) $storage->getQuery()
      ->condition('tenant_id', $tenantId)
      ->condition('status', 'draft')
      ->accessCheck(FALSE)
      ->count()
      ->execute();

    // Total de visitas.
    $totalViews = 0;
    if ($this->database->schema()->tableExists('blog_post')) {
      $viewsQuery = $this->database->select('blog_post', 'bp')
        ->condition('bp.tenant_id', $tenantId);
      $viewsQuery->addExpression('SUM(views_count)', 'total_views');
      $totalViews = (int) $viewsQuery->execute()->fetchField();
    }

    // Categorias activas.
    $categories = (int) $this->entityTypeManager->getStorage('blog_category')
      ->getQuery()
      ->condition('tenant_id', $tenantId)
      ->accessCheck(FALSE)
      ->count()
      ->execute();

    // Autores activos.
    $authors = (int) $this->entityTypeManager->getStorage('blog_author')
      ->getQuery()
      ->condition('tenant_id', $tenantId)
      ->condition('is_active', TRUE)
      ->accessCheck(FALSE)
      ->count()
      ->execute();

    return [
      'total_posts' => $total,
      'published' => $published,
      'draft' => $draft,
      'total_views' => $totalViews,
      'categories' => $categories,
      'authors' => $authors,
    ];
  }

  /**
   * Obtiene los posts populares (mas vistos).
   */
  public function getPopularPosts(int $limit = 5): array {
    $tenantId = $this->getTenantId();
    $storage = $this->entityTypeManager->getStorage('blog_post');

    $ids = $storage->getQuery()
      ->condition('tenant_id', $tenantId)
      ->condition('status', 'published')
      ->accessCheck(FALSE)
      ->sort('views_count', 'DESC')
      ->range(0, $limit)
      ->execute();

    return $ids ? array_values($storage->loadMultiple($ids)) : [];
  }

  /**
   * Obtiene todas las tags unicas del tenant.
   */
  public function getAllTags(): array {
    $tenantId = $this->getTenantId();

    $result = $this->database->select('blog_post', 'bp')
      ->fields('bp', ['tags'])
      ->condition('bp.tenant_id', $tenantId)
      ->condition('bp.status', 'published')
      ->isNotNull('bp.tags')
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

  /**
   * Publica posts programados cuya fecha ha pasado.
   *
   * Se ejecuta via cron.
   */
  public function publishScheduledPosts(): int {
    $storage = $this->entityTypeManager->getStorage('blog_post');
    $now = date('Y-m-d\TH:i:s');

    $ids = $storage->getQuery()
      ->condition('status', 'scheduled')
      ->condition('scheduled_at', $now, '<=')
      ->accessCheck(FALSE)
      ->execute();

    $count = 0;
    if ($ids) {
      $posts = $storage->loadMultiple($ids);
      foreach ($posts as $post) {
        $post->set('status', 'published');
        $post->set('published_at', $post->get('scheduled_at')->value);
        $post->save();
        $count++;

        $this->logger->info('Post programado publicado: @id (@title)', [
          '@id' => $post->id(),
          '@title' => $post->label(),
        ]);
      }
    }

    return $count;
  }

}
