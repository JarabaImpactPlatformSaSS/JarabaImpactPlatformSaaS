<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de búsqueda semántica en la base de conocimiento.
 *
 * PROPÓSITO:
 * Proporciona búsqueda semántica sobre artículos KB por título,
 * cuerpo y tags. Soporta sugerencias de autocompletado.
 *
 * LÓGICA:
 * - Búsqueda LIKE sobre campos title, body y tags
 * - Filtrado por tenant_id y estado publicado
 * - Sugerencias basadas en títulos coincidentes
 * - Ordenación por relevancia (título primero, luego body)
 *
 * DIRECTRICES:
 * - Patrón try-catch con logger en todos los métodos públicos
 * - Traducciones con t() en etiquetas
 */
class KbSemanticSearchService {

  /**
   * Constructor del servicio de búsqueda semántica KB.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de log.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Busca artículos en la base de conocimiento.
   *
   * @param string $query
   *   Término de búsqueda.
   * @param int|null $tenantId
   *   ID del tenant para filtrar, o NULL para todos.
   * @param int $limit
   *   Número máximo de resultados.
   *
   * @return array
   *   Array de resultados con keys: id, title, slug, summary, score.
   */
  public function search(string $query, ?int $tenantId, int $limit = 10): array {
    try {
      $query = trim($query);
      if (mb_strlen($query) < 2) {
        return [];
      }

      $storage = $this->entityTypeManager->getStorage('kb_article');

      // Búsqueda en títulos.
      $titleQuery = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('article_status', 'published')
        ->condition('title', '%' . $query . '%', 'LIKE')
        ->sort('view_count', 'DESC')
        ->range(0, $limit);

      if ($tenantId !== NULL) {
        $titleQuery->condition('tenant_id', $tenantId);
      }

      $titleIds = $titleQuery->execute();

      // Búsqueda en body.
      $bodyQuery = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('article_status', 'published')
        ->condition('body', '%' . $query . '%', 'LIKE')
        ->sort('view_count', 'DESC')
        ->range(0, $limit);

      if ($tenantId !== NULL) {
        $bodyQuery->condition('tenant_id', $tenantId);
      }

      $bodyIds = $bodyQuery->execute();

      // Búsqueda en tags.
      $tagsQuery = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('article_status', 'published')
        ->condition('tags', '%' . $query . '%', 'LIKE')
        ->sort('view_count', 'DESC')
        ->range(0, $limit);

      if ($tenantId !== NULL) {
        $tagsQuery->condition('tenant_id', $tenantId);
      }

      $tagsIds = $tagsQuery->execute();

      // Combinar resultados priorizando título > tags > body.
      $orderedIds = [];
      foreach ($titleIds as $id) {
        $orderedIds[$id] = 3;
      }
      foreach ($tagsIds as $id) {
        if (!isset($orderedIds[$id])) {
          $orderedIds[$id] = 2;
        }
      }
      foreach ($bodyIds as $id) {
        if (!isset($orderedIds[$id])) {
          $orderedIds[$id] = 1;
        }
      }

      // Ordenar por score descendente.
      arsort($orderedIds);

      // Limitar resultados.
      $orderedIds = array_slice($orderedIds, 0, $limit, TRUE);

      if (empty($orderedIds)) {
        return [];
      }

      $articles = $storage->loadMultiple(array_keys($orderedIds));

      $results = [];
      foreach ($orderedIds as $id => $score) {
        if (!isset($articles[$id])) {
          continue;
        }
        /** @var \Drupal\jaraba_tenant_knowledge\Entity\KbArticle $article */
        $article = $articles[$id];
        $results[] = [
          'id' => (int) $article->id(),
          'title' => $article->getTitle(),
          'slug' => $article->getSlug(),
          'summary' => $article->getSummary() ?: $this->truncate($article->getBody(), 200),
          'score' => $score,
        ];
      }

      return $results;
    }
    catch (\Exception $e) {
      $this->logger->error('Error en búsqueda KB "@query": @error', [
        '@query' => $query,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Obtiene sugerencias de autocompletado.
   *
   * @param string $query
   *   Término de búsqueda parcial.
   * @param int|null $tenantId
   *   ID del tenant para filtrar, o NULL para todos.
   *
   * @return array
   *   Array de sugerencias con keys: id, title, slug.
   */
  public function getSuggestions(string $query, ?int $tenantId): array {
    try {
      $query = trim($query);
      if (mb_strlen($query) < 2) {
        return [];
      }

      $storage = $this->entityTypeManager->getStorage('kb_article');

      $dbQuery = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('article_status', 'published')
        ->condition('title', '%' . $query . '%', 'LIKE')
        ->sort('view_count', 'DESC')
        ->range(0, 5);

      if ($tenantId !== NULL) {
        $dbQuery->condition('tenant_id', $tenantId);
      }

      $ids = $dbQuery->execute();

      if (empty($ids)) {
        return [];
      }

      $articles = $storage->loadMultiple($ids);
      $suggestions = [];

      foreach ($articles as $article) {
        /** @var \Drupal\jaraba_tenant_knowledge\Entity\KbArticle $article */
        $suggestions[] = [
          'id' => (int) $article->id(),
          'title' => $article->getTitle(),
          'slug' => $article->getSlug(),
        ];
      }

      return $suggestions;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo sugerencias KB para "@query": @error', [
        '@query' => $query,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Trunca texto al límite especificado sin cortar palabras.
   */
  protected function truncate(string $text, int $limit): string {
    $text = strip_tags($text);
    if (mb_strlen($text) <= $limit) {
      return $text;
    }
    $truncated = mb_substr($text, 0, $limit);
    $lastSpace = mb_strrpos($truncated, ' ');
    if ($lastSpace !== FALSE) {
      $truncated = mb_substr($truncated, 0, $lastSpace);
    }
    return $truncated . '...';
  }

}
