<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\image\Entity\ImageStyle;
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
     * Generador de URLs de archivos.
     */
    protected FileUrlGeneratorInterface $fileUrlGenerator;

    /**
     * Constructs a CategoryController.
     */
    public function __construct(
        ArticleService $articleService,
        CategoryService $categoryService,
        FileUrlGeneratorInterface $fileUrlGenerator,
    ) {
        $this->articleService = $articleService;
        $this->categoryService = $categoryService;
        $this->fileUrlGenerator = $fileUrlGenerator;
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
            $image_data = $this->getImageData($article);
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
                'category_url' => $category ? $category->toUrl()->toString() : '',
                'featured_image' => $image_data['card'] ?? NULL,
                'featured_image_srcset' => $image_data['srcset'] ?? '',
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
                'contexts' => ['languages'],
                'max-age' => 300,
            ],
        ];
    }

    /**
     * Obtiene URLs de imagen con Image Styles para srcset responsive.
     *
     * @param mixed $article
     *   La entidad ContentArticle.
     *
     * @return array
     *   Array con claves 'card', 'featured', 'srcset', o vacío.
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
        $card_style = ImageStyle::load('article_card');
        $card_url = $card_style
            ? $card_style->buildUrl($uri)
            : $this->fileUrlGenerator->generateAbsoluteString($uri);

        $featured_style = ImageStyle::load('article_featured');
        $featured_url = $featured_style
            ? $featured_style->buildUrl($uri)
            : $this->fileUrlGenerator->generateAbsoluteString($uri);

        return [
            'card' => $card_url,
            'featured' => $featured_url,
            'srcset' => $card_url . ' 600w, ' . $featured_url . ' 1200w',
        ];
    }

}
