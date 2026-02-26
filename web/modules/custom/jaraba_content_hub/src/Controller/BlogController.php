<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\Core\Url;
use Drupal\jaraba_content_hub\Service\ArticleService;
use Drupal\jaraba_content_hub\Service\CategoryService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Controlador para las páginas públicas del blog.
 *
 * PROPÓSITO:
 * Renderiza la página principal del blog con artículos, categorías
 * y contenido trending. Utiliza los servicios ArticleService y
 * CategoryService para obtener los datos.
 *
 * ARQUITECTURA:
 * - Usa ControllerBase para acceso a servicios Drupal
 * - Inyección de dependencias via ContainerInjectionInterface
 * - Renderiza template 'content_hub_blog_index'
 *
 * ESPECIFICACIÓN: Doc 128 - Platform_AI_Content_Hub_v2
 */
class BlogController extends ControllerBase implements ContainerInjectionInterface
{

    /**
     * El servicio de artículos.
     *
     * @var \Drupal\jaraba_content_hub\Service\ArticleService
     */
    protected ArticleService $articleService;

    /**
     * El servicio de categorías.
     *
     * @var \Drupal\jaraba_content_hub\Service\CategoryService
     */
    protected CategoryService $categoryService;

    /**
     * Generador de URLs de archivos.
     *
     * @var \Drupal\Core\File\FileUrlGeneratorInterface
     */
    protected FileUrlGeneratorInterface $fileUrlGenerator;

    /**
     * La pila de peticiones HTTP.
     *
     * @var \Symfony\Component\HttpFoundation\RequestStack
     */
    protected RequestStack $requestStack;

    /**
     * Construye un BlogController.
     *
     * @param \Drupal\jaraba_content_hub\Service\ArticleService $articleService
     *   Servicio para gestión de artículos.
     * @param \Drupal\jaraba_content_hub\Service\CategoryService $categoryService
     *   Servicio para gestión de categorías.
     * @param \Drupal\Core\File\FileUrlGeneratorInterface $fileUrlGenerator
     *   Generador de URLs de archivos.
     * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
     *   La pila de peticiones HTTP.
     */
    public function __construct(
        ArticleService $articleService,
        CategoryService $categoryService,
        FileUrlGeneratorInterface $fileUrlGenerator,
        RequestStack $requestStack,
    ) {
        $this->articleService = $articleService;
        $this->categoryService = $categoryService;
        $this->fileUrlGenerator = $fileUrlGenerator;
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_content_hub.article_service'),
            $container->get('jaraba_content_hub.category_service'),
            $container->get('file_url_generator'),
            $container->get('request_stack'),
        );
    }

    /**
     * Renderiza la página principal del blog.
     *
     * Muestra un listado paginado de artículos publicados, sidebar
     * con categorías y widget de artículos trending. Los límites
     * y configuración se obtienen del módulo settings.
     *
     * @return array
     *   Render array con tema 'content_hub_blog_index'.
     */
    public function index(): array
    {
        $config = $this->config('jaraba_content_hub.settings');
        $limit = $config->get('articles_per_page') ?? 12;

        // Paginación: leer página actual desde query string.
        $request = $this->requestStack->getCurrentRequest();
        $current_page = max(1, (int) ($request ? $request->query->get('page', 1) : 1));
        $offset = ($current_page - 1) * $limit;
        $total_articles = $this->articleService->countPublishedArticles();
        $total_pages = (int) ceil($total_articles / $limit);

        // Obtener datos de los servicios.
        $articles = $this->articleService->getPublishedArticles([
            'limit' => $limit,
            'offset' => $offset,
        ]);
        $categories = $this->categoryService->getAllCategories();
        $trending = $this->articleService->getTrendingArticles(5);

        // Preparar array de artículos para el template.
        $article_items = [];
        foreach ($articles as $article) {
            $category = $article->get('category')->entity;
            $image_data = $this->getImageData($article);
            // Use slug-based URL if slug exists, fallback to entity ID.
            $slug = $article->getSlug();
            $article_url = !empty($slug)
                ? Url::fromRoute('entity.content_article.canonical', ['content_article' => $slug])->toString()
                : $article->toUrl()->toString();

            $article_items[] = [
                'id' => $article->id(),
                'title' => $article->getTitle(),
                'slug' => $slug,
                'excerpt' => $article->getExcerpt(),
                'reading_time' => $article->getReadingTime(),
                'publish_date' => $article->get('publish_date')->value,
                'category_name' => $category ? $category->getName() : '',
                'category_color' => $category ? $category->getColor() : '#233D63',
                'category_url' => $category ? $category->toUrl()->toString() : '',
                'featured_image' => $image_data['card'] ?? NULL,
                'featured_image_srcset' => $image_data['srcset'] ?? '',
                'url' => $article_url,
            ];
        }

        // Preparar array de categorías con conteo de artículos.
        // Batch count: una sola consulta GROUP BY en vez de N queries (N+1 fix).
        $article_counts = $this->categoryService->getArticleCountsByCategory();
        $category_items = [];
        foreach ($categories as $category) {
            $category_items[] = [
                'id' => $category->id(),
                'name' => $category->getName(),
                'slug' => $category->getSlug(),
                'color' => $category->getColor(),
                'icon' => $category->getIcon(),
                'count' => $article_counts[(int) $category->id()] ?? 0,
                'url' => $category->toUrl()->toString(),
            ];
        }

        // Preparar array de artículos trending.
        $trending_items = [];
        foreach ($trending as $article) {
            $trendSlug = $article->getSlug();
            $trending_items[] = [
                'id' => $article->id(),
                'title' => $article->getTitle(),
                'url' => !empty($trendSlug)
                    ? Url::fromRoute('entity.content_article.canonical', ['content_article' => $trendSlug])->toString()
                    : $article->toUrl()->toString(),
            ];
        }

        // Construir datos de paginación.
        $pager = NULL;
        if ($total_pages > 1) {
            $pager = [
                'current_page' => $current_page,
                'total_pages' => $total_pages,
                'total_articles' => $total_articles,
                'has_previous' => $current_page > 1,
                'has_next' => $current_page < $total_pages,
                'previous_url' => $current_page > 1
                    ? \Drupal\Core\Url::fromRoute('jaraba_content_hub.blog', [], ['query' => ['page' => $current_page - 1]])->toString()
                    : NULL,
                'next_url' => $current_page < $total_pages
                    ? \Drupal\Core\Url::fromRoute('jaraba_content_hub.blog', [], ['query' => ['page' => $current_page + 1]])->toString()
                    : NULL,
                'pages' => [],
            ];

            // Generar lista de páginas con ventana deslizante.
            $window = 2;
            $start = max(1, $current_page - $window);
            $end = min($total_pages, $current_page + $window);
            for ($i = $start; $i <= $end; $i++) {
                $pager['pages'][] = [
                    'number' => $i,
                    'is_current' => $i === $current_page,
                    'url' => \Drupal\Core\Url::fromRoute('jaraba_content_hub.blog', [], ['query' => ['page' => $i]])->toString(),
                ];
            }
        }

        return [
            '#theme' => 'content_hub_blog_index',
            '#title' => $config->get('blog_title') ?? $this->t('Blog'),
            '#articles' => $article_items,
            '#categories' => $category_items,
            '#trending' => $trending_items,
            '#show_reading_time' => $config->get('show_reading_time') ?? TRUE,
            '#pager' => $pager,
            '#stats' => [
                'total_articles' => $total_articles,
                'total_categories' => count($categories),
            ],
            '#cache' => [
                'tags' => ['content_article_list', 'content_category_list'],
                'contexts' => ['url.query_args:page', 'languages'],
                'max-age' => 300,
            ],
        ];
    }

    /**
     * Obtiene URLs de imagen con Image Styles para srcset responsive.
     *
     * Genera derivados de la imagen destacada en múltiples tamaños:
     * - article_card (600x400): Para cards estándar en el grid.
     * - article_featured (1200x600): Para card featured y OG image.
     * - srcset: Atributo srcset listo para usar en <img>.
     *
     * @param mixed $article
     *   La entidad ContentArticle.
     *
     * @return array
     *   Array con claves 'card', 'featured', 'srcset', o vacío si no hay imagen.
     */
    protected function getImageData($article): array
    {
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
        $card_url = NULL;
        $featured_url = NULL;

        $card_style = ImageStyle::load('article_card');
        if ($card_style) {
            $card_url = $card_style->buildUrl($uri);
        }

        $featured_style = ImageStyle::load('article_featured');
        if ($featured_style) {
            $featured_url = $featured_style->buildUrl($uri);
        }

        // Fallback a URL original si los styles no existen.
        if (!$card_url) {
            $card_url = $this->fileUrlGenerator->generateAbsoluteString($uri);
        }
        if (!$featured_url) {
            $featured_url = $this->fileUrlGenerator->generateAbsoluteString($uri);
        }

        // Construir srcset para responsive images.
        $srcset = $card_url . ' 600w, ' . $featured_url . ' 1200w';

        return [
            'card' => $card_url,
            'featured' => $featured_url,
            'srcset' => $srcset,
        ];
    }

}
