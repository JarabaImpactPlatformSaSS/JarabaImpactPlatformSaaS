<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Url;
use Drupal\image\Entity\ImageStyle;
use Drupal\jaraba_content_hub\Service\ArticleService;
use Drupal\jaraba_content_hub\Service\CategoryService;
use Drupal\jaraba_content_hub\Service\RssService;
use Drupal\jaraba_content_hub\Service\SeoService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controlador para las paginas publicas del blog.
 *
 * Rutas publicas:
 * - /blog (listado principal con paginacion)
 * - /blog/categoria/{slug} (listado filtrado por categoria)
 * - /blog/autor/{slug} (pagina de autor con sus articulos)
 * - /blog/feed.xml (RSS 2.0 feed)
 *
 * Consolida la funcionalidad de BlogFrontendController (jaraba_blog)
 * con el sistema canonico Content Hub.
 *
 * ESPECIFICACION: Doc 128 - Platform_AI_Content_Hub_v2
 */
class BlogController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Construye un BlogController.
   *
   * @param \Drupal\jaraba_content_hub\Service\ArticleService $articleService
   *   Servicio para gestion de articulos.
   * @param \Drupal\jaraba_content_hub\Service\CategoryService $categoryService
   *   Servicio para gestion de categorias.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $fileUrlGenerator
   *   Generador de URLs de archivos.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   La pila de peticiones HTTP.
   * @param \Drupal\jaraba_content_hub\Service\SeoService $seoService
   *   Servicio SEO para meta tags y JSON-LD.
   * @param \Drupal\jaraba_content_hub\Service\RssService $rssService
   *   Servicio para generacion de RSS feed.
   */
  public function __construct(
    protected ArticleService $articleService,
    protected CategoryService $categoryService,
    protected FileUrlGeneratorInterface $fileUrlGenerator,
    protected RequestStack $requestStack,
    protected SeoService $seoService,
    protected RssService $rssService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_content_hub.article_service'),
      $container->get('jaraba_content_hub.category_service'),
      $container->get('file_url_generator'),
      $container->get('request_stack'),
      $container->get('jaraba_content_hub.seo_service'),
      $container->get('jaraba_content_hub.rss_service'),
    );
  }

  // ========================================================================
  // Blog listing (index)
  // ========================================================================

  /**
   * Renderiza la pagina principal del blog.
   *
   * Muestra un listado paginado de articulos publicados, sidebar
   * con categorias y widget de trending. Incluye SEO head tags
   * completos (OG, Twitter, canonical).
   *
   * @return array
   *   Render array con tema 'content_hub_blog_index'.
   */
  public function index(): array {
    $config = $this->config('jaraba_content_hub.settings');
    $limit = (int) ($config->get('articles_per_page') ?? 12);

    // Paginacion: leer pagina actual desde query string.
    $request = $this->requestStack->getCurrentRequest();
    $currentPage = max(1, (int) ($request ? $request->query->get('page', 1) : 1));
    $offset = ($currentPage - 1) * $limit;
    $totalArticles = $this->articleService->countPublishedArticles();
    $totalPages = (int) ceil($totalArticles / max(1, $limit));

    // Obtener datos de los servicios.
    $articles = $this->articleService->getPublishedArticles([
      'limit' => $limit,
      'offset' => $offset,
    ]);
    $categories = $this->categoryService->getAllCategories();
    $trending = $this->articleService->getTrendingArticles(5);

    // Preparar items para template.
    $articleItems = $this->buildArticleItems($articles);
    $categoryItems = $this->buildCategoryItems($categories);
    $trendingItems = $this->buildTrendingItems($trending);
    $pager = $this->buildPager($currentPage, $totalPages, $totalArticles, 'jaraba_content_hub.blog');

    // SEO head tags (ROUTE-LANGPREFIX-001: URL via ruta).
    $canonicalUrl = Url::fromRoute('jaraba_content_hub.blog')->setAbsolute()->toString();
    $siteName = $config->get('site_name') ?? '';
    $seo = $this->seoService->generateListingSeo($canonicalUrl, $siteName);

    return [
      '#theme' => 'content_hub_blog_index',
      '#title' => $config->get('blog_title') ?? $this->t('Blog'),
      '#articles' => $articleItems,
      '#categories' => $categoryItems,
      '#trending' => $trendingItems,
      '#current_category' => NULL,
      '#current_author' => NULL,
      '#show_reading_time' => $config->get('show_reading_time') ?? TRUE,
      '#pager' => $pager,
      '#stats' => [
        'total_articles' => $totalArticles,
        'total_categories' => count($categories),
      ],
      '#attached' => [
        'html_head' => $this->buildSeoHead($seo),
        'html_head_link' => [
          [
            [
              'rel' => 'alternate',
              'type' => 'application/rss+xml',
              'title' => ($config->get('blog_title') ?? 'Blog') . ' RSS',
              'href' => Url::fromRoute('jaraba_content_hub.blog.rss')->toString(),
            ],
          ],
        ],
      ],
      '#cache' => [
        'tags' => ['content_article_list', 'content_category_list'],
        'contexts' => ['url.query_args:page', 'languages'],
        'max-age' => 300,
      ],
    ];
  }

  // ========================================================================
  // Category listing
  // ========================================================================

  /**
   * Renderiza la pagina de listado por categoria.
   *
   * Muestra articulos publicados filtrados por la categoria indicada
   * via slug. Incluye SEO head tags con contexto de categoria.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La peticion HTTP actual.
   * @param string $slug
   *   Slug URL-friendly de la categoria.
   *
   * @return array
   *   Render array con tema 'content_hub_blog_index'.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Si la categoria no existe o no esta activa.
   */
  public function category(Request $request, string $slug): array {
    $category = $this->categoryService->getBySlug($slug);

    if (!$category) {
      throw new NotFoundHttpException();
    }

    // Si la categoria tiene is_active y esta desactivada, 404.
    if ($category->hasField('is_active') && !$category->get('is_active')->value) {
      throw new NotFoundHttpException();
    }

    $config = $this->config('jaraba_content_hub.settings');
    $limit = (int) ($config->get('articles_per_page') ?? 12);
    $currentPage = max(1, (int) $request->query->get('page', 1));
    $offset = ($currentPage - 1) * $limit;

    $categoryId = (int) $category->id();
    $articles = $this->articleService->getPublishedArticles([
      'category' => $categoryId,
      'limit' => $limit,
      'offset' => $offset,
    ]);
    $totalArticles = $this->articleService->countPublishedArticles(['category' => $categoryId]);
    $totalPages = (int) ceil($totalArticles / max(1, $limit));

    $categories = $this->categoryService->getAllCategories();
    $trending = $this->articleService->getTrendingArticles(5);

    $articleItems = $this->buildArticleItems($articles);
    $categoryItems = $this->buildCategoryItems($categories);
    $trendingItems = $this->buildTrendingItems($trending);
    $pager = $this->buildPager($currentPage, $totalPages, $totalArticles, 'jaraba_content_hub.blog.category', ['slug' => $slug]);

    // SEO con contexto de categoria (ROUTE-LANGPREFIX-001).
    $categoryUrl = Url::fromRoute('jaraba_content_hub.blog.category', ['slug' => $slug])->setAbsolute()->toString();
    $blogUrl = Url::fromRoute('jaraba_content_hub.blog')->setAbsolute()->toString();
    $siteName = $config->get('site_name') ?? '';
    $seo = $this->seoService->generateListingSeo($categoryUrl, $siteName, $category->getName());

    // Breadcrumb JSON-LD.
    $breadcrumbJsonLd = $this->seoService->generateBreadcrumbJsonLd([
      ['name' => 'Blog', 'url' => $blogUrl],
      ['name' => $category->getName(), 'url' => $categoryUrl],
    ]);
    $seo['breadcrumb_json_ld'] = $breadcrumbJsonLd;

    // Imagen de la categoria (si tiene featured_image).
    $categoryImage = NULL;
    if ($category->hasField('featured_image') && !$category->get('featured_image')->isEmpty()) {
      $file = $category->get('featured_image')->entity;
      if ($file) {
        $categoryImage = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
      }
    }

    return [
      '#theme' => 'content_hub_blog_index',
      '#title' => $category->getName() . ' - Blog',
      '#articles' => $articleItems,
      '#categories' => $categoryItems,
      '#trending' => $trendingItems,
      '#current_category' => [
        'id' => $category->id(),
        'name' => $category->getName(),
        'slug' => $slug,
        'description' => $category->get('description')->value ?? '',
        'color' => $category->getColor(),
        'icon' => $category->getIcon(),
        'featured_image' => $categoryImage,
        'meta_title' => $category->hasField('meta_title') ? ($category->get('meta_title')->value ?? '') : '',
        'meta_description' => $category->hasField('meta_description') ? ($category->get('meta_description')->value ?? '') : '',
      ],
      '#current_author' => NULL,
      '#show_reading_time' => $config->get('show_reading_time') ?? TRUE,
      '#pager' => $pager,
      '#stats' => [
        'total_articles' => $totalArticles,
        'total_categories' => count($categories),
      ],
      '#attached' => [
        'html_head' => $this->buildSeoHead($seo),
      ],
      '#cache' => [
        'tags' => ['content_article_list', 'content_category:' . $category->id()],
        'contexts' => ['url.query_args:page', 'languages'],
        'max-age' => 300,
      ],
    ];
  }

  /**
   * Title callback para la ruta de categoria.
   *
   * @param string $slug
   *   Slug de la categoria.
   *
   * @return string
   *   Titulo de la pagina.
   */
  public function categoryTitle(string $slug): string {
    $category = $this->categoryService->getBySlug($slug);
    return $category ? $category->getName() . ' - Blog' : 'Blog';
  }

  // ========================================================================
  // Author page
  // ========================================================================

  /**
   * Renderiza la pagina de un autor con sus articulos.
   *
   * Muestra el perfil del autor (bio, avatar, redes sociales)
   * y un listado paginado de sus articulos publicados.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La peticion HTTP actual.
   * @param string $slug
   *   Slug URL-friendly del autor.
   *
   * @return array
   *   Render array con tema 'content_hub_author_page'.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Si el autor no existe o no esta activo.
   */
  public function author(Request $request, string $slug): array {
    // Buscar autor por slug.
    $authors = $this->entityTypeManager()
      ->getStorage('content_author')
      ->loadByProperties(['slug' => $slug]);
    $author = $authors ? reset($authors) : NULL;

    if (!$author) {
      throw new NotFoundHttpException();
    }

    if ($author->hasField('is_active') && !$author->get('is_active')->value) {
      throw new NotFoundHttpException();
    }

    $config = $this->config('jaraba_content_hub.settings');
    $limit = (int) ($config->get('articles_per_page') ?? 12);
    $currentPage = max(1, (int) $request->query->get('page', 1));
    $offset = ($currentPage - 1) * $limit;

    $authorId = (int) $author->id();
    $articles = $this->articleService->getArticlesByAuthor($authorId, $limit, $offset);
    $totalArticles = $this->articleService->countArticlesByAuthor($authorId);
    $totalPages = (int) ceil($totalArticles / max(1, $limit));

    $articleItems = $this->buildArticleItems($articles);
    $pager = $this->buildPager($currentPage, $totalPages, $totalArticles, 'jaraba_content_hub.blog.author', ['slug' => $slug]);

    // SEO con contexto de autor (ROUTE-LANGPREFIX-001).
    $authorUrl = Url::fromRoute('jaraba_content_hub.blog.author', ['slug' => $slug])->setAbsolute()->toString();
    $blogUrl = Url::fromRoute('jaraba_content_hub.blog')->setAbsolute()->toString();
    $siteName = $config->get('site_name') ?? '';
    $authorName = $author->getDisplayName() ?? '';
    $seo = $this->seoService->generateListingSeo($authorUrl, $siteName, NULL, $authorName);

    // Breadcrumb JSON-LD.
    $breadcrumbJsonLd = $this->seoService->generateBreadcrumbJsonLd([
      ['name' => 'Blog', 'url' => $blogUrl],
      ['name' => $authorName, 'url' => $authorUrl],
    ]);
    $seo['breadcrumb_json_ld'] = $breadcrumbJsonLd;

    // Avatar del autor.
    $avatarUrl = '';
    if ($author->hasField('avatar') && !$author->get('avatar')->isEmpty()) {
      $avatarFile = $author->get('avatar')->entity;
      if ($avatarFile) {
        $avatarUrl = $this->fileUrlGenerator->generateAbsoluteString($avatarFile->getFileUri());
      }
    }

    // Redes sociales.
    $socialLinks = [];
    foreach (['twitter', 'linkedin', 'github', 'website'] as $network) {
      $fieldName = 'social_' . $network;
      if ($author->hasField($fieldName)) {
        $value = $author->get($fieldName)->value ?? '';
        if (!empty($value)) {
          $socialLinks[$network] = $value;
        }
      }
    }

    return [
      '#theme' => 'content_hub_author_page',
      '#author' => [
        'id' => $author->id(),
        'display_name' => $authorName,
        'slug' => $slug,
        'bio' => $author->hasField('bio') ? ($author->get('bio')->value ?? '') : '',
        'avatar' => $avatarUrl,
        'social_links' => $socialLinks,
        'posts_count' => $totalArticles,
      ],
      '#articles' => $articleItems,
      '#show_reading_time' => $config->get('show_reading_time') ?? TRUE,
      '#pager' => $pager,
      '#attached' => [
        'html_head' => $this->buildSeoHead($seo),
      ],
      '#cache' => [
        'tags' => ['content_article_list', 'content_author:' . $author->id()],
        'contexts' => ['url.query_args:page', 'languages'],
        'max-age' => 300,
      ],
    ];
  }

  /**
   * Title callback para la ruta de autor.
   *
   * @param string $slug
   *   Slug del autor.
   *
   * @return string
   *   Titulo de la pagina.
   */
  public function authorTitle(string $slug): string {
    $authors = $this->entityTypeManager()
      ->getStorage('content_author')
      ->loadByProperties(['slug' => $slug]);
    $author = $authors ? reset($authors) : NULL;
    return $author ? ($author->getDisplayName() ?? '') . ' - Blog' : 'Blog';
  }

  // ========================================================================
  // RSS Feed
  // ========================================================================

  /**
   * Genera y devuelve el feed RSS 2.0.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Respuesta HTTP con XML RSS y content-type application/rss+xml.
   */
  public function rssFeed(): Response {
    $request = $this->requestStack->getCurrentRequest();
    $baseUrl = $request ? $request->getSchemeAndHttpHost() : '';
    $config = $this->config('jaraba_content_hub.settings');
    $siteName = $config->get('site_name') ?? $config->get('blog_title') ?? 'Blog';
    $language = $this->languageManager()->getCurrentLanguage()->getId();

    $xml = $this->rssService->generateFeed($baseUrl, $siteName, $language, 20);

    return new Response($xml, 200, [
      'Content-Type' => 'application/rss+xml; charset=UTF-8',
      'Cache-Control' => 'public, max-age=3600',
    ]);
  }

  // ========================================================================
  // Helpers: Build template data
  // ========================================================================

  /**
   * Construye array de datos de articulos para templates.
   *
   * @param array $articles
   *   Array de entidades ContentArticle.
   *
   * @return array
   *   Array de items listos para el template.
   */
  protected function buildArticleItems(array $articles): array {
    $items = [];
    foreach ($articles as $article) {
      $category = $article->get('category')->entity;
      $imageData = $this->getImageData($article);
      $slug = $article->getSlug();
      $articleUrl = !empty($slug)
        ? Url::fromRoute('entity.content_article.canonical', ['content_article' => $slug])->toString()
        : $article->toUrl()->toString();

      // Autor: ContentAuthor > Owner.
      $authorName = '';
      $authorSlug = '';
      $contentAuthor = $article->get('content_author')->entity ?? NULL;
      if ($contentAuthor) {
        $authorName = $contentAuthor->getDisplayName() ?? '';
        $authorSlug = $contentAuthor->getSlug() ?? '';
      }
      elseif (method_exists($article, 'getOwner') && $article->getOwner()) {
        $authorName = $article->getOwner()->getDisplayName() ?? '';
      }

      $items[] = [
        'id' => $article->id(),
        'title' => $article->getTitle(),
        'slug' => $slug,
        'excerpt' => $article->getExcerpt(),
        'reading_time' => $article->getReadingTime(),
        'publish_date' => $article->get('publish_date')->value,
        'category_name' => $category ? $category->getName() : '',
        'category_color' => $category ? $category->getColor() : '#233D63',
        'category_slug' => $category && method_exists($category, 'getSlug') ? $category->getSlug() : '',
        'featured_image' => $imageData['card'] ?? NULL,
        'featured_image_srcset' => $imageData['srcset'] ?? '',
        'featured_image_alt' => $article->hasField('featured_image_alt') ? ($article->get('featured_image_alt')->value ?? '') : '',
        'url' => $articleUrl,
        'author_name' => $authorName,
        'author_slug' => $authorSlug,
        'is_featured' => $article->hasField('is_featured') ? (bool) $article->get('is_featured')->value : FALSE,
        'views_count' => $article->hasField('views_count') ? (int) ($article->get('views_count')->value ?? 0) : 0,
      ];
    }
    return $items;
  }

  /**
   * Construye array de datos de categorias para templates.
   *
   * @param array $categories
   *   Array de entidades ContentCategory.
   *
   * @return array
   *   Array de items de categoria.
   */
  protected function buildCategoryItems(array $categories): array {
    // Batch count: una sola consulta GROUP BY (N+1 fix).
    $articleCounts = $this->categoryService->getArticleCountsByCategory();
    $items = [];
    foreach ($categories as $category) {
      $slug = $category->getSlug();
      // Omitir categorias sin slug (no pueden tener URL amigable).
      if (empty($slug)) {
        continue;
      }
      $items[] = [
        'id' => $category->id(),
        'name' => $category->getName(),
        'slug' => $slug,
        'color' => $category->getColor(),
        'icon' => $category->getIcon(),
        'count' => $articleCounts[(int) $category->id()] ?? 0,
        'url' => Url::fromRoute('jaraba_content_hub.blog.category', ['slug' => $slug])->toString(),
      ];
    }
    return $items;
  }

  /**
   * Construye array de datos de articulos trending para templates.
   *
   * @param array $trending
   *   Array de entidades ContentArticle trending.
   *
   * @return array
   *   Array de items trending.
   */
  protected function buildTrendingItems(array $trending): array {
    $items = [];
    foreach ($trending as $article) {
      $slug = $article->getSlug();
      $items[] = [
        'id' => $article->id(),
        'title' => $article->getTitle(),
        'url' => !empty($slug)
          ? Url::fromRoute('entity.content_article.canonical', ['content_article' => $slug])->toString()
          : $article->toUrl()->toString(),
      ];
    }
    return $items;
  }

  /**
   * Construye datos de paginacion para templates.
   *
   * Genera una ventana deslizante de +-2 paginas alrededor de la actual.
   * Usa Url::fromRoute() para generar URLs (ROUTE-LANGPREFIX-001).
   *
   * @param int $currentPage
   *   Pagina actual (1-based).
   * @param int $totalPages
   *   Total de paginas.
   * @param int $totalArticles
   *   Total de articulos.
   * @param string $routeName
   *   Nombre de la ruta para generar URLs.
   * @param array $routeParams
   *   Parametros de la ruta.
   *
   * @return array|null
   *   Datos de paginacion o NULL si solo hay una pagina.
   */
  protected function buildPager(int $currentPage, int $totalPages, int $totalArticles, string $routeName, array $routeParams = []): ?array {
    if ($totalPages <= 1) {
      return NULL;
    }

    $pager = [
      'current_page' => $currentPage,
      'total_pages' => $totalPages,
      'total_articles' => $totalArticles,
      'has_previous' => $currentPage > 1,
      'has_next' => $currentPage < $totalPages,
      'previous_url' => $currentPage > 1
        ? Url::fromRoute($routeName, $routeParams, ['query' => ['page' => $currentPage - 1]])->toString()
        : NULL,
      'next_url' => $currentPage < $totalPages
        ? Url::fromRoute($routeName, $routeParams, ['query' => ['page' => $currentPage + 1]])->toString()
        : NULL,
      'pages' => [],
    ];

    // Ventana deslizante de +-2.
    $window = 2;
    $start = max(1, $currentPage - $window);
    $end = min($totalPages, $currentPage + $window);
    for ($i = $start; $i <= $end; $i++) {
      $pager['pages'][] = [
        'number' => $i,
        'is_current' => $i === $currentPage,
        'url' => Url::fromRoute($routeName, $routeParams, ['query' => ['page' => $i]])->toString(),
      ];
    }

    return $pager;
  }

  // ========================================================================
  // Helpers: SEO head tags
  // ========================================================================

  /**
   * Construye los meta tags HTML head desde datos SEO.
   *
   * Convierte el array estructurado del SeoService en elementos
   * #attached/html_head compatibles con el render system de Drupal.
   *
   * @param array $seo
   *   Array SEO con claves: meta_tags, og_tags, twitter_tags,
   *   json_ld, canonical, breadcrumb_json_ld.
   *
   * @return array
   *   Array de elementos html_head para #attached.
   */
  protected function buildSeoHead(array $seo): array {
    $head = [];

    // Meta tags basicos (name=...).
    if (!empty($seo['meta_tags'])) {
      foreach ($seo['meta_tags'] as $name => $content) {
        if ($content) {
          $head[] = [
            [
              '#tag' => 'meta',
              '#attributes' => ['name' => $name, 'content' => $content],
            ],
            'blog_meta_' . $name,
          ];
        }
      }
    }

    // Open Graph tags (property=...).
    if (!empty($seo['og_tags'])) {
      foreach ($seo['og_tags'] as $property => $content) {
        if ($content) {
          $head[] = [
            [
              '#tag' => 'meta',
              '#attributes' => ['property' => $property, 'content' => $content],
            ],
            'blog_og_' . str_replace(':', '_', $property),
          ];
        }
      }
    }

    // Twitter Card tags (name=...).
    if (!empty($seo['twitter_tags'])) {
      foreach ($seo['twitter_tags'] as $name => $content) {
        if ($content) {
          $head[] = [
            [
              '#tag' => 'meta',
              '#attributes' => ['name' => $name, 'content' => $content],
            ],
            'blog_twitter_' . str_replace(':', '_', $name),
          ];
        }
      }
    }

    // Canonical link.
    if (!empty($seo['canonical'])) {
      $head[] = [
        [
          '#tag' => 'link',
          '#attributes' => ['rel' => 'canonical', 'href' => $seo['canonical']],
        ],
        'blog_canonical',
      ];
    }

    // JSON-LD (article/listing schema).
    if (!empty($seo['json_ld'])) {
      $head[] = [
        [
          '#tag' => 'script',
          '#attributes' => ['type' => 'application/ld+json'],
          '#value' => json_encode($seo['json_ld'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ],
        'blog_json_ld',
      ];
    }

    // BreadcrumbList JSON-LD.
    if (!empty($seo['breadcrumb_json_ld'])) {
      $head[] = [
        [
          '#tag' => 'script',
          '#attributes' => ['type' => 'application/ld+json'],
          '#value' => json_encode($seo['breadcrumb_json_ld'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ],
        'blog_breadcrumb_json_ld',
      ];
    }

    return $head;
  }

  // ========================================================================
  // Helpers: Image processing
  // ========================================================================

  /**
   * Obtiene URLs de imagen con Image Styles para srcset responsive.
   *
   * Genera derivados de la imagen destacada en multiples tamanos:
   * - article_card (600x400): Para cards estandar en el grid.
   * - article_featured (1200x600): Para card featured y OG image.
   * - srcset: Atributo srcset listo para usar en <img>.
   *
   * @param mixed $article
   *   La entidad ContentArticle.
   *
   * @return array
   *   Array con claves 'card', 'featured', 'srcset', o vacio si no hay imagen.
   */
  protected function getImageData($article): array {
    if (!$article->hasField('featured_image')) {
      return [];
    }

    $imageField = $article->get('featured_image');
    if ($imageField->isEmpty()) {
      return [];
    }

    $file = $imageField->entity;
    if (!$file) {
      return [];
    }

    $uri = $file->getFileUri();

    $cardStyle = ImageStyle::load('article_card');
    $cardUrl = $cardStyle
      ? $cardStyle->buildUrl($uri)
      : $this->fileUrlGenerator->generateAbsoluteString($uri);

    $featuredStyle = ImageStyle::load('article_featured');
    $featuredUrl = $featuredStyle
      ? $featuredStyle->buildUrl($uri)
      : $this->fileUrlGenerator->generateAbsoluteString($uri);

    return [
      'card' => $cardUrl,
      'featured' => $featuredUrl,
      'srcset' => $cardUrl . ' 600w, ' . $featuredUrl . ' 1200w',
    ];
  }

}
