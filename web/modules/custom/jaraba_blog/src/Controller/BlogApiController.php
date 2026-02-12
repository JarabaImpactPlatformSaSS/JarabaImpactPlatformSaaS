<?php

declare(strict_types=1);

namespace Drupal\jaraba_blog\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_blog\Service\BlogService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller API REST para el blog.
 *
 * Endpoints para CRUD de posts, categorias y autores.
 * Todos los endpoints requieren autenticacion.
 */
class BlogApiController extends ControllerBase {

  /**
   * Constructor.
   */
  public function __construct(
    protected BlogService $blogService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_blog.blog'),
    );
  }

  // ========================================================================
  // Posts
  // ========================================================================

  /**
   * GET /api/v1/blog/posts - Listar posts paginados.
   */
  public function listPosts(Request $request): JsonResponse {
    try {
      $page = max(0, (int) $request->query->get('page', 0));
      $limit = min(50, max(1, (int) $request->query->get('limit', 12)));
      $filters = [];

      if ($request->query->has('status')) {
        $filters['status'] = $request->query->get('status');
      }
      if ($request->query->has('category_id')) {
        $filters['category_id'] = (int) $request->query->get('category_id');
      }
      if ($request->query->has('author_id')) {
        $filters['author_id'] = (int) $request->query->get('author_id');
      }
      if ($request->query->has('is_featured')) {
        $filters['is_featured'] = TRUE;
      }
      if ($request->query->has('q')) {
        $filters['search'] = $request->query->get('q');
      }

      $result = $this->blogService->listPosts($filters, $page, $limit);

      return new JsonResponse([
        'success' => TRUE,
        'data' => array_map([$this, 'serializePost'], $result['posts']),
        'meta' => [
          'total' => $result['total'],
          'pages' => $result['pages'],
          'page' => $page,
          'limit' => $limit,
        ],
      ]);
    }
    catch (\Exception $e) {
      return $this->errorResponse('Error al listar posts.', $e);
    }
  }

  /**
   * GET /api/v1/blog/posts/{id} - Detalle de post.
   */
  public function show(int $id): JsonResponse {
    try {
      $post = $this->blogService->getPost($id);
      if (!$post) {
        return new JsonResponse(['success' => FALSE, 'error' => 'Post no encontrado.'], 404);
      }

      return new JsonResponse([
        'success' => TRUE,
        'data' => $this->serializePost($post),
      ]);
    }
    catch (\Exception $e) {
      return $this->errorResponse('Error al obtener post.', $e);
    }
  }

  /**
   * POST /api/v1/blog/posts - Crear post.
   */
  public function store(Request $request): JsonResponse {
    try {
      $data = json_decode($request->getContent(), TRUE);
      if (!$data || empty($data['title'])) {
        return new JsonResponse(['success' => FALSE, 'error' => 'Titulo requerido.'], 400);
      }

      $storage = $this->entityTypeManager()->getStorage('blog_post');

      // Generar slug si no se proporciona.
      $slug = $data['slug'] ?? $this->generateSlug($data['title']);

      $entity = $storage->create([
        'tenant_id' => $data['tenant_id'] ?? NULL,
        'title' => $data['title'],
        'slug' => $slug,
        'excerpt' => $data['excerpt'] ?? '',
        'body' => $data['body'] ?? '',
        'category_id' => $data['category_id'] ?? NULL,
        'author_id' => $data['author_id'] ?? NULL,
        'tags' => $data['tags'] ?? '',
        'status' => $data['status'] ?? 'draft',
        'is_featured' => $data['is_featured'] ?? FALSE,
        'meta_title' => $data['meta_title'] ?? '',
        'meta_description' => $data['meta_description'] ?? '',
        'schema_type' => $data['schema_type'] ?? 'BlogPosting',
      ]);

      // Calcular reading time.
      if (!empty($data['body'])) {
        $entity->set('reading_time', $this->blogService->calculateReadingTime($data['body']));
      }

      $entity->save();

      return new JsonResponse([
        'success' => TRUE,
        'data' => $this->serializePost($entity),
      ], 201);
    }
    catch (\Exception $e) {
      return $this->errorResponse('Error al crear post.', $e);
    }
  }

  /**
   * PATCH /api/v1/blog/posts/{id} - Actualizar post.
   */
  public function update(int $id, Request $request): JsonResponse {
    try {
      $post = $this->blogService->getPost($id);
      if (!$post) {
        return new JsonResponse(['success' => FALSE, 'error' => 'Post no encontrado.'], 404);
      }

      $data = json_decode($request->getContent(), TRUE);
      if (!$data) {
        return new JsonResponse(['success' => FALSE, 'error' => 'Datos invalidos.'], 400);
      }

      $updatable = [
        'title', 'slug', 'excerpt', 'body', 'category_id', 'author_id',
        'tags', 'status', 'is_featured', 'meta_title', 'meta_description',
        'schema_type', 'published_at', 'scheduled_at',
      ];

      foreach ($updatable as $field) {
        if (array_key_exists($field, $data)) {
          $post->set($field, $data[$field]);
        }
      }

      // Recalcular reading time si cambio el body.
      if (array_key_exists('body', $data)) {
        $post->set('reading_time', $this->blogService->calculateReadingTime($data['body']));
      }

      $post->save();

      return new JsonResponse([
        'success' => TRUE,
        'data' => $this->serializePost($post),
      ]);
    }
    catch (\Exception $e) {
      return $this->errorResponse('Error al actualizar post.', $e);
    }
  }

  /**
   * DELETE /api/v1/blog/posts/{id} - Eliminar post.
   */
  public function remove(int $id): JsonResponse {
    try {
      $post = $this->blogService->getPost($id);
      if (!$post) {
        return new JsonResponse(['success' => FALSE, 'error' => 'Post no encontrado.'], 404);
      }

      $post->delete();

      return new JsonResponse(['success' => TRUE]);
    }
    catch (\Exception $e) {
      return $this->errorResponse('Error al eliminar post.', $e);
    }
  }

  /**
   * POST /api/v1/blog/posts/{id}/publish - Publicar post.
   */
  public function publish(int $id): JsonResponse {
    try {
      $success = $this->blogService->publishPost($id);
      if (!$success) {
        return new JsonResponse(['success' => FALSE, 'error' => 'Post no encontrado.'], 404);
      }

      return new JsonResponse(['success' => TRUE]);
    }
    catch (\Exception $e) {
      return $this->errorResponse('Error al publicar post.', $e);
    }
  }

  /**
   * POST /api/v1/blog/posts/{id}/schedule - Programar publicacion.
   */
  public function schedule(int $id, Request $request): JsonResponse {
    try {
      $data = json_decode($request->getContent(), TRUE);
      if (empty($data['scheduled_at'])) {
        return new JsonResponse(['success' => FALSE, 'error' => 'Fecha requerida.'], 400);
      }

      $success = $this->blogService->schedulePost($id, $data['scheduled_at']);
      if (!$success) {
        return new JsonResponse(['success' => FALSE, 'error' => 'Post no encontrado.'], 404);
      }

      return new JsonResponse(['success' => TRUE]);
    }
    catch (\Exception $e) {
      return $this->errorResponse('Error al programar post.', $e);
    }
  }

  /**
   * GET /api/v1/blog/posts/{id}/related - Posts relacionados.
   */
  public function related(int $id): JsonResponse {
    try {
      $posts = $this->blogService->getRelatedPosts($id);

      return new JsonResponse([
        'success' => TRUE,
        'data' => array_map([$this, 'serializePost'], $posts),
      ]);
    }
    catch (\Exception $e) {
      return $this->errorResponse('Error al obtener posts relacionados.', $e);
    }
  }

  /**
   * POST /api/v1/blog/posts/{id}/view - Incrementar visitas.
   */
  public function trackView(int $id): JsonResponse {
    try {
      $this->blogService->trackView($id);
      return new JsonResponse(['success' => TRUE]);
    }
    catch (\Exception $e) {
      return $this->errorResponse('Error al registrar visita.', $e);
    }
  }

  // ========================================================================
  // Categories
  // ========================================================================

  /**
   * GET /api/v1/blog/categories - Listar categorias.
   */
  public function listCategories(): JsonResponse {
    try {
      $categories = $this->blogService->listCategories();

      return new JsonResponse([
        'success' => TRUE,
        'data' => array_map([$this, 'serializeCategory'], $categories),
      ]);
    }
    catch (\Exception $e) {
      return $this->errorResponse('Error al listar categorias.', $e);
    }
  }

  /**
   * POST /api/v1/blog/categories - Crear categoria.
   */
  public function storeCategory(Request $request): JsonResponse {
    try {
      $data = json_decode($request->getContent(), TRUE);
      if (empty($data['name'])) {
        return new JsonResponse(['success' => FALSE, 'error' => 'Nombre requerido.'], 400);
      }

      $storage = $this->entityTypeManager()->getStorage('blog_category');
      $entity = $storage->create([
        'tenant_id' => $data['tenant_id'] ?? NULL,
        'name' => $data['name'],
        'slug' => $data['slug'] ?? $this->generateSlug($data['name']),
        'description' => $data['description'] ?? '',
        'parent_id' => $data['parent_id'] ?? NULL,
        'icon' => $data['icon'] ?? 'folder',
        'color' => $data['color'] ?? '#233D63',
        'weight' => $data['weight'] ?? 0,
        'is_active' => $data['is_active'] ?? TRUE,
        'meta_title' => $data['meta_title'] ?? '',
        'meta_description' => $data['meta_description'] ?? '',
      ]);

      $entity->save();

      return new JsonResponse([
        'success' => TRUE,
        'data' => $this->serializeCategory($entity),
      ], 201);
    }
    catch (\Exception $e) {
      return $this->errorResponse('Error al crear categoria.', $e);
    }
  }

  /**
   * PATCH /api/v1/blog/categories/{id} - Actualizar categoria.
   */
  public function updateCategory(int $id, Request $request): JsonResponse {
    try {
      $storage = $this->entityTypeManager()->getStorage('blog_category');
      $category = $storage->load($id);
      if (!$category) {
        return new JsonResponse(['success' => FALSE, 'error' => 'Categoria no encontrada.'], 404);
      }

      $data = json_decode($request->getContent(), TRUE);
      $updatable = ['name', 'slug', 'description', 'parent_id', 'icon', 'color', 'weight', 'is_active', 'meta_title', 'meta_description'];

      foreach ($updatable as $field) {
        if (array_key_exists($field, $data)) {
          $category->set($field, $data[$field]);
        }
      }

      $category->save();

      return new JsonResponse([
        'success' => TRUE,
        'data' => $this->serializeCategory($category),
      ]);
    }
    catch (\Exception $e) {
      return $this->errorResponse('Error al actualizar categoria.', $e);
    }
  }

  /**
   * DELETE /api/v1/blog/categories/{id} - Eliminar categoria.
   */
  public function removeCategory(int $id): JsonResponse {
    try {
      $storage = $this->entityTypeManager()->getStorage('blog_category');
      $category = $storage->load($id);
      if (!$category) {
        return new JsonResponse(['success' => FALSE, 'error' => 'Categoria no encontrada.'], 404);
      }

      $category->delete();

      return new JsonResponse(['success' => TRUE]);
    }
    catch (\Exception $e) {
      return $this->errorResponse('Error al eliminar categoria.', $e);
    }
  }

  // ========================================================================
  // Authors
  // ========================================================================

  /**
   * GET /api/v1/blog/authors - Listar autores.
   */
  public function listAuthors(): JsonResponse {
    try {
      $authors = $this->blogService->listAuthors();

      return new JsonResponse([
        'success' => TRUE,
        'data' => array_map([$this, 'serializeAuthor'], $authors),
      ]);
    }
    catch (\Exception $e) {
      return $this->errorResponse('Error al listar autores.', $e);
    }
  }

  /**
   * POST /api/v1/blog/authors - Crear autor.
   */
  public function storeAuthor(Request $request): JsonResponse {
    try {
      $data = json_decode($request->getContent(), TRUE);
      if (empty($data['display_name'])) {
        return new JsonResponse(['success' => FALSE, 'error' => 'Nombre requerido.'], 400);
      }

      $storage = $this->entityTypeManager()->getStorage('blog_author');
      $entity = $storage->create([
        'tenant_id' => $data['tenant_id'] ?? NULL,
        'user_id' => $data['user_id'] ?? NULL,
        'display_name' => $data['display_name'],
        'slug' => $data['slug'] ?? $this->generateSlug($data['display_name']),
        'bio' => $data['bio'] ?? '',
        'social_twitter' => $data['social_twitter'] ?? '',
        'social_linkedin' => $data['social_linkedin'] ?? '',
        'social_website' => $data['social_website'] ?? '',
        'is_active' => $data['is_active'] ?? TRUE,
      ]);

      $entity->save();

      return new JsonResponse([
        'success' => TRUE,
        'data' => $this->serializeAuthor($entity),
      ], 201);
    }
    catch (\Exception $e) {
      return $this->errorResponse('Error al crear autor.', $e);
    }
  }

  /**
   * PATCH /api/v1/blog/authors/{id} - Actualizar autor.
   */
  public function updateAuthor(int $id, Request $request): JsonResponse {
    try {
      $storage = $this->entityTypeManager()->getStorage('blog_author');
      $author = $storage->load($id);
      if (!$author) {
        return new JsonResponse(['success' => FALSE, 'error' => 'Autor no encontrado.'], 404);
      }

      $data = json_decode($request->getContent(), TRUE);
      $updatable = ['display_name', 'slug', 'bio', 'social_twitter', 'social_linkedin', 'social_website', 'is_active'];

      foreach ($updatable as $field) {
        if (array_key_exists($field, $data)) {
          $author->set($field, $data[$field]);
        }
      }

      $author->save();

      return new JsonResponse([
        'success' => TRUE,
        'data' => $this->serializeAuthor($author),
      ]);
    }
    catch (\Exception $e) {
      return $this->errorResponse('Error al actualizar autor.', $e);
    }
  }

  // ========================================================================
  // Stats
  // ========================================================================

  /**
   * GET /api/v1/blog/stats - Estadisticas del blog.
   */
  public function stats(): JsonResponse {
    try {
      return new JsonResponse([
        'success' => TRUE,
        'data' => $this->blogService->getStats(),
      ]);
    }
    catch (\Exception $e) {
      return $this->errorResponse('Error al obtener estadisticas.', $e);
    }
  }

  // ========================================================================
  // Serializers
  // ========================================================================

  /**
   * Serializa un post a array.
   */
  protected function serializePost(object $post): array {
    return [
      'id' => (int) $post->id(),
      'title' => $post->getTitle(),
      'slug' => $post->getSlug(),
      'excerpt' => $post->getExcerpt(),
      'body' => $post->getBody(),
      'category_id' => $post->getCategoryId(),
      'author_id' => $post->getAuthorId(),
      'tags' => $post->getTagsArray(),
      'status' => $post->getStatus(),
      'is_featured' => $post->isFeatured(),
      'reading_time' => $post->getReadingTime(),
      'views_count' => $post->getViewsCount(),
      'published_at' => $post->get('published_at')->value,
      'created' => $post->get('created')->value,
      'changed' => $post->get('changed')->value,
    ];
  }

  /**
   * Serializa una categoria a array.
   */
  protected function serializeCategory(object $category): array {
    return [
      'id' => (int) $category->id(),
      'name' => $category->getName(),
      'slug' => $category->getSlug(),
      'description' => (string) ($category->get('description')->value ?? ''),
      'parent_id' => $category->getParentId(),
      'icon' => $category->getIcon(),
      'color' => $category->getColor(),
      'weight' => (int) $category->get('weight')->value,
      'is_active' => $category->isActive(),
      'posts_count' => $category->getPostsCount(),
    ];
  }

  /**
   * Serializa un autor a array.
   */
  protected function serializeAuthor(object $author): array {
    return [
      'id' => (int) $author->id(),
      'display_name' => $author->getDisplayName(),
      'slug' => $author->getSlug(),
      'bio' => $author->getBio(),
      'social_links' => $author->getSocialLinks(),
      'is_active' => $author->isActive(),
      'posts_count' => $author->getPostsCount(),
    ];
  }

  // ========================================================================
  // Helpers
  // ========================================================================

  /**
   * Genera un slug URL-friendly a partir de texto.
   */
  protected function generateSlug(string $text): string {
    $text = mb_strtolower($text);
    $replacements = [
      'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
      'ñ' => 'n', 'ü' => 'u', 'ç' => 'c',
    ];
    $text = strtr($text, $replacements);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    $text = preg_replace('/-+/', '-', $text);

    return $text ?: 'entrada';
  }

  /**
   * Genera una respuesta de error estandarizada.
   */
  protected function errorResponse(string $userMessage, \Exception $e): JsonResponse {
    \Drupal::logger('jaraba_blog')->error('@message: @error', [
      '@message' => $userMessage,
      '@error' => $e->getMessage(),
    ]);

    return new JsonResponse([
      'success' => FALSE,
      'error' => $userMessage,
    ], 500);
  }

}
