<?php

declare(strict_types=1);

namespace Drupal\jaraba_blog\Service;

use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Psr\Log\LoggerInterface;

/**
 * Servicio para generacion de feed RSS/Atom per-tenant.
 *
 * Genera XML RSS 2.0 con:
 * - Canal con titulo, descripcion, link del tenant
 * - Items con titulo, link, descripcion, pubDate, author, category, guid
 * - Imagen del canal (logo del tenant)
 */
class BlogRssService {

  /**
   * Constructor.
   */
  public function __construct(
    protected BlogService $blogService,
    protected TenantContextService $tenantContext,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Genera el feed RSS como string XML.
   *
   * @param int $limit
   *   Numero de posts en el feed.
   *
   * @return string
   *   XML RSS 2.0 bien formado.
   */
  public function generateFeed(int $limit = 20): string {
    $tenant = $this->tenantContext->getCurrentTenant();
    $tenantName = $tenant ? $tenant->label() : 'Blog';

    $result = $this->blogService->listPosts(
      ['status' => 'published'],
      0,
      $limit
    );

    $baseUrl = \Drupal::request()->getSchemeAndHttpHost();
    $blogUrl = $baseUrl . '/blog';
    $feedUrl = $baseUrl . '/blog/feed.xml';

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

    $channel->appendChild($xml->createElement('title', $this->xmlEncode($tenantName . ' - Blog')));
    $channel->appendChild($xml->createElement('link', $blogUrl));
    $channel->appendChild($xml->createElement('description', $this->xmlEncode((string) t('Blog de @name', ['@name' => $tenantName]))));
    $channel->appendChild($xml->createElement('language', \Drupal::languageManager()->getCurrentLanguage()->getId()));
    $channel->appendChild($xml->createElement('lastBuildDate', date('r')));

    // Atom self-link.
    $atomLink = $xml->createElement('atom:link');
    $atomLink->setAttribute('href', $feedUrl);
    $atomLink->setAttribute('rel', 'self');
    $atomLink->setAttribute('type', 'application/rss+xml');
    $channel->appendChild($atomLink);

    // Items.
    foreach ($result['posts'] as $post) {
      $item = $xml->createElement('item');
      $channel->appendChild($item);

      $postUrl = $baseUrl . '/blog/' . $post->getSlug();

      $item->appendChild($xml->createElement('title', $this->xmlEncode($post->getTitle())));
      $item->appendChild($xml->createElement('link', $postUrl));

      // Descripcion (excerpt o truncated body).
      $description = $post->getExcerpt();
      if (empty($description)) {
        $description = mb_substr(strip_tags($post->getBody()), 0, 300) . '...';
      }
      $descCdata = $xml->createCDATASection($description);
      $descEl = $xml->createElement('description');
      $descEl->appendChild($descCdata);
      $item->appendChild($descEl);

      // GUID.
      $guid = $xml->createElement('guid', $postUrl);
      $guid->setAttribute('isPermaLink', 'true');
      $item->appendChild($guid);

      // Fecha de publicacion.
      $publishedAt = $post->get('published_at')->value;
      if ($publishedAt) {
        $timestamp = strtotime($publishedAt);
        if ($timestamp) {
          $item->appendChild($xml->createElement('pubDate', date('r', $timestamp)));
        }
      }

      // Autor.
      $author = $post->get('author_id')->entity;
      if ($author) {
        $item->appendChild($xml->createElement('dc:creator', $this->xmlEncode($author->getDisplayName())));
      }

      // Categoria.
      $category = $post->get('category_id')->entity;
      if ($category) {
        $item->appendChild($xml->createElement('category', $this->xmlEncode($category->getName())));
      }
    }

    return $xml->saveXML();
  }

  /**
   * Codifica una cadena para XML.
   */
  protected function xmlEncode(string $text): string {
    return htmlspecialchars($text, ENT_XML1, 'UTF-8');
  }

}
