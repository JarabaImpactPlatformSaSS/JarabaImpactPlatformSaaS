178
ESPECIFICACIÓN TÉCNICA
Blog System Nativo
Posts | Categorías | Tags | RSS | Editor | SEO
Ecosistema Jaraba | EDI Google Antigravity
Versión:	1.0.0
Fecha:	27 de Enero de 2026
Horas Estimadas:	60-80 horas
 
Índice de Contenidos
1. Resumen Ejecutivo
2. Arquitectura de Datos
3. API de Posts
4. Categorías y Tags
5. Templates del Blog
6. Editor de Posts
7. SEO del Blog
8. RSS Feed
9. Límites por Plan
10. Roadmap de Implementación
 
1. Resumen Ejecutivo
El Blog System Nativo proporciona un sistema de blogging completo integrado en el Site Builder, permitiendo a tenants crear y gestionar blogs con categorías, tags, autores, y optimización SEO.

Capacidades del Sistema
Posts con Markdown/HTML/Bloques | Categorías jerárquicas | Tags | Múltiples autores | Imágenes destacadas | SEO automático | RSS Feed | Posts relacionados | Búsqueda fulltext | Programación de publicación

1.1. Características Principales
•	Editor de posts con Markdown, HTML o bloques del Page Builder
•	Categorías jerárquicas con colores e imágenes
•	Sistema de tags con autocompletado
•	Perfiles de autor con bio y redes sociales
•	Imágenes destacadas con lazy loading
•	Tiempo de lectura calculado automáticamente
•	Posts destacados (featured) y fijados (sticky)
•	Programación de publicación
•	Búsqueda fulltext
•	RSS Feed automático
•	Schema.org BlogPosting
1.2. Layouts Disponibles
Layout	Descripción	Uso Recomendado
grid	Tarjetas en cuadrícula	Blogs visuales, portfolio
list	Lista vertical con imagen lateral	Blogs de noticias, artículos
masonry	Grid tipo Pinterest	Blogs de fotografía, diseño
cards	Cards grandes destacados	Blogs corporativos
 
2. Arquitectura de Datos
Esquema completo de base de datos para el sistema de blog.
 blog_schema.sql
-- =============================================
-- BLOG SYSTEM - ESQUEMA DE BASE DE DATOS
-- =============================================
 
-- Configuración del blog por tenant
CREATE TABLE blog_config (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT UNSIGNED NOT NULL UNIQUE,
  
  -- Configuración general
  blog_title VARCHAR(255) DEFAULT 'Blog',
  blog_description TEXT,
  posts_per_page INT DEFAULT 10,
  
  -- URLs
  blog_base_path VARCHAR(100) DEFAULT '/blog',
  category_base_path VARCHAR(100) DEFAULT '/blog/categoria',
  tag_base_path VARCHAR(100) DEFAULT '/blog/tag',
  author_base_path VARCHAR(100) DEFAULT '/blog/autor',
  
  -- Página de índice (page_content del Page Builder)
  index_page_id INT UNSIGNED,
  
  -- Layout del listado
  list_layout ENUM('grid', 'list', 'masonry', 'cards') DEFAULT 'grid',
  grid_columns INT DEFAULT 3,
  show_featured_image BOOLEAN DEFAULT TRUE,
  show_excerpt BOOLEAN DEFAULT TRUE,
  excerpt_length INT DEFAULT 150,
  show_author BOOLEAN DEFAULT TRUE,
  show_date BOOLEAN DEFAULT TRUE,
  show_category BOOLEAN DEFAULT TRUE,
  show_reading_time BOOLEAN DEFAULT TRUE,
  
  -- Layout del post
  post_layout ENUM('standard', 'wide', 'fullwidth', 'sidebar') DEFAULT 'standard',
  sidebar_position ENUM('left', 'right') DEFAULT 'right',
  show_toc BOOLEAN DEFAULT FALSE, -- Table of contents
  show_share_buttons BOOLEAN DEFAULT TRUE,
  show_related_posts BOOLEAN DEFAULT TRUE,
  related_posts_count INT DEFAULT 3,
  show_author_bio BOOLEAN DEFAULT TRUE,
  show_comments BOOLEAN DEFAULT FALSE,
  
  -- SEO
  default_og_image INT UNSIGNED,
  schema_type ENUM('BlogPosting', 'Article', 'NewsArticle') DEFAULT 'BlogPosting',
  
  -- RSS
  rss_enabled BOOLEAN DEFAULT TRUE,
  rss_full_content BOOLEAN DEFAULT FALSE,
  
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  changed TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (tenant_id) REFERENCES groups_field_data(id) ON DELETE CASCADE,
  FOREIGN KEY (index_page_id) REFERENCES page_content(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 
-- Posts del blog
CREATE TABLE blog_post (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  uuid VARCHAR(128) NOT NULL UNIQUE,
  tenant_id INT UNSIGNED NOT NULL,
  
  -- Contenido básico
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL,
  excerpt TEXT,
  content LONGTEXT NOT NULL, -- Contenido en Markdown o HTML
  content_format ENUM('markdown', 'html', 'blocks') DEFAULT 'markdown',
  
  -- Si usa Page Builder para contenido
  page_content_id INT UNSIGNED, -- FK page_content (alternativa a content)
  
  -- Media
  featured_image_id INT UNSIGNED, -- FK file_managed
  featured_image_alt VARCHAR(255),
  featured_image_caption TEXT,
  
  -- Taxonomía
  primary_category_id INT UNSIGNED, -- FK blog_category
  
  -- Autor
  author_id INT UNSIGNED NOT NULL, -- FK users
  
  -- Estado y fechas
  status ENUM('draft', 'pending', 'published', 'scheduled', 'archived') DEFAULT 'draft',
  published_at TIMESTAMP NULL,
  scheduled_at TIMESTAMP NULL,
  
  -- SEO
  meta_title VARCHAR(60),
  meta_description VARCHAR(160),
  focus_keyword VARCHAR(100),
  canonical_url VARCHAR(500),
  noindex BOOLEAN DEFAULT FALSE,
  
  -- Engagement
  views_count INT UNSIGNED DEFAULT 0,
  reading_time_minutes INT DEFAULT 0,
  
  -- Opciones
  allow_comments BOOLEAN DEFAULT TRUE,
  is_featured BOOLEAN DEFAULT FALSE,
  is_sticky BOOLEAN DEFAULT FALSE, -- Fijar arriba
  
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  changed TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (tenant_id) REFERENCES groups_field_data(id) ON DELETE CASCADE,
  FOREIGN KEY (primary_category_id) REFERENCES blog_category(id) ON DELETE SET NULL,
  FOREIGN KEY (page_content_id) REFERENCES page_content(id) ON DELETE SET NULL,
  
  UNIQUE KEY unique_tenant_slug (tenant_id, slug),
  INDEX idx_tenant_status (tenant_id, status),
  INDEX idx_published (tenant_id, status, published_at DESC),
  INDEX idx_featured (tenant_id, is_featured, published_at DESC),
  FULLTEXT INDEX ft_content (title, excerpt, content)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 
-- Categorías del blog
CREATE TABLE blog_category (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT UNSIGNED NOT NULL,
  
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(100) NOT NULL,
  description TEXT,
  
  parent_id INT UNSIGNED DEFAULT NULL, -- Jerarquía de categorías
  
  -- Media
  image_id INT UNSIGNED,
  color VARCHAR(7), -- Color para badges
  
  -- SEO
  meta_title VARCHAR(60),
  meta_description VARCHAR(160),
  
  weight INT DEFAULT 0,
  posts_count INT UNSIGNED DEFAULT 0, -- Cache de conteo
  
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (tenant_id) REFERENCES groups_field_data(id) ON DELETE CASCADE,
  FOREIGN KEY (parent_id) REFERENCES blog_category(id) ON DELETE SET NULL,
  
  UNIQUE KEY unique_tenant_slug (tenant_id, slug),
  INDEX idx_parent (tenant_id, parent_id, weight)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 
-- Tags del blog
CREATE TABLE blog_tag (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT UNSIGNED NOT NULL,
  
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(100) NOT NULL,
  
  posts_count INT UNSIGNED DEFAULT 0,
  
  FOREIGN KEY (tenant_id) REFERENCES groups_field_data(id) ON DELETE CASCADE,
  UNIQUE KEY unique_tenant_slug (tenant_id, slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 
-- Relación posts <-> categorías (muchos a muchos)
CREATE TABLE blog_post_category (
  post_id INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NOT NULL,
  
  PRIMARY KEY (post_id, category_id),
  FOREIGN KEY (post_id) REFERENCES blog_post(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES blog_category(id) ON DELETE CASCADE
) ENGINE=InnoDB;
 
-- Relación posts <-> tags (muchos a muchos)
CREATE TABLE blog_post_tag (
  post_id INT UNSIGNED NOT NULL,
  tag_id INT UNSIGNED NOT NULL,
  
  PRIMARY KEY (post_id, tag_id),
  FOREIGN KEY (post_id) REFERENCES blog_post(id) ON DELETE CASCADE,
  FOREIGN KEY (tag_id) REFERENCES blog_tag(id) ON DELETE CASCADE
) ENGINE=InnoDB;
 
-- Autores del blog (perfil extendido)
CREATE TABLE blog_author (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL, -- FK users
  
  display_name VARCHAR(255) NOT NULL,
  slug VARCHAR(100) NOT NULL,
  bio TEXT,
  avatar_id INT UNSIGNED,
  
  -- Social links
  website_url VARCHAR(500),
  twitter_handle VARCHAR(50),
  linkedin_url VARCHAR(500),
  
  posts_count INT UNSIGNED DEFAULT 0,
  is_active BOOLEAN DEFAULT TRUE,
  
  FOREIGN KEY (tenant_id) REFERENCES groups_field_data(id) ON DELETE CASCADE,
  UNIQUE KEY unique_tenant_user (tenant_id, user_id),
  UNIQUE KEY unique_tenant_slug (tenant_id, slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

 
3. API de Posts
API REST completa para gestión de posts del blog.
3.1. Endpoints
Método	Endpoint	Descripción
GET	/api/v1/blog/posts	Lista posts con filtros y paginación
GET	/api/v1/blog/posts/{slug}	Obtener post por slug
POST	/api/v1/blog/posts	Crear nuevo post
PUT	/api/v1/blog/posts/{id}	Actualizar post
DELETE	/api/v1/blog/posts/{id}	Eliminar post
POST	/api/v1/blog/posts/{id}/publish	Publicar post
POST	/api/v1/blog/posts/{id}/unpublish	Despublicar
POST	/api/v1/blog/posts/{id}/schedule	Programar publicación
POST	/api/v1/blog/posts/{id}/duplicate	Duplicar post
GET	/api/v1/blog/posts/{id}/related	Posts relacionados

3.2. Filtros de Listado
 list-filters.txt
GET /api/v1/blog/posts?
  page=0&
  limit=10&
  status=published&
  category=tecnologia&
  tag=javascript&
  author=pepe-jaraba&
  search=transformacion+digital&
  featured=true&
  order_by=published_at&
  order_dir=DESC

 
3.3. Controller de Posts
 BlogPostController.php
<?php
 
namespace Drupal\jaraba_blog\Controller;
 
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
 
/**
 * API Controller para Blog Posts.
 */
class BlogPostController extends ControllerBase {
 
  /**
   * GET /api/v1/blog/posts
   * Lista posts del blog con filtros y paginación.
   */
  public function listPosts(Request $request): JsonResponse {
    $tenantId = $this->tenantContext->getCurrentTenantId();
    
    // Parámetros
    $page = max(0, (int) $request->query->get('page', 0));
    $limit = min(50, max(1, (int) $request->query->get('limit', 10)));
    $status = $request->query->get('status', 'published');
    $category = $request->query->get('category');
    $tag = $request->query->get('tag');
    $author = $request->query->get('author');
    $search = $request->query->get('search');
    $featured = $request->query->get('featured');
    $orderBy = $request->query->get('order_by', 'published_at');
    $orderDir = $request->query->get('order_dir', 'DESC');
    
    // Query base
    $query = $this->database->select('blog_post', 'bp')
      ->fields('bp')
      ->condition('bp.tenant_id', $tenantId);
    
    // Filtros
    if ($status !== 'all') {
      $query->condition('bp.status', $status);
    }
    
    if ($category) {
      $query->join('blog_post_category', 'bpc', 'bp.id = bpc.post_id');
      $query->join('blog_category', 'bc', 'bpc.category_id = bc.id');
      $query->condition('bc.slug', $category);
    }
    
    if ($tag) {
      $query->join('blog_post_tag', 'bpt', 'bp.id = bpt.post_id');
      $query->join('blog_tag', 'bt', 'bpt.tag_id = bt.id');
      $query->condition('bt.slug', $tag);
    }
    
    if ($author) {
      $query->join('blog_author', 'ba', 'bp.author_id = ba.id');
      $query->condition('ba.slug', $author);
    }
    
    if ($search) {
      $query->condition($query->orConditionGroup()
        ->condition('bp.title', '%' . $search . '%', 'LIKE')
        ->condition('bp.excerpt', '%' . $search . '%', 'LIKE')
        ->condition('bp.content', '%' . $search . '%', 'LIKE')
      );
    }
    
    if ($featured !== NULL) {
      $query->condition('bp.is_featured', (bool) $featured);
    }
    
    // Ordenación
    $allowedOrderBy = ['published_at', 'title', 'views_count', 'created'];
    if (in_array($orderBy, $allowedOrderBy)) {
      // Posts sticky primero
      $query->orderBy('bp.is_sticky', 'DESC');
      $query->orderBy('bp.' . $orderBy, $orderDir);
    }
    
    // Contar total
    $countQuery = clone $query;
    $total = $countQuery->countQuery()->execute()->fetchField();
    
    // Paginación
    $query->range($page * $limit, $limit);
    
    $posts = $query->execute()->fetchAll();
    
    // Enriquecer posts
    foreach ($posts as &$post) {
      $this->enrichPost($post);
    }
    
    return new JsonResponse([
      'posts' => $posts,
      'pagination' => [
        'page' => $page,
        'limit' => $limit,
        'total' => (int) $total,
        'pages' => ceil($total / $limit),
      ],
    ]);
  }
 
  /**
   * GET /api/v1/blog/posts/{slug}
   * Obtiene un post por slug.
   */
  public function getPost(string $slug): JsonResponse {
    $tenantId = $this->tenantContext->getCurrentTenantId();
    
    $post = $this->database->select('blog_post', 'bp')
      ->fields('bp')
      ->condition('tenant_id', $tenantId)
      ->condition('slug', $slug)
      ->execute()
      ->fetchObject();
    
    if (!$post) {
      return new JsonResponse(['error' => 'Post not found'], 404);
    }
    
    // Verificar estado (solo admin puede ver drafts)
    if ($post->status !== 'published' && !$this->currentUser()->hasPermission('edit any blog_post')) {
      return new JsonResponse(['error' => 'Post not found'], 404);
    }
    
    // Incrementar views
    $this->database->update('blog_post')
      ->expression('views_count', 'views_count + 1')
      ->condition('id', $post->id)
      ->execute();
    
    $this->enrichPost($post, TRUE);
    
    // Cargar posts relacionados
    $post->related_posts = $this->getRelatedPosts($post);
    
    return new JsonResponse($post);
  }
 
  /**
   * POST /api/v1/blog/posts
   * Crea nuevo post.
   */
  public function createPost(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);
    $tenantId = $this->tenantContext->getCurrentTenantId();
    
    // Validar
    if (empty($data['title'])) {
      return new JsonResponse(['error' => 'Title is required'], 400);
    }
    
    // Generar slug
    $slug = $data['slug'] ?? $this->generateSlug($data['title']);
    $slug = $this->ensureUniqueSlug($tenantId, $slug);
    
    // Calcular tiempo de lectura
    $readingTime = $this->calculateReadingTime($data['content'] ?? '');
    
    // Obtener o crear autor
    $authorId = $this->getOrCreateAuthor($tenantId, $this->currentUser()->id());
    
    $id = $this->database->insert('blog_post')
      ->fields([
        'uuid' => $this->uuidGenerator->generate(),
        'tenant_id' => $tenantId,
        'title' => $data['title'],
        'slug' => $slug,
        'excerpt' => $data['excerpt'] ?? NULL,
        'content' => $data['content'] ?? '',
        'content_format' => $data['content_format'] ?? 'markdown',
        'page_content_id' => $data['page_content_id'] ?? NULL,
        'featured_image_id' => $data['featured_image_id'] ?? NULL,
        'featured_image_alt' => $data['featured_image_alt'] ?? NULL,
        'primary_category_id' => $data['primary_category_id'] ?? NULL,
        'author_id' => $authorId,
        'status' => $data['status'] ?? 'draft',
        'published_at' => $data['status'] === 'published' ? date('Y-m-d H:i:s') : NULL,
        'scheduled_at' => $data['scheduled_at'] ?? NULL,
        'meta_title' => $data['meta_title'] ?? NULL,
        'meta_description' => $data['meta_description'] ?? NULL,
        'focus_keyword' => $data['focus_keyword'] ?? NULL,
        'reading_time_minutes' => $readingTime,
        'is_featured' => $data['is_featured'] ?? FALSE,
        'is_sticky' => $data['is_sticky'] ?? FALSE,
      ])
      ->execute();
    
    // Guardar categorías
    if (!empty($data['categories'])) {
      foreach ($data['categories'] as $categoryId) {
        $this->database->insert('blog_post_category')
          ->fields(['post_id' => $id, 'category_id' => $categoryId])
          ->execute();
      }
      $this->updateCategoryCounts($data['categories']);
    }
    
    // Guardar tags
    if (!empty($data['tags'])) {
      $this->saveTags($id, $tenantId, $data['tags']);
    }
    
    return new JsonResponse(['id' => $id, 'slug' => $slug], 201);
  }
 
  /**
   * PUT /api/v1/blog/posts/{id}
   * Actualiza post existente.
   */
  public function updatePost(int $id, Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);
    $tenantId = $this->tenantContext->getCurrentTenantId();
    
    // Verificar existe
    $post = $this->loadPost($id, $tenantId);
    if (!$post) {
      return new JsonResponse(['error' => 'Post not found'], 404);
    }
    
    // Preparar campos
    $fields = [];
    
    if (isset($data['title'])) {
      $fields['title'] = $data['title'];
    }
    
    if (isset($data['slug']) && $data['slug'] !== $post->slug) {
      $fields['slug'] = $this->ensureUniqueSlug($tenantId, $data['slug'], $id);
    }
    
    if (isset($data['content'])) {
      $fields['content'] = $data['content'];
      $fields['reading_time_minutes'] = $this->calculateReadingTime($data['content']);
    }
    
    if (isset($data['excerpt'])) $fields['excerpt'] = $data['excerpt'];
    if (isset($data['featured_image_id'])) $fields['featured_image_id'] = $data['featured_image_id'];
    if (isset($data['primary_category_id'])) $fields['primary_category_id'] = $data['primary_category_id'];
    if (isset($data['meta_title'])) $fields['meta_title'] = $data['meta_title'];
    if (isset($data['meta_description'])) $fields['meta_description'] = $data['meta_description'];
    if (isset($data['is_featured'])) $fields['is_featured'] = $data['is_featured'];
    if (isset($data['is_sticky'])) $fields['is_sticky'] = $data['is_sticky'];
    
    // Cambio de estado
    if (isset($data['status'])) {
      $fields['status'] = $data['status'];
      if ($data['status'] === 'published' && !$post->published_at) {
        $fields['published_at'] = date('Y-m-d H:i:s');
      }
    }
    
    if (!empty($fields)) {
      $this->database->update('blog_post')
        ->fields($fields)
        ->condition('id', $id)
        ->execute();
    }
    
    // Actualizar categorías
    if (isset($data['categories'])) {
      $this->database->delete('blog_post_category')->condition('post_id', $id)->execute();
      foreach ($data['categories'] as $categoryId) {
        $this->database->insert('blog_post_category')
          ->fields(['post_id' => $id, 'category_id' => $categoryId])
          ->execute();
      }
    }
    
    // Actualizar tags
    if (isset($data['tags'])) {
      $this->database->delete('blog_post_tag')->condition('post_id', $id)->execute();
      $this->saveTags($id, $tenantId, $data['tags']);
    }
    
    return new JsonResponse(['success' => TRUE]);
  }
 
  /**
   * Enriquece un post con datos relacionados.
   */
  protected function enrichPost(object &$post, bool $full = FALSE): void {
    // Featured image
    if ($post->featured_image_id) {
      $file = $this->fileStorage->load($post->featured_image_id);
      if ($file) {
        $post->featured_image_url = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
      }
    }
    
    // Autor
    $author = $this->database->select('blog_author', 'ba')
      ->fields('ba', ['display_name', 'slug', 'avatar_id'])
      ->condition('id', $post->author_id)
      ->execute()
      ->fetchObject();
    $post->author = $author;
    
    // Categorías
    $post->categories = $this->database->select('blog_post_category', 'bpc')
      ->fields('bc', ['id', 'name', 'slug', 'color'])
      ->join('blog_category', 'bc', 'bpc.category_id = bc.id')
      ->condition('bpc.post_id', $post->id)
      ->execute()
      ->fetchAll();
    
    // Tags
    $post->tags = $this->database->select('blog_post_tag', 'bpt')
      ->fields('bt', ['id', 'name', 'slug'])
      ->join('blog_tag', 'bt', 'bpt.tag_id = bt.id')
      ->condition('bpt.post_id', $post->id)
      ->execute()
      ->fetchAll();
    
    // Contenido procesado (si es listado, solo excerpt)
    if ($full) {
      $post->content_rendered = $this->renderContent($post->content, $post->content_format);
    }
    
    // URL
    $blogConfig = $this->getBlogConfig($post->tenant_id);
    $post->url = $blogConfig['blog_base_path'] . '/' . $post->slug;
  }
 
  /**
   * Calcula tiempo de lectura en minutos.
   */
  protected function calculateReadingTime(string $content): int {
    $wordCount = str_word_count(strip_tags($content));
    $readingTime = ceil($wordCount / 200); // 200 palabras por minuto
    return max(1, $readingTime);
  }
 
  /**
   * Obtiene posts relacionados.
   */
  protected function getRelatedPosts(object $post, int $limit = 3): array {
    // Por categoría principal primero
    $query = $this->database->select('blog_post', 'bp')
      ->fields('bp', ['id', 'title', 'slug', 'excerpt', 'featured_image_id', 'published_at'])
      ->condition('bp.tenant_id', $post->tenant_id)
      ->condition('bp.status', 'published')
      ->condition('bp.id', $post->id, '<>')
      ->orderBy('bp.published_at', 'DESC')
      ->range(0, $limit);
    
    if ($post->primary_category_id) {
      $query->condition('bp.primary_category_id', $post->primary_category_id);
    }
    
    $related = $query->execute()->fetchAll();
    
    // Enriquecer
    foreach ($related as &$relatedPost) {
      $this->enrichPost($relatedPost);
    }
    
    return $related;
  }
}

 
4. Categorías y Tags
4.1. API de Categorías
Método	Endpoint	Descripción
GET	/api/v1/blog/categories	Lista categorías
POST	/api/v1/blog/categories	Crear categoría
PUT	/api/v1/blog/categories/{id}	Actualizar
DELETE	/api/v1/blog/categories/{id}	Eliminar
POST	/api/v1/blog/categories/reorder	Reordenar
4.2. API de Tags
Método	Endpoint	Descripción
GET	/api/v1/blog/tags	Lista tags (con conteo)
GET	/api/v1/blog/tags/popular	Tags más usados
GET	/api/v1/blog/tags/search?q=	Autocompletado
POST	/api/v1/blog/tags	Crear tag
DELETE	/api/v1/blog/tags/{id}	Eliminar tag
4.3. Categorías Jerárquicas
 categories-tree.json
{
  "categories": [
    {
      "id": 1,
      "name": "Tecnología",
      "slug": "tecnologia",
      "color": "#3B82F6",
      "posts_count": 15,
      "children": [
        {
          "id": 5,
          "name": "Inteligencia Artificial",
          "slug": "ia",
          "parent_id": 1,
          "posts_count": 8
        },
        {
          "id": 6,
          "name": "Desarrollo Web",
          "slug": "desarrollo-web",
          "parent_id": 1,
          "posts_count": 7
        }
      ]
    },
    {
      "id": 2,
      "name": "Negocios",
      "slug": "negocios",
      "color": "#10B981",
      "posts_count": 12
    }
  ]
}

 
5. Templates del Blog
5.1. Template de Listado
 blog-list.html.twig
{# blog-list.html.twig - Template de listado del blog #}
{% set config = blog_config %}
 
<div class="jaraba-blog">
  {# Header del Blog #}
  <header class="jaraba-blog__header">
    <div class="jaraba-container">
      <h1 class="jaraba-blog__title">{{ config.blog_title }}</h1>
      {% if config.blog_description %}
        <p class="jaraba-blog__description">{{ config.blog_description }}</p>
      {% endif %}
      
      {# Filtros #}
      <div class="jaraba-blog__filters">
        <div class="jaraba-blog__categories">
          <a href="{{ config.blog_base_path }}" 
             class="jaraba-blog__filter{% if not current_category %} jaraba-blog__filter--active{% endif %}">
            Todos
          </a>
          {% for category in categories %}
            <a href="{{ config.category_base_path }}/{{ category.slug }}"
               class="jaraba-blog__filter{% if current_category == category.slug %} jaraba-blog__filter--active{% endif %}"
               {% if category.color %}style="--category-color: {{ category.color }}"{% endif %}>
              {{ category.name }}
              <span class="jaraba-blog__filter-count">{{ category.posts_count }}</span>
            </a>
          {% endfor %}
        </div>
        
        {# Búsqueda #}
        <form class="jaraba-blog__search" action="{{ config.blog_base_path }}" method="get">
          <input 
            type="search" 
            name="search" 
            placeholder="Buscar artículos..."
            value="{{ search_query }}"
            class="jaraba-blog__search-input"
          />
          <button type="submit" class="jaraba-blog__search-btn" aria-label="Buscar">
            <i data-lucide="search"></i>
          </button>
        </form>
      </div>
    </div>
  </header>
 
  {# Grid de Posts #}
  <div class="jaraba-container">
    {% if posts|length > 0 %}
      <div class="jaraba-blog__grid jaraba-blog__grid--{{ config.list_layout }} jaraba-blog__grid--cols-{{ config.grid_columns }}">
        {% for post in posts %}
          <article class="jaraba-post-card{% if post.is_featured %} jaraba-post-card--featured{% endif %}{% if post.is_sticky %} jaraba-post-card--sticky{% endif %}">
            
            {# Imagen destacada #}
            {% if config.show_featured_image and post.featured_image_url %}
              <a href="{{ post.url }}" class="jaraba-post-card__image">
                <img 
                  src="{{ post.featured_image_url }}" 
                  alt="{{ post.featured_image_alt ?: post.title }}"
                  loading="lazy"
                />
                {% if post.is_sticky %}
                  <span class="jaraba-post-card__badge jaraba-post-card__badge--sticky">
                    <i data-lucide="pin"></i> Destacado
                  </span>
                {% endif %}
              </a>
            {% endif %}
            
            <div class="jaraba-post-card__content">
              {# Categoría #}
              {% if config.show_category and post.categories|length > 0 %}
                <a 
                  href="{{ config.category_base_path }}/{{ post.categories[0].slug }}"
                  class="jaraba-post-card__category"
                  {% if post.categories[0].color %}style="background-color: {{ post.categories[0].color }}"{% endif %}
                >
                  {{ post.categories[0].name }}
                </a>
              {% endif %}
              
              {# Título #}
              <h2 class="jaraba-post-card__title">
                <a href="{{ post.url }}">{{ post.title }}</a>
              </h2>
              
              {# Excerpt #}
              {% if config.show_excerpt and post.excerpt %}
                <p class="jaraba-post-card__excerpt">
                  {{ post.excerpt|striptags|slice(0, config.excerpt_length) }}{% if post.excerpt|length > config.excerpt_length %}...{% endif %}
                </p>
              {% endif %}
              
              {# Meta #}
              <footer class="jaraba-post-card__meta">
                {% if config.show_author and post.author %}
                  <a href="{{ config.author_base_path }}/{{ post.author.slug }}" class="jaraba-post-card__author">
                    {% if post.author.avatar_url %}
                      <img src="{{ post.author.avatar_url }}" alt="{{ post.author.display_name }}" class="jaraba-post-card__avatar" />
                    {% endif %}
                    <span>{{ post.author.display_name }}</span>
                  </a>
                {% endif %}
                
                {% if config.show_date %}
                  <time class="jaraba-post-card__date" datetime="{{ post.published_at }}">
                    {{ post.published_at|date('d M Y') }}
                  </time>
                {% endif %}
                
                {% if config.show_reading_time %}
                  <span class="jaraba-post-card__reading-time">
                    <i data-lucide="clock"></i>
                    {{ post.reading_time_minutes }} min
                  </span>
                {% endif %}
              </footer>
            </div>
          </article>
        {% endfor %}
      </div>
      
      {# Paginación #}
      {% if pagination.pages > 1 %}
        <nav class="jaraba-blog__pagination" aria-label="Paginación del blog">
          {% if pagination.page > 0 %}
            <a href="{{ current_url }}?page={{ pagination.page - 1 }}" class="jaraba-pagination__prev">
              <i data-lucide="chevron-left"></i> Anterior
            </a>
          {% endif %}
          
          <span class="jaraba-pagination__info">
            Página {{ pagination.page + 1 }} de {{ pagination.pages }}
          </span>
          
          {% if pagination.page < pagination.pages - 1 %}
            <a href="{{ current_url }}?page={{ pagination.page + 1 }}" class="jaraba-pagination__next">
              Siguiente <i data-lucide="chevron-right"></i>
            </a>
          {% endif %}
        </nav>
      {% endif %}
      
    {% else %}
      <div class="jaraba-blog__empty">
        <i data-lucide="file-text"></i>
        <h2>No hay artículos</h2>
        <p>No se encontraron artículos{% if search_query %} para "{{ search_query }}"{% endif %}{% if current_category %} en esta categoría{% endif %}.</p>
      </div>
    {% endif %}
  </div>
</div>

 
5.2. Template de Post Individual
 blog-post.html.twig
{# blog-post.html.twig - Template de post individual #}
{% set config = blog_config %}
 
<article class="jaraba-post jaraba-post--{{ config.post_layout }}" itemscope itemtype="https://schema.org/{{ config.schema_type }}">
  
  {# Hero del post #}
  <header class="jaraba-post__header">
    <div class="jaraba-container jaraba-container--narrow">
      {# Breadcrumbs #}
      <nav class="jaraba-post__breadcrumbs" aria-label="Breadcrumbs">
        <a href="/">Inicio</a>
        <span>/</span>
        <a href="{{ config.blog_base_path }}">{{ config.blog_title }}</a>
        {% if post.primary_category %}
          <span>/</span>
          <a href="{{ config.category_base_path }}/{{ post.categories[0].slug }}">{{ post.categories[0].name }}</a>
        {% endif %}
      </nav>
      
      {# Categoría #}
      {% if post.categories|length > 0 %}
        <a 
          href="{{ config.category_base_path }}/{{ post.categories[0].slug }}"
          class="jaraba-post__category"
          {% if post.categories[0].color %}style="background-color: {{ post.categories[0].color }}"{% endif %}
        >
          {{ post.categories[0].name }}
        </a>
      {% endif %}
      
      {# Título #}
      <h1 class="jaraba-post__title" itemprop="headline">{{ post.title }}</h1>
      
      {# Meta #}
      <div class="jaraba-post__meta">
        {# Autor #}
        <a href="{{ config.author_base_path }}/{{ post.author.slug }}" class="jaraba-post__author" itemprop="author" itemscope itemtype="https://schema.org/Person">
          {% if post.author.avatar_url %}
            <img src="{{ post.author.avatar_url }}" alt="" class="jaraba-post__avatar" />
          {% endif %}
          <span itemprop="name">{{ post.author.display_name }}</span>
        </a>
        
        <span class="jaraba-post__meta-sep">·</span>
        
        {# Fecha #}
        <time class="jaraba-post__date" datetime="{{ post.published_at }}" itemprop="datePublished">
          {{ post.published_at|date('d de F, Y') }}
        </time>
        
        <span class="jaraba-post__meta-sep">·</span>
        
        {# Tiempo de lectura #}
        <span class="jaraba-post__reading-time">
          <i data-lucide="clock"></i>
          {{ post.reading_time_minutes }} min de lectura
        </span>
      </div>
    </div>
    
    {# Imagen destacada #}
    {% if post.featured_image_url %}
      <figure class="jaraba-post__featured-image">
        <img 
          src="{{ post.featured_image_url }}" 
          alt="{{ post.featured_image_alt ?: post.title }}"
          itemprop="image"
        />
        {% if post.featured_image_caption %}
          <figcaption>{{ post.featured_image_caption }}</figcaption>
        {% endif %}
      </figure>
    {% endif %}
  </header>
 
  {# Contenido con sidebar opcional #}
  <div class="jaraba-post__body{% if config.post_layout == 'sidebar' %} jaraba-post__body--with-sidebar jaraba-post__body--sidebar-{{ config.sidebar_position }}{% endif %}">
    <div class="jaraba-container">
      <div class="jaraba-post__layout">
        
        {# Contenido principal #}
        <div class="jaraba-post__content" itemprop="articleBody">
          
          {# Table of Contents #}
          {% if config.show_toc and toc|length > 0 %}
            <nav class="jaraba-post__toc">
              <h2>Contenido</h2>
              <ul>
                {% for item in toc %}
                  <li class="jaraba-post__toc-item jaraba-post__toc-item--h{{ item.level }}">
                    <a href="#{{ item.id }}">{{ item.text }}</a>
                  </li>
                {% endfor %}
              </ul>
            </nav>
          {% endif %}
          
          {# Contenido renderizado #}
          <div class="jaraba-post__prose">
            {{ post.content_rendered|raw }}
          </div>
          
          {# Tags #}
          {% if post.tags|length > 0 %}
            <div class="jaraba-post__tags">
              {% for tag in post.tags %}
                <a href="{{ config.tag_base_path }}/{{ tag.slug }}" class="jaraba-post__tag">
                  #{{ tag.name }}
                </a>
              {% endfor %}
            </div>
          {% endif %}
          
          {# Share buttons #}
          {% if config.show_share_buttons %}
            <div class="jaraba-post__share">
              <span>Compartir:</span>
              <a href="https://twitter.com/intent/tweet?url={{ post.url|url_encode }}&text={{ post.title|url_encode }}" target="_blank" rel="noopener" aria-label="Compartir en Twitter">
                <i data-lucide="twitter"></i>
              </a>
              <a href="https://www.linkedin.com/shareArticle?mini=true&url={{ post.url|url_encode }}&title={{ post.title|url_encode }}" target="_blank" rel="noopener" aria-label="Compartir en LinkedIn">
                <i data-lucide="linkedin"></i>
              </a>
              <a href="https://www.facebook.com/sharer/sharer.php?u={{ post.url|url_encode }}" target="_blank" rel="noopener" aria-label="Compartir en Facebook">
                <i data-lucide="facebook"></i>
              </a>
              <button class="jaraba-post__copy-link" data-url="{{ post.url }}" aria-label="Copiar enlace">
                <i data-lucide="link"></i>
              </button>
            </div>
          {% endif %}
        </div>
        
        {# Sidebar #}
        {% if config.post_layout == 'sidebar' %}
          <aside class="jaraba-post__sidebar">
            {# Autor bio #}
            {% if config.show_author_bio %}
              <div class="jaraba-sidebar__author">
                {% if post.author.avatar_url %}
                  <img src="{{ post.author.avatar_url }}" alt="" class="jaraba-sidebar__avatar" />
                {% endif %}
                <h3>{{ post.author.display_name }}</h3>
                {% if post.author.bio %}
                  <p>{{ post.author.bio }}</p>
                {% endif %}
                {% if post.author.twitter_handle %}
                  <a href="https://twitter.com/{{ post.author.twitter_handle }}" target="_blank" rel="noopener">
                    @{{ post.author.twitter_handle }}
                  </a>
                {% endif %}
              </div>
            {% endif %}
            
            {# Newsletter #}
            {% if sidebar_newsletter %}
              <div class="jaraba-sidebar__newsletter">
                <h3>Suscríbete</h3>
                <p>Recibe los últimos artículos en tu email.</p>
                <form action="/api/v1/newsletter/subscribe" method="post">
                  <input type="email" name="email" placeholder="tu@email.com" required />
                  <button type="submit">Suscribirse</button>
                </form>
              </div>
            {% endif %}
            
            {# Categorías #}
            <div class="jaraba-sidebar__categories">
              <h3>Categorías</h3>
              <ul>
                {% for category in all_categories %}
                  <li>
                    <a href="{{ config.category_base_path }}/{{ category.slug }}">
                      {{ category.name }}
                      <span>({{ category.posts_count }})</span>
                    </a>
                  </li>
                {% endfor %}
              </ul>
            </div>
          </aside>
        {% endif %}
        
      </div>
    </div>
  </div>
 
  {# Posts relacionados #}
  {% if config.show_related_posts and post.related_posts|length > 0 %}
    <section class="jaraba-post__related">
      <div class="jaraba-container">
        <h2>Artículos relacionados</h2>
        <div class="jaraba-post__related-grid">
          {% for related in post.related_posts %}
            <article class="jaraba-post-card jaraba-post-card--small">
              {% if related.featured_image_url %}
                <a href="{{ related.url }}" class="jaraba-post-card__image">
                  <img src="{{ related.featured_image_url }}" alt="{{ related.title }}" loading="lazy" />
                </a>
              {% endif %}
              <div class="jaraba-post-card__content">
                <h3 class="jaraba-post-card__title">
                  <a href="{{ related.url }}">{{ related.title }}</a>
                </h3>
                <time class="jaraba-post-card__date">{{ related.published_at|date('d M Y') }}</time>
              </div>
            </article>
          {% endfor %}
        </div>
      </div>
    </section>
  {% endif %}
</article>

 
6. Editor de Posts
6.1. Modos de Edición
Modo	Descripción	Recomendado Para
markdown	Editor Markdown con preview	Desarrolladores, escritores técnicos
html	Editor HTML con WYSIWYG	Usuarios avanzados
blocks	Usa el Page Builder para contenido	Diseñadores, contenido visual
6.2. Funcionalidades del Editor
•	Toolbar de formato (bold, italic, links, headers)
•	Inserción de imágenes con drag & drop
•	Embeds automáticos (YouTube, Twitter, etc.)
•	Preview en tiempo real
•	Autoguardado cada 30 segundos
•	Historial de versiones
•	Contador de palabras y tiempo de lectura
•	Sugerencias SEO en tiempo real
6.3. Panel de Configuración del Post
•	Visibilidad: Borrador / Publicado / Programado
•	Fecha de publicación (datepicker)
•	URL (slug) editable
•	Categoría principal + categorías secundarias
•	Tags (con autocompletado)
•	Imagen destacada (upload/biblioteca)
•	Excerpt (manual o auto-generado)
•	Autor (si múltiples)
•	Opciones: featured, sticky, noindex
•	SEO: meta title, description, focus keyword
 
7. SEO del Blog
7.1. Schema.org Automático
 schema-blogposting.json
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BlogPosting",
  "headline": "{{ post.title }}",
  "description": "{{ post.meta_description }}",
  "image": "{{ post.featured_image_url }}",
  "author": {
    "@type": "Person",
    "name": "{{ post.author.display_name }}",
    "url": "{{ author_url }}"
  },
  "publisher": {
    "@type": "Organization",
    "name": "{{ site_config.site_name }}",
    "logo": {
      "@type": "ImageObject",
      "url": "{{ site_config.logo_url }}"
    }
  },
  "datePublished": "{{ post.published_at|date('c') }}",
  "dateModified": "{{ post.changed|date('c') }}",
  "mainEntityOfPage": {
    "@type": "WebPage",
    "@id": "{{ post.url }}"
  },
  "wordCount": {{ post.word_count }},
  "articleSection": "{{ post.categories[0].name }}"
}
</script>

7.2. Meta Tags Automáticos
•	Title: {post.meta_title || post.title} | {blog_title}
•	Description: {post.meta_description || post.excerpt}
•	Open Graph completo (og:title, og:description, og:image, og:type)
•	Twitter Cards (summary_large_image)
•	Canonical URL
•	Article tags para Facebook
 
8. RSS Feed
 RssFeedGenerator.php
<?php
 
namespace Drupal\jaraba_blog\Service;
 
/**
 * Generador de RSS Feed.
 */
class RssFeedGenerator {
 
  /**
   * Genera RSS feed del blog.
   */
  public function generate(int $tenantId): string {
    $config = $this->getBlogConfig($tenantId);
    $siteConfig = $this->getSiteConfig($tenantId);
    
    $baseUrl = $this->getBaseUrl($tenantId);
    $feedUrl = $baseUrl . $config['blog_base_path'] . '/feed';
    
    // Obtener últimos posts
    $posts = $this->database->select('blog_post', 'bp')
      ->fields('bp')
      ->condition('tenant_id', $tenantId)
      ->condition('status', 'published')
      ->orderBy('published_at', 'DESC')
      ->range(0, 20)
      ->execute()
      ->fetchAll();
    
    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/">';
    $xml .= '<channel>';
    
    // Info del canal
    $xml .= '<title>' . htmlspecialchars($config['blog_title'] . ' | ' . $siteConfig['site_name']) . '</title>';
    $xml .= '<link>' . $baseUrl . $config['blog_base_path'] . '</link>';
    $xml .= '<description>' . htmlspecialchars($config['blog_description'] ?? '') . '</description>';
    $xml .= '<language>es</language>';
    $xml .= '<lastBuildDate>' . date(DATE_RSS) . '</lastBuildDate>';
    $xml .= '<atom:link href="' . $feedUrl . '" rel="self" type="application/rss+xml"/>';
    
    if ($config['default_og_image']) {
      $imageUrl = $this->getFileUrl($config['default_og_image']);
      $xml .= '<image>';
      $xml .= '<url>' . $imageUrl . '</url>';
      $xml .= '<title>' . htmlspecialchars($config['blog_title']) . '</title>';
      $xml .= '<link>' . $baseUrl . '</link>';
      $xml .= '</image>';
    }
    
    // Items
    foreach ($posts as $post) {
      $postUrl = $baseUrl . $config['blog_base_path'] . '/' . $post->slug;
      
      $xml .= '<item>';
      $xml .= '<title>' . htmlspecialchars($post->title) . '</title>';
      $xml .= '<link>' . $postUrl . '</link>';
      $xml .= '<guid isPermaLink="true">' . $postUrl . '</guid>';
      $xml .= '<pubDate>' . date(DATE_RSS, strtotime($post->published_at)) . '</pubDate>';
      
      // Autor
      $author = $this->getAuthor($post->author_id);
      if ($author) {
        $xml .= '<author>' . htmlspecialchars($author->display_name) . '</author>';
      }
      
      // Categorías
      $categories = $this->getPostCategories($post->id);
      foreach ($categories as $cat) {
        $xml .= '<category>' . htmlspecialchars($cat->name) . '</category>';
      }
      
      // Descripción
      $description = $post->excerpt ?: substr(strip_tags($post->content), 0, 300) . '...';
      $xml .= '<description>' . htmlspecialchars($description) . '</description>';
      
      // Contenido completo (si configurado)
      if ($config['rss_full_content']) {
        $xml .= '<content:encoded><![CDATA[' . $this->renderContent($post->content, $post->content_format) . ']]></content:encoded>';
      }
      
      // Imagen
      if ($post->featured_image_id) {
        $imageUrl = $this->getFileUrl($post->featured_image_id);
        $xml .= '<enclosure url="' . $imageUrl . '" type="image/jpeg"/>';
      }
      
      $xml .= '</item>';
    }
    
    $xml .= '</channel>';
    $xml .= '</rss>';
    
    return $xml;
  }
}

8.1. Configuración RSS
•	URL: /blog/feed o /blog/feed.xml
•	Opción de contenido completo o solo excerpt
•	Últimos 20 posts
•	Imágenes como enclosures
•	Autodiscovery link en <head>
 
9. Límites por Plan
Capacidad	Starter	Professional	Enterprise
Posts	20	200	Ilimitados
Categorías	5	20	Ilimitadas
Autores	1	5	Ilimitados
Imágenes por post	3	10	Ilimitadas
Programación	—	✓	✓
Posts destacados	1	5	Ilimitados
RSS Feed	✓	✓	✓
Búsqueda fulltext	—	✓	✓
Modo bloques (Page Builder)	—	✓	✓
Múltiples autores	—	—	✓

10. Roadmap de Implementación
Sprint	Componente	Horas	Entregables
1	Modelo de datos + migraciones	10-12h	Tablas blog_post, category, tag, author
1	APIs CRUD posts	12-15h	Endpoints completos con filtros
2	APIs categorías y tags	8-10h	CRUD + autocompletado
2	Editor de posts	15-18h	Markdown editor + panel config
3	Templates Twig listado y post	12-15h	4 layouts + sidebar
3	SEO + Schema.org + RSS	8-10h	Meta tags, structured data, feed
4	UI gestión categorías/tags	8-10h	Panels de administración
4	Integración con Site Builder	6-8h	Menús, navegación, breadcrumbs

Total: 60-80 horas (€4,800-€6,400 @ €80/h)

Dependencias
Doc 176 (Site Structure Manager) - Integración con árbol | Doc 177 (Global Navigation) - Menús y breadcrumbs | Doc 162 (Page Builder) - Modo bloques | Doc 164 (SEO/GEO) - Structured data

Fin del documento.
