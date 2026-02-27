<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Service;

use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\jaraba_content_hub\Entity\ContentArticleInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio SEO para el Content Hub.
 *
 * Genera meta tags completos, Open Graph, Twitter Cards, Schema.org JSON-LD,
 * y proporciona analisis de calidad SEO para articulos y paginas de listado.
 *
 * Backportea y consolida la funcionalidad de BlogSeoService.
 *
 * ESPECIFICACION: Doc 128 - Platform_AI_Content_Hub_v2
 */
class SeoService {

  /**
   * Construye un SeoService.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   El servicio de logging.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $fileUrlGenerator
   *   Generador de URLs para archivos.
   */
  public function __construct(
    protected LoggerInterface $logger,
    protected FileUrlGeneratorInterface $fileUrlGenerator,
  ) {}

  /**
   * Genera los meta tags SEO completos para un articulo.
   *
   * Produce un array estructurado con meta tags, Open Graph,
   * Twitter Cards, JSON-LD Schema.org y URL canonica.
   *
   * @param \Drupal\jaraba_content_hub\Entity\ContentArticleInterface $article
   *   La entidad ContentArticle.
   * @param string $articleUrl
   *   URL canonica absoluta del articulo (generada via Url::fromRoute).
   * @param string $siteName
   *   Nombre del sitio/tenant para og:site_name.
   *
   * @return array
   *   Array con claves: meta_tags, og_tags, twitter_tags, json_ld, canonical.
   */
  public function generateArticleSeo(ContentArticleInterface $article, string $articleUrl, string $siteName = ''): array {

    // Meta tags: seo_title > title, seo_description > excerpt > body truncado.
    $metaTitle = $article->get('seo_title')->value ?: $article->label();
    $metaDescription = $article->get('seo_description')->value ?: ($article->get('excerpt')->value ?? '');
    if (empty($metaDescription)) {
      $body = $article->get('body')->value ?? '';
      $metaDescription = mb_substr(strip_tags($body), 0, 155);
    }

    // Imagen: og_image > featured_image.
    $imageUrl = $this->resolveArticleImageUrl($article);

    // Autor: ContentAuthor > Owner.
    $authorName = $this->resolveAuthorName($article);

    // Categoria.
    $categoryName = '';
    $category = $article->get('category')->entity;
    if ($category) {
      $categoryName = $category->label() ?? '';
    }

    // Tags.
    $tags = $article->getTags();

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
        'og:url' => $articleUrl,
        'og:image' => $imageUrl,
        'og:site_name' => $siteName,
        'article:published_time' => $article->get('publish_date')->value ?? '',
        'article:modified_time' => date('c', (int) $article->getChangedTime()),
        'article:author' => $authorName,
        'article:section' => $categoryName,
        'article:tag' => $tags,
      ],
      'twitter_tags' => [
        'twitter:card' => $imageUrl ? 'summary_large_image' : 'summary',
        'twitter:title' => $metaTitle,
        'twitter:description' => $metaDescription,
        'twitter:image' => $imageUrl,
      ],
      'json_ld' => $this->generateJsonLd($article, $articleUrl, $imageUrl, $authorName, $siteName),
      'canonical' => $articleUrl,
    ];
  }

  /**
   * Genera Schema.org JSON-LD para un articulo.
   *
   * Soporta tipos configurables: BlogPosting, Article, NewsArticle.
   * Incluye timeRequired, wordCount y BreadcrumbList como @graph.
   *
   * @param \Drupal\jaraba_content_hub\Entity\ContentArticleInterface $article
   *   La entidad ContentArticle.
   * @param string $articleUrl
   *   URL canonica del articulo.
   * @param string $imageUrl
   *   URL absoluta de la imagen destacada.
   * @param string $authorName
   *   Nombre del autor.
   * @param string $publisherName
   *   Nombre de la organizacion publicadora.
   *
   * @return array
   *   Array JSON-LD listo para json_encode().
   */
  protected function generateJsonLd(
    ContentArticleInterface $article,
    string $articleUrl,
    string $imageUrl,
    string $authorName,
    string $publisherName,
  ): array {
    // Tipo configurable via campo schema_type (BlogPosting/Article/NewsArticle).
    $schemaType = $article->getSchemaType() ?: 'BlogPosting';

    $jsonLd = [
      '@context' => 'https://schema.org',
      '@type' => $schemaType,
      'headline' => $article->label() ?? '',
      'description' => $article->get('excerpt')->value ?: mb_substr(strip_tags($article->get('body')->value ?? ''), 0, 155),
      'url' => $articleUrl,
      'mainEntityOfPage' => [
        '@type' => 'WebPage',
        '@id' => $articleUrl,
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

    // Fechas.
    $publishedAt = $article->get('publish_date')->value;
    if ($publishedAt) {
      $jsonLd['datePublished'] = $publishedAt;
    }
    $jsonLd['dateModified'] = date('c', (int) $article->getChangedTime());

    // Tiempo de lectura (ISO 8601 duration).
    $readingTime = (int) ($article->get('reading_time')->value ?? 0);
    if ($readingTime > 0) {
      $jsonLd['timeRequired'] = 'PT' . $readingTime . 'M';
    }

    // Conteo de palabras.
    $body = $article->get('body')->value ?? '';
    $wordCount = str_word_count(strip_tags($body));
    if ($wordCount > 0) {
      $jsonLd['wordCount'] = $wordCount;
    }

    // REV-PHASE7: Conteo de comentarios aprobados para Schema.org commentCount.
    try {
      if (\Drupal::entityTypeManager()->hasDefinition('content_comment')) {
        $commentCount = \Drupal::entityTypeManager()
          ->getStorage('content_comment')
          ->getQuery()
          ->accessCheck(TRUE)
          ->condition('article_id', $article->id())
          ->condition('status', 1)
          ->count()
          ->execute();
        if ($commentCount > 0) {
          $jsonLd['commentCount'] = (int) $commentCount;
        }
      }
    }
    catch (\Exception) {
      // Non-blocking: comment count is supplementary.
    }

    return $jsonLd;
  }

  /**
   * Genera meta tags SEO para paginas de listado (index, categoria, autor).
   *
   * @param string $canonicalUrl
   *   URL canonica absoluta de la pagina (generada via Url::fromRoute).
   * @param string $siteName
   *   Nombre del sitio/tenant.
   * @param string|null $categoryName
   *   Nombre de la categoria (si es filtro por categoria).
   * @param string|null $authorName
   *   Nombre del autor (si es filtro por autor).
   *
   * @return array
   *   Array con claves: meta_tags, og_tags, canonical.
   */
  public function generateListingSeo(string $canonicalUrl, string $siteName, ?string $categoryName = NULL, ?string $authorName = NULL): array {
    $title = 'Blog';
    $description = $siteName ? 'Blog de ' . $siteName : 'Blog';

    if ($categoryName) {
      $title = $categoryName . ' - Blog';
      $description = 'Articulos sobre ' . $categoryName . ' en el blog de ' . $siteName;
    }

    if ($authorName) {
      $title = $authorName . ' - Blog';
      $description = 'Articulos de ' . $authorName . ' en el blog de ' . $siteName;
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
        'og:url' => $canonicalUrl,
        'og:site_name' => $siteName,
      ],
      'canonical' => $canonicalUrl,
    ];
  }

  /**
   * Genera JSON-LD de tipo BreadcrumbList.
   *
   * @param array $items
   *   Array de items del breadcrumb, cada uno con claves:
   *   - 'name': Nombre visible del item.
   *   - 'url': URL absoluta del item.
   *
   * @return array
   *   Array JSON-LD BreadcrumbList listo para json_encode().
   */
  public function generateBreadcrumbJsonLd(array $items): array {
    $listItems = [];
    foreach ($items as $position => $item) {
      $listItems[] = [
        '@type' => 'ListItem',
        'position' => $position + 1,
        'name' => $item['name'] ?? '',
        'item' => $item['url'] ?? '',
      ];
    }

    return [
      '@context' => 'https://schema.org',
      '@type' => 'BreadcrumbList',
      'itemListElement' => $listItems,
    ];
  }

  /**
   * Genera el markup Schema.org de tipo Article (legacy).
   *
   * Mantiene compatibilidad con el API existente basado en arrays.
   *
   * @param array $data
   *   Datos del articulo con claves: title, excerpt, publish_date,
   *   changed, author_name, publisher_name, publisher_logo,
   *   featured_image, url.
   *
   * @return array
   *   Array Schema.org JSON-LD.
   */
  public function generateArticleSchema(array $data): array {
    return [
      '@context' => 'https://schema.org',
      '@type' => 'Article',
      'headline' => $data['title'] ?? '',
      'description' => $data['excerpt'] ?? '',
      'datePublished' => $data['publish_date'] ?? '',
      'dateModified' => $data['changed'] ?? '',
      'author' => [
        '@type' => 'Person',
        'name' => $data['author_name'] ?? '',
      ],
      'publisher' => [
        '@type' => 'Organization',
        'name' => $data['publisher_name'] ?? 'Jaraba',
        'logo' => [
          '@type' => 'ImageObject',
          'url' => $data['publisher_logo'] ?? '',
        ],
      ],
      'image' => $data['featured_image'] ?? '',
      'mainEntityOfPage' => [
        '@type' => 'WebPage',
        '@id' => $data['url'] ?? '',
      ],
    ];
  }

  /**
   * Analiza la calidad SEO de un articulo.
   *
   * Evalua multiples factores SEO y genera una puntuacion
   * de 0-100 junto con sugerencias de mejora.
   *
   * @param array $data
   *   Datos del articulo con claves: title, seo_title, seo_description,
   *   answer_capsule, featured_image, body.
   *
   * @return array
   *   Resultado con: score, suggestions, word_count.
   */
  public function analyzeArticleSeo(array $data): array {
    $score = 0;
    $suggestions = [];

    // Verificacion del titulo.
    $title = $data['seo_title'] ?? $data['title'] ?? '';
    $titleLength = strlen($title);
    if ($titleLength >= 30 && $titleLength <= 60) {
      $score += 20;
    }
    else {
      $suggestions[] = 'El titulo SEO deberia tener entre 30-60 caracteres.';
    }

    // Verificacion de la descripcion.
    $description = $data['seo_description'] ?? '';
    $descLength = strlen($description);
    if ($descLength >= 120 && $descLength <= 160) {
      $score += 20;
    }
    else {
      $suggestions[] = 'La meta description deberia tener entre 120-160 caracteres.';
    }

    // Verificacion del Answer Capsule.
    $answerCapsule = $data['answer_capsule'] ?? '';
    if (!empty($answerCapsule)) {
      $score += 15;
    }
    else {
      $suggestions[] = 'Anade un Answer Capsule para optimizacion GEO.';
    }

    // Verificacion de imagen destacada.
    if (!empty($data['featured_image'])) {
      $score += 15;
    }
    else {
      $suggestions[] = 'Anade una imagen destacada para compartir en redes.';
    }

    // Verificacion de longitud del contenido.
    $body = $data['body'] ?? '';
    $wordCount = str_word_count(strip_tags($body));
    if ($wordCount >= 300) {
      $score += 15;
    }
    else {
      $suggestions[] = 'El articulo deberia tener al menos 300 palabras.';
    }

    if ($wordCount >= 1000) {
      $score += 15;
    }
    else {
      $suggestions[] = 'El contenido largo (1000+ palabras) tiene mejor rendimiento.';
    }

    return [
      'score' => min(100, $score),
      'suggestions' => $suggestions,
      'word_count' => $wordCount,
    ];
  }

  /**
   * Resuelve la URL absoluta de la imagen del articulo.
   *
   * Prioriza og_image sobre featured_image.
   *
   * @param \Drupal\jaraba_content_hub\Entity\ContentArticleInterface $article
   *   La entidad ContentArticle.
   *
   * @return string
   *   URL absoluta de la imagen, o cadena vacia.
   */
  protected function resolveArticleImageUrl(ContentArticleInterface $article): string {
    // Prioridad 1: og_image (imagen especifica para redes).
    $ogImage = $article->get('og_image')->entity;
    if ($ogImage) {
      return $this->fileUrlGenerator->generateAbsoluteString($ogImage->getFileUri());
    }

    // Prioridad 2: featured_image.
    $featuredImage = $article->get('featured_image')->entity;
    if ($featuredImage) {
      return $this->fileUrlGenerator->generateAbsoluteString($featuredImage->getFileUri());
    }

    return '';
  }

  /**
   * Resuelve el nombre del autor del articulo.
   *
   * Prioriza ContentAuthor (content_author field) sobre el owner (uid).
   *
   * @param \Drupal\jaraba_content_hub\Entity\ContentArticleInterface $article
   *   La entidad ContentArticle.
   *
   * @return string
   *   Nombre del autor, o cadena vacia.
   */
  protected function resolveAuthorName(ContentArticleInterface $article): string {
    // Prioridad 1: ContentAuthor dedicado.
    $contentAuthor = $article->get('content_author')->entity;
    if ($contentAuthor) {
      return $contentAuthor->getDisplayName() ?? '';
    }

    // Prioridad 2: Owner (usuario Drupal).
    $owner = $article->getOwner();
    if ($owner) {
      return $owner->getDisplayName() ?? '';
    }

    return '';
  }

}
