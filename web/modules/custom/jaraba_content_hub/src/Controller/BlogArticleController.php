<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\jaraba_content_hub\Entity\ContentArticleInterface;
use Drupal\jaraba_content_hub\Service\ArticleService;
use Drupal\jaraba_content_hub\Service\SeoService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for individual blog article view with slug support.
 *
 * Handles slug-based canonical URLs for articles. If accessed via numeric ID
 * and the article has a slug, issues a 301 redirect to the slug URL for SEO.
 */
class BlogArticleController extends ControllerBase {

  /**
   * Constructs a BlogArticleController.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    protected RequestStack $requestStack,
    protected ArticleService $articleService,
    protected SeoService $seoService,
  ) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('request_stack'),
      $container->get('jaraba_content_hub.article_service'),
      $container->get('jaraba_content_hub.seo_service'),
    );
  }

  /**
   * Renders a blog article or redirects numeric IDs to slug URLs.
   *
   * @param \Drupal\jaraba_content_hub\Entity\ContentArticleInterface|null $content_article
   *   The article loaded by ParamConverter (by slug or ID).
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Render array or 301 redirect.
   */
  public function view(?ContentArticleInterface $content_article) {
    if (!$content_article) {
      throw new NotFoundHttpException();
    }

    // Access check: only published articles are publicly viewable.
    if (!$content_article->isPublished() && !$this->currentUser()->hasPermission('view unpublished content articles')) {
      throw new AccessDeniedHttpException();
    }

    // 301 redirect: if accessed via numeric ID and article has a slug,
    // redirect to the clean slug URL for SEO canonicalization.
    $request = $this->requestStack->getCurrentRequest();
    $path_parts = explode('/', trim($request->getPathInfo(), '/'));
    $raw_param = end($path_parts);

    if (is_numeric($raw_param) && !empty($content_article->getSlug())) {
      $slug_url = Url::fromRoute('entity.content_article.canonical', [
        'content_article' => $content_article->getSlug(),
      ])->setAbsolute()->toString();
      return new RedirectResponse($slug_url, 301);
    }

    // View tracking: incrementar views_count.
    $this->articleService->trackView((int) $content_article->id());

    // Render the article using the entity view builder.
    $view_builder = $this->entityTypeManager->getViewBuilder('content_article');
    $build = $view_builder->view($content_article, 'full');

    $build['#cache']['tags'][] = 'content_article:' . $content_article->id();
    $build['#cache']['contexts'][] = 'url.path';

    // SEO head tags (OG, Twitter, JSON-LD, canonical).
    // ROUTE-LANGPREFIX-001: URL generada via ruta, no hardcoded.
    $slug = $content_article->getSlug();
    $articleUrl = Url::fromRoute('entity.content_article.canonical', [
      'content_article' => !empty($slug) ? $slug : $content_article->id(),
    ])->setAbsolute()->toString();
    $config = $this->config('jaraba_content_hub.settings');
    $siteName = $config->get('site_name') ?? '';
    $seo = $this->seoService->generateArticleSeo($content_article, $articleUrl, $siteName);

    // Breadcrumb JSON-LD.
    $categoryName = '';
    $categorySlug = '';
    $category = $content_article->get('category')->entity ?? NULL;
    if ($category) {
      $categoryName = $category->getName() ?? '';
      $categorySlug = method_exists($category, 'getSlug') ? ($category->getSlug() ?? '') : '';
    }

    $blogUrl = Url::fromRoute('jaraba_content_hub.blog')->setAbsolute()->toString();
    $breadcrumbItems = [
      ['name' => 'Blog', 'url' => $blogUrl],
    ];
    if ($categoryName && $categorySlug) {
      $categoryPageUrl = Url::fromRoute('jaraba_content_hub.blog.category', ['slug' => $categorySlug])->setAbsolute()->toString();
      $breadcrumbItems[] = ['name' => $categoryName, 'url' => $categoryPageUrl];
    }
    $breadcrumbItems[] = ['name' => $content_article->getTitle(), 'url' => $seo['canonical'] ?? ''];
    $seo['breadcrumb_json_ld'] = $this->seoService->generateBreadcrumbJsonLd($breadcrumbItems);

    $build['#attached']['html_head'] = $this->buildSeoHead($seo);

    // RSS autodiscovery link.
    $build['#attached']['html_head_link'][] = [
      [
        'rel' => 'alternate',
        'type' => 'application/rss+xml',
        'title' => ($config->get('blog_title') ?? 'Blog') . ' RSS',
        'href' => Url::fromRoute('jaraba_content_hub.blog.rss')->toString(),
      ],
    ];

    return $build;
  }

  /**
   * Title callback for the article page.
   *
   * @param \Drupal\jaraba_content_hub\Entity\ContentArticleInterface|null $content_article
   *   The content article entity.
   *
   * @return string
   *   The article title.
   */
  public function title(?ContentArticleInterface $content_article): string {
    if (!$content_article) {
      return '';
    }
    return $content_article->getTitle();
  }

  /**
   * Construye los meta tags HTML head desde datos SEO.
   *
   * @param array $seo
   *   Array SEO del SeoService.
   *
   * @return array
   *   Array de elementos html_head para #attached.
   */
  protected function buildSeoHead(array $seo): array {
    $head = [];

    if (!empty($seo['meta_tags'])) {
      foreach ($seo['meta_tags'] as $name => $content) {
        if ($content) {
          $head[] = [
            ['#tag' => 'meta', '#attributes' => ['name' => $name, 'content' => $content]],
            'blog_meta_' . $name,
          ];
        }
      }
    }

    if (!empty($seo['og_tags'])) {
      foreach ($seo['og_tags'] as $property => $content) {
        if ($content) {
          $head[] = [
            ['#tag' => 'meta', '#attributes' => ['property' => $property, 'content' => $content]],
            'blog_og_' . str_replace(':', '_', $property),
          ];
        }
      }
    }

    if (!empty($seo['twitter_tags'])) {
      foreach ($seo['twitter_tags'] as $name => $content) {
        if ($content) {
          $head[] = [
            ['#tag' => 'meta', '#attributes' => ['name' => $name, 'content' => $content]],
            'blog_twitter_' . str_replace(':', '_', $name),
          ];
        }
      }
    }

    if (!empty($seo['canonical'])) {
      $head[] = [
        ['#tag' => 'link', '#attributes' => ['rel' => 'canonical', 'href' => $seo['canonical']]],
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

}
