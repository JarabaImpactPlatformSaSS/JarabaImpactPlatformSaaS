<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\jaraba_content_hub\Service\ArticleService;
use Drupal\jaraba_content_hub\Service\CategoryService;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
     * Construye un BlogController.
     *
     * @param \Drupal\jaraba_content_hub\Service\ArticleService $articleService
     *   Servicio para gestión de artículos.
     * @param \Drupal\jaraba_content_hub\Service\CategoryService $categoryService
     *   Servicio para gestión de categorías.
     */
    public function __construct(
        ArticleService $articleService,
        CategoryService $categoryService,
    ) {
        $this->articleService = $articleService;
        $this->categoryService = $categoryService;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_content_hub.article_service'),
            $container->get('jaraba_content_hub.category_service'),
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

        // Obtener datos de los servicios.
        $articles = $this->articleService->getPublishedArticles(['limit' => $limit]);
        $categories = $this->categoryService->getAllCategories();
        $trending = $this->articleService->getTrendingArticles(5);

        // Preparar array de artículos para el template.
        $article_items = [];
        foreach ($articles as $article) {
            $category = $article->get('category')->entity;
            $article_items[] = [
                'id' => $article->id(),
                'title' => $article->getTitle(),
                'slug' => $article->getSlug(),
                'excerpt' => $article->getExcerpt(),
                'reading_time' => $article->getReadingTime(),
                'publish_date' => $article->get('publish_date')->value,
                'category_name' => $category ? $category->getName() : '',
                'category_color' => $category ? $category->getColor() : '#233D63',
                'url' => $article->toUrl()->toString(),
            ];
        }

        // Preparar array de categorías con conteo de artículos.
        $category_items = [];
        foreach ($categories as $category) {
            $category_items[] = [
                'id' => $category->id(),
                'name' => $category->getName(),
                'slug' => $category->getSlug(),
                'color' => $category->getColor(),
                'icon' => $category->getIcon(),
                'count' => $this->categoryService->getArticleCount((int) $category->id()),
                'url' => $category->toUrl()->toString(),
            ];
        }

        // Preparar array de artículos trending.
        $trending_items = [];
        foreach ($trending as $article) {
            $trending_items[] = [
                'id' => $article->id(),
                'title' => $article->getTitle(),
                'url' => $article->toUrl()->toString(),
            ];
        }

        return [
            '#theme' => 'content_hub_blog_index',
            '#title' => $config->get('blog_title') ?? $this->t('Blog'),
            '#articles' => $article_items,
            '#categories' => $category_items,
            '#trending' => $trending_items,
            '#show_reading_time' => $config->get('show_reading_time') ?? TRUE,
            '#cache' => [
                'tags' => ['content_article_list', 'content_category_list'],
                'max-age' => 300,
            ],
        ];
    }

}
