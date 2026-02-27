<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Service;

use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;

/**
 * Servicio para generacion de feed RSS 2.0.
 *
 * Genera XML RSS 2.0 con:
 * - Canal con titulo, descripcion, link
 * - Items con titulo, link, descripcion, pubDate, dc:creator, category, guid
 * - Atom self-link para autodescubrimiento
 * - CDATA para descripciones con HTML
 *
 * Backportea la funcionalidad de BlogRssService con mejoras:
 * - Soporte para ContentAuthor (no solo usuario Drupal)
 * - Imagenes en items via enclosure
 * - Sin dependencia de TenantContextService (parametros explicitos)
 */
class RssService {

  /**
   * Construye un RssService.
   *
   * @param \Drupal\jaraba_content_hub\Service\ArticleService $articleService
   *   Servicio de articulos.
   * @param \Psr\Log\LoggerInterface $logger
   *   Servicio de logging.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $fileUrlGenerator
   *   Generador de URLs para archivos.
   */
  public function __construct(
    protected ArticleService $articleService,
    protected LoggerInterface $logger,
    protected FileUrlGeneratorInterface $fileUrlGenerator,
  ) {}

  /**
   * Genera el feed RSS como string XML.
   *
   * @param string $baseUrl
   *   URL base del sitio (scheme + host).
   * @param string $siteName
   *   Nombre del sitio/tenant para el titulo del canal.
   * @param string $language
   *   Codigo de idioma ISO 639-1 (ej: 'es', 'en').
   * @param int $limit
   *   Numero maximo de items en el feed.
   *
   * @return string
   *   XML RSS 2.0 bien formado.
   */
  public function generateFeed(string $baseUrl, string $siteName, string $language = 'es', int $limit = 20): string {
    // ROUTE-LANGPREFIX-001: URLs generadas via ruta.
    $blogUrl = Url::fromRoute('jaraba_content_hub.blog')->setAbsolute()->toString();
    $feedUrl = Url::fromRoute('jaraba_content_hub.blog.rss')->setAbsolute()->toString();

    // Obtener articulos publicados ordenados por fecha.
    $articles = $this->articleService->getPublishedArticles(['limit' => $limit]);

    $xml = new \DOMDocument('1.0', 'UTF-8');
    $xml->formatOutput = TRUE;

    // Elemento raiz RSS.
    $rss = $xml->createElement('rss');
    $rss->setAttribute('version', '2.0');
    $rss->setAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
    $rss->setAttribute('xmlns:dc', 'http://purl.org/dc/elements/1.1/');
    $xml->appendChild($rss);

    // Canal.
    $channel = $xml->createElement('channel');
    $rss->appendChild($channel);

    $channel->appendChild($xml->createElement('title', $this->xmlEncode($siteName . ' - Blog')));
    $channel->appendChild($xml->createElement('link', $blogUrl));
    $channel->appendChild($xml->createElement('description', $this->xmlEncode('Blog de ' . $siteName)));
    $channel->appendChild($xml->createElement('language', $language));
    $channel->appendChild($xml->createElement('lastBuildDate', date('r')));
    $channel->appendChild($xml->createElement('generator', 'Jaraba Impact Platform'));

    // Atom self-link (autodescubrimiento).
    $atomLink = $xml->createElement('atom:link');
    $atomLink->setAttribute('href', $feedUrl);
    $atomLink->setAttribute('rel', 'self');
    $atomLink->setAttribute('type', 'application/rss+xml');
    $channel->appendChild($atomLink);

    // Items.
    foreach ($articles as $article) {
      $item = $xml->createElement('item');
      $channel->appendChild($item);

      // ROUTE-LANGPREFIX-001: URL generada via ruta, no hardcoded.
      $slug = $article->get('slug')->value ?? '';
      $articleUrl = !empty($slug)
        ? Url::fromRoute('entity.content_article.canonical', ['content_article' => $slug])->setAbsolute()->toString()
        : $baseUrl . '/blog/' . $article->id();

      $item->appendChild($xml->createElement('title', $this->xmlEncode($article->label() ?? '')));
      $item->appendChild($xml->createElement('link', $articleUrl));

      // Descripcion: excerpt o cuerpo truncado.
      $description = $article->get('excerpt')->value ?? '';
      if (empty($description)) {
        $body = $article->get('body')->value ?? '';
        $description = mb_substr(strip_tags($body), 0, 300);
        if (strlen(strip_tags($body)) > 300) {
          $description .= '...';
        }
      }
      $descCdata = $xml->createCDATASection($description);
      $descEl = $xml->createElement('description');
      $descEl->appendChild($descCdata);
      $item->appendChild($descEl);

      // GUID (permalink).
      $guid = $xml->createElement('guid', $articleUrl);
      $guid->setAttribute('isPermaLink', 'true');
      $item->appendChild($guid);

      // Fecha de publicacion.
      $publishedAt = $article->get('publish_date')->value;
      if ($publishedAt) {
        $timestamp = strtotime($publishedAt);
        if ($timestamp) {
          $item->appendChild($xml->createElement('pubDate', date('r', $timestamp)));
        }
      }

      // Autor: ContentAuthor > Owner.
      $authorName = $this->resolveAuthorName($article);
      if ($authorName) {
        $item->appendChild($xml->createElement('dc:creator', $this->xmlEncode($authorName)));
      }

      // Categoria.
      $category = $article->get('category')->entity;
      if ($category) {
        $item->appendChild($xml->createElement('category', $this->xmlEncode($category->label() ?? '')));
      }

      // Imagen como enclosure (si existe).
      $imageUrl = $this->resolveImageUrl($article);
      if ($imageUrl) {
        $enclosure = $xml->createElement('enclosure');
        $enclosure->setAttribute('url', $imageUrl);
        $enclosure->setAttribute('type', 'image/jpeg');
        $item->appendChild($enclosure);
      }
    }

    return $xml->saveXML() ?: '';
  }

  /**
   * Resuelve el nombre del autor del articulo.
   *
   * @param object $article
   *   La entidad ContentArticle.
   *
   * @return string
   *   Nombre del autor o cadena vacia.
   */
  protected function resolveAuthorName(object $article): string {
    // Prioridad 1: ContentAuthor dedicado.
    $contentAuthor = $article->get('content_author')->entity;
    if ($contentAuthor) {
      return $contentAuthor->getDisplayName() ?? '';
    }

    // Prioridad 2: Owner (usuario Drupal).
    if (method_exists($article, 'getOwner')) {
      $owner = $article->getOwner();
      if ($owner) {
        return $owner->getDisplayName() ?? '';
      }
    }

    return '';
  }

  /**
   * Resuelve la URL absoluta de la imagen del articulo.
   *
   * @param object $article
   *   La entidad ContentArticle.
   *
   * @return string
   *   URL absoluta de la imagen o cadena vacia.
   */
  protected function resolveImageUrl(object $article): string {
    $featuredImage = $article->get('featured_image')->entity;
    if ($featuredImage) {
      return $this->fileUrlGenerator->generateAbsoluteString($featuredImage->getFileUri());
    }

    return '';
  }

  /**
   * Codifica una cadena para XML.
   *
   * @param string $text
   *   Texto a codificar.
   *
   * @return string
   *   Texto con entidades XML escapadas.
   */
  protected function xmlEncode(string $text): string {
    return htmlspecialchars($text, ENT_XML1, 'UTF-8');
  }

}
