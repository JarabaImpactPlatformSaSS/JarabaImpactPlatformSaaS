<?php

declare(strict_types=1);

namespace Drupal\jaraba_blog\Service;

use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Psr\Log\LoggerInterface;

/**
 * Servicio de SEO para el blog.
 *
 * Genera:
 * - Meta tags (title, description, robots)
 * - Open Graph tags (og:title, og:description, og:image, og:type)
 * - Twitter Cards (twitter:card, twitter:title, twitter:description)
 * - Schema.org JSON-LD (BlogPosting, Article, NewsArticle)
 * - Canonical URLs
 */
class BlogSeoService {

  /**
   * Constructor.
   */
  public function __construct(
    protected BlogService $blogService,
    protected TenantContextService $tenantContext,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Genera los meta tags SEO para un post.
   *
   * @param object $post
   *   Entidad BlogPost.
   *
   * @return array
   *   Array con meta_tags, og_tags, twitter_tags, json_ld, canonical.
   */
  public function generatePostSeo(object $post): array {
    $baseUrl = \Drupal::request()->getSchemeAndHttpHost();
    $postUrl = $baseUrl . '/blog/' . $post->getSlug();
    $tenant = $this->tenantContext->getCurrentTenant();
    $tenantName = $tenant ? $tenant->label() : '';

    // Meta tags basicos.
    $metaTitle = $post->get('meta_title')->value ?: $post->getTitle();
    $metaDescription = $post->get('meta_description')->value ?: $post->getExcerpt();
    if (empty($metaDescription)) {
      $metaDescription = mb_substr(strip_tags($post->getBody()), 0, 155);
    }

    // Imagen.
    $imageUrl = '';
    $ogImage = $post->get('og_image')->entity;
    if ($ogImage) {
      $imageUrl = \Drupal::service('file_url_generator')->generateAbsoluteString($ogImage->getFileUri());
    }
    elseif ($post->get('featured_image')->entity) {
      $imageUrl = \Drupal::service('file_url_generator')->generateAbsoluteString(
        $post->get('featured_image')->entity->getFileUri()
      );
    }

    // Autor.
    $author = $post->get('author_id')->entity;
    $authorName = $author ? $author->getDisplayName() : '';

    // Categoria.
    $category = $post->get('category_id')->entity;
    $categoryName = $category ? $category->getName() : '';

    return [
      'meta_tags' => [
        'title' => $metaTitle,
        'description' => $metaDescription,
        'robots' => 'index, follow',
      ],
      'og_tags' => [
        'og:type' => 'article',
        'og:title' => $metaTitle,
        'og:description' => $metaDescription,
        'og:url' => $postUrl,
        'og:image' => $imageUrl,
        'og:site_name' => $tenantName,
        'article:published_time' => $post->get('published_at')->value ?? '',
        'article:modified_time' => date('c', (int) $post->get('changed')->value),
        'article:author' => $authorName,
        'article:section' => $categoryName,
        'article:tag' => implode(',', $post->getTagsArray()),
      ],
      'twitter_tags' => [
        'twitter:card' => $imageUrl ? 'summary_large_image' : 'summary',
        'twitter:title' => $metaTitle,
        'twitter:description' => $metaDescription,
        'twitter:image' => $imageUrl,
      ],
      'json_ld' => $this->generateJsonLd($post, $postUrl, $imageUrl, $authorName, $tenantName),
      'canonical' => $postUrl,
    ];
  }

  /**
   * Genera Schema.org JSON-LD para un post.
   */
  protected function generateJsonLd(
    object $post,
    string $postUrl,
    string $imageUrl,
    string $authorName,
    string $publisherName,
  ): array {
    $schemaType = $post->get('schema_type')->value ?: 'BlogPosting';

    $jsonLd = [
      '@context' => 'https://schema.org',
      '@type' => $schemaType,
      'headline' => $post->getTitle(),
      'description' => $post->getExcerpt() ?: mb_substr(strip_tags($post->getBody()), 0, 155),
      'url' => $postUrl,
      'mainEntityOfPage' => [
        '@type' => 'WebPage',
        '@id' => $postUrl,
      ],
    ];

    if ($imageUrl) {
      $jsonLd['image'] = [
        '@type' => 'ImageObject',
        'url' => $imageUrl,
      ];
    }

    if ($authorName) {
      $jsonLd['author'] = [
        '@type' => 'Person',
        'name' => $authorName,
      ];
    }

    if ($publisherName) {
      $jsonLd['publisher'] = [
        '@type' => 'Organization',
        'name' => $publisherName,
      ];
    }

    $publishedAt = $post->get('published_at')->value;
    if ($publishedAt) {
      $jsonLd['datePublished'] = $publishedAt;
    }

    $jsonLd['dateModified'] = date('c', (int) $post->get('changed')->value);

    $readingTime = $post->getReadingTime();
    if ($readingTime > 0) {
      $jsonLd['timeRequired'] = 'PT' . $readingTime . 'M';
    }

    $wordCount = str_word_count(strip_tags($post->getBody()));
    if ($wordCount > 0) {
      $jsonLd['wordCount'] = $wordCount;
    }

    return $jsonLd;
  }

  /**
   * Genera meta tags SEO para la pagina de listado del blog.
   */
  public function generateListingSeo(?string $categoryName = NULL, ?string $authorName = NULL): array {
    $baseUrl = \Drupal::request()->getSchemeAndHttpHost();
    $tenant = $this->tenantContext->getCurrentTenant();
    $tenantName = $tenant ? $tenant->label() : '';

    $title = 'Blog';
    $description = (string) t('Blog de @name', ['@name' => $tenantName]);
    $url = $baseUrl . '/blog';

    if ($categoryName) {
      $title = $categoryName . ' - Blog';
      $description = (string) t('Articulos sobre @category en el blog de @name', [
        '@category' => $categoryName,
        '@name' => $tenantName,
      ]);
    }

    if ($authorName) {
      $title = $authorName . ' - Blog';
      $description = (string) t('Articulos de @author en el blog de @name', [
        '@author' => $authorName,
        '@name' => $tenantName,
      ]);
    }

    return [
      'meta_tags' => [
        'title' => $title,
        'description' => $description,
        'robots' => 'index, follow',
      ],
      'og_tags' => [
        'og:type' => 'website',
        'og:title' => $title,
        'og:description' => $description,
        'og:url' => $url,
        'og:site_name' => $tenantName,
      ],
      'canonical' => $url,
    ];
  }

}
