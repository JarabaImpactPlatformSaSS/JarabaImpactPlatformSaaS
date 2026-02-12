<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\jaraba_content_hub\Entity\ContentCategory;
use Drupal\jaraba_content_hub\Service\ArticleService;
use Drupal\jaraba_content_hub\Service\CategoryService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for category pages.
 */
class CategoryController extends ControllerBase implements ContainerInjectionInterface
{

    /**
     * The article service.
     */
    protected ArticleService $articleService;

    /**
     * The category service.
     */
    protected CategoryService $categoryService;

    /**
     * Constructs a CategoryController.
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
     * Category page with articles.
     *
     * @param \Drupal\jaraba_content_hub\Entity\ContentCategory $content_category
     *   The category entity.
     *
     * @return array
     *   Render array.
     */
    public function view(ContentCategory $content_category): array
    {
        $config = $this->config('jaraba_content_hub.settings');
        $limit = $config->get('articles_per_page') ?? 12;

        $articles = $this->articleService->getPublishedArticles([
            'category' => $content_category->id(),
            'limit' => $limit,
        ]);

        $article_items = [];
        foreach ($articles as $article) {
            $article_items[] = [
                'id' => $article->id(),
                'title' => $article->getTitle(),
                'slug' => $article->getSlug(),
                'excerpt' => $article->getExcerpt(),
                'reading_time' => $article->getReadingTime(),
                'publish_date' => $article->get('publish_date')->value,
                'url' => $article->toUrl()->toString(),
            ];
        }

        return [
            '#theme' => 'content_hub_category_page',
            '#category' => [
                'id' => $content_category->id(),
                'name' => $content_category->getName(),
                'description' => $content_category->get('description')->value ?? '',
                'color' => $content_category->getColor(),
                'icon' => $content_category->getIcon(),
            ],
            '#articles' => $article_items,
            '#show_reading_time' => $config->get('show_reading_time') ?? TRUE,
            '#cache' => [
                'tags' => [
                    'content_article_list',
                    'content_category:' . $content_category->id(),
                ],
                'max-age' => 300,
            ],
        ];
    }

}
