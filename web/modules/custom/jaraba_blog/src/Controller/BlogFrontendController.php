<?php

declare(strict_types=1);

namespace Drupal\jaraba_blog\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_blog\Service\BlogService;
use Drupal\jaraba_blog\Service\BlogSeoService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller para las paginas frontend del blog.
 *
 * Rutas publicas per-tenant:
 * - /blog (listado)
 * - /blog/{slug} (detalle)
 * - /blog/categoria/{slug} (listado por categoria)
 * - /blog/autor/{slug} (listado por autor)
 */
class BlogFrontendController extends ControllerBase {

  /**
   * Constructor.
   */
  public function __construct(
    protected BlogService $blogService,
    protected BlogSeoService $seoService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_blog.blog'),
      $container->get('jaraba_blog.seo'),
    );
  }

  /**
   * Pagina de listado del blog.
   */
  public function listing(Request $request): array {
    $page = max(0, (int) $request->query->get('page', 0));
    $search = $request->query->get('q', '');

    $filters = ['status' => 'published'];
    if ($search) {
      $filters['search'] = $search;
    }

    $result = $this->blogService->listPosts($filters, $page);
    $categories = $this->blogService->listCategories();
    $popularPosts = $this->blogService->getPopularPosts(5);
    $stats = $this->blogService->getStats();
    $seo = $this->seoService->generateListingSeo();

    return [
      '#theme' => 'blog_listing',
      '#posts' => $result['posts'],
      '#categories' => $categories,
      '#current_category' => NULL,
      '#current_author' => NULL,
      '#pagination' => [
        'current' => $page,
        'total' => $result['pages'],
        'base_url' => '/blog',
      ],
      '#popular_posts' => $popularPosts,
      '#stats' => $stats,
      '#attached' => [
        'library' => ['jaraba_blog/blog'],
        'html_head' => $this->buildSeoHead($seo),
      ],
      '#cache' => [
        'tags' => ['blog_post_list'],
        'contexts' => ['url.query_args'],
        'max-age' => 300,
      ],
    ];
  }

  /**
   * Pagina de detalle de un post.
   */
  public function detail(string $slug): array {
    $post = $this->blogService->getPostBySlug($slug);

    if (!$post || !$post->isPublished()) {
      throw new NotFoundHttpException();
    }

    // Incrementar visitas.
    $this->blogService->trackView((int) $post->id());

    $author = $post->get('author_id')->entity;
    $category = $post->get('category_id')->entity;
    $relatedPosts = $this->blogService->getRelatedPosts((int) $post->id());
    $adjacent = $this->blogService->getAdjacentPosts((int) $post->id());
    $seo = $this->seoService->generatePostSeo($post);

    return [
      '#theme' => 'blog_detail',
      '#post' => $post,
      '#author' => $author,
      '#category' => $category,
      '#related_posts' => $relatedPosts,
      '#prev_post' => $adjacent['prev'],
      '#next_post' => $adjacent['next'],
      '#seo' => $seo,
      '#attached' => [
        'library' => ['jaraba_blog/blog'],
        'html_head' => $this->buildSeoHead($seo),
      ],
      '#cache' => [
        'tags' => ['blog_post:' . $post->id()],
        'max-age' => 600,
      ],
    ];
  }

  /**
   * Pagina de listado filtrado por categoria.
   */
  public function category(Request $request, string $slug): array {
    $category = $this->blogService->getCategoryBySlug($slug);

    if (!$category || !$category->isActive()) {
      throw new NotFoundHttpException();
    }

    $page = max(0, (int) $request->query->get('page', 0));

    $result = $this->blogService->listPosts(
      ['status' => 'published', 'category_id' => (int) $category->id()],
      $page
    );

    $categories = $this->blogService->listCategories();
    $popularPosts = $this->blogService->getPopularPosts(5);
    $seo = $this->seoService->generateListingSeo($category->getName());

    return [
      '#theme' => 'blog_listing',
      '#posts' => $result['posts'],
      '#categories' => $categories,
      '#current_category' => $category,
      '#current_author' => NULL,
      '#pagination' => [
        'current' => $page,
        'total' => $result['pages'],
        'base_url' => '/blog/categoria/' . $slug,
      ],
      '#popular_posts' => $popularPosts,
      '#stats' => [],
      '#attached' => [
        'library' => ['jaraba_blog/blog'],
        'html_head' => $this->buildSeoHead($seo),
      ],
      '#cache' => [
        'tags' => ['blog_post_list', 'blog_category:' . $category->id()],
        'contexts' => ['url.query_args'],
        'max-age' => 300,
      ],
    ];
  }

  /**
   * Pagina de listado filtrado por autor.
   */
  public function author(Request $request, string $slug): array {
    $author = $this->blogService->getAuthorBySlug($slug);

    if (!$author || !$author->isActive()) {
      throw new NotFoundHttpException();
    }

    $page = max(0, (int) $request->query->get('page', 0));

    $result = $this->blogService->listPosts(
      ['status' => 'published', 'author_id' => (int) $author->id()],
      $page
    );

    $categories = $this->blogService->listCategories();
    $seo = $this->seoService->generateListingSeo(NULL, $author->getDisplayName());

    return [
      '#theme' => 'blog_listing',
      '#posts' => $result['posts'],
      '#categories' => $categories,
      '#current_category' => NULL,
      '#current_author' => $author,
      '#pagination' => [
        'current' => $page,
        'total' => $result['pages'],
        'base_url' => '/blog/autor/' . $slug,
      ],
      '#popular_posts' => [],
      '#stats' => [],
      '#attached' => [
        'library' => ['jaraba_blog/blog'],
        'html_head' => $this->buildSeoHead($seo),
      ],
      '#cache' => [
        'tags' => ['blog_post_list'],
        'contexts' => ['url.query_args'],
        'max-age' => 300,
      ],
    ];
  }

  /**
   * Construye los meta tags HTML head desde datos SEO.
   */
  protected function buildSeoHead(array $seo): array {
    $head = [];

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

    if (!empty($seo['canonical'])) {
      $head[] = [
        [
          '#tag' => 'link',
          '#attributes' => ['rel' => 'canonical', 'href' => $seo['canonical']],
        ],
        'blog_canonical',
      ];
    }

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

    return $head;
  }

}
