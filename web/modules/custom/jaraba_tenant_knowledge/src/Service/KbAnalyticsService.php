<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de analíticas de la base de conocimiento.
 *
 * PROPÓSITO:
 * Proporciona estadísticas y métricas sobre el rendimiento de la base
 * de conocimiento: artículos más vistos, ratio de utilidad, distribución
 * por categoría, etc.
 *
 * DIRECTRICES:
 * - Patrón try-catch con logger en todos los métodos públicos
 * - Queries directas a DB para agregaciones eficientes
 * - Traducciones con t() en etiquetas
 */
class KbAnalyticsService {

  /**
   * Constructor del servicio de analíticas KB.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad.
   * @param \Drupal\Core\Database\Connection $database
   *   Conexión a base de datos.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de log.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Connection $database,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Obtiene estadísticas generales de artículos.
   *
   * @param int|null $tenantId
   *   ID del tenant para filtrar, o NULL para todos.
   *
   * @return array
   *   Array con keys: total_articles, published, draft, archived,
   *   total_views, total_helpful, total_not_helpful, helpfulness_ratio.
   */
  public function getArticleStats(?int $tenantId): array {
    try {
      $query = $this->database->select('kb_article', 'a');
      $query->addExpression('COUNT(*)', 'total_articles');
      $query->addExpression("SUM(CASE WHEN a.article_status = 'published' THEN 1 ELSE 0 END)", 'published');
      $query->addExpression("SUM(CASE WHEN a.article_status = 'draft' THEN 1 ELSE 0 END)", 'draft');
      $query->addExpression("SUM(CASE WHEN a.article_status = 'archived' THEN 1 ELSE 0 END)", 'archived');
      $query->addExpression('COALESCE(SUM(a.view_count), 0)', 'total_views');
      $query->addExpression('COALESCE(SUM(a.helpful_count), 0)', 'total_helpful');
      $query->addExpression('COALESCE(SUM(a.not_helpful_count), 0)', 'total_not_helpful');

      if ($tenantId !== NULL) {
        $query->condition('a.tenant_id', $tenantId);
      }

      $result = $query->execute()->fetchAssoc();

      $totalHelpful = (int) ($result['total_helpful'] ?? 0);
      $totalNotHelpful = (int) ($result['total_not_helpful'] ?? 0);
      $totalFeedback = $totalHelpful + $totalNotHelpful;

      return [
        'total_articles' => (int) ($result['total_articles'] ?? 0),
        'published' => (int) ($result['published'] ?? 0),
        'draft' => (int) ($result['draft'] ?? 0),
        'archived' => (int) ($result['archived'] ?? 0),
        'total_views' => (int) ($result['total_views'] ?? 0),
        'total_helpful' => $totalHelpful,
        'total_not_helpful' => $totalNotHelpful,
        'helpfulness_ratio' => $totalFeedback > 0
          ? round($totalHelpful / $totalFeedback * 100, 1)
          : 0.0,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo estadísticas de artículos KB: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [
        'total_articles' => 0,
        'published' => 0,
        'draft' => 0,
        'archived' => 0,
        'total_views' => 0,
        'total_helpful' => 0,
        'total_not_helpful' => 0,
        'helpfulness_ratio' => 0.0,
      ];
    }
  }

  /**
   * Obtiene analíticas de búsqueda.
   *
   * @param int|null $tenantId
   *   ID del tenant para filtrar, o NULL para todos.
   *
   * @return array
   *   Array con keys: top_articles (los 10 más vistos).
   */
  public function getSearchAnalytics(?int $tenantId): array {
    try {
      $query = $this->database->select('kb_article', 'a');
      $query->fields('a', ['id', 'title', 'view_count', 'helpful_count', 'not_helpful_count']);
      $query->condition('a.article_status', 'published');
      $query->orderBy('a.view_count', 'DESC');
      $query->range(0, 10);

      if ($tenantId !== NULL) {
        $query->condition('a.tenant_id', $tenantId);
      }

      $results = $query->execute()->fetchAll();

      $topArticles = [];
      foreach ($results as $row) {
        $topArticles[] = [
          'id' => (int) $row->id,
          'title' => $row->title,
          'view_count' => (int) $row->view_count,
          'helpful_count' => (int) $row->helpful_count,
          'not_helpful_count' => (int) $row->not_helpful_count,
        ];
      }

      return [
        'top_articles' => $topArticles,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo analíticas de búsqueda KB: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [
        'top_articles' => [],
      ];
    }
  }

  /**
   * Obtiene distribución de artículos por categoría.
   *
   * @param int|null $tenantId
   *   ID del tenant para filtrar, o NULL para todos.
   *
   * @return array
   *   Array de categorías con keys: category_id, category_name, article_count, total_views.
   */
  public function getCategoryDistribution(?int $tenantId): array {
    try {
      $query = $this->database->select('kb_article', 'a');
      $query->leftJoin('kb_category', 'c', 'a.category_id = c.id');
      $query->addField('a', 'category_id');
      $query->addField('c', 'name', 'category_name');
      $query->addExpression('COUNT(a.id)', 'article_count');
      $query->addExpression('COALESCE(SUM(a.view_count), 0)', 'total_views');
      $query->condition('a.article_status', 'published');
      $query->groupBy('a.category_id');
      $query->groupBy('c.name');
      $query->orderBy('article_count', 'DESC');

      if ($tenantId !== NULL) {
        $query->condition('a.tenant_id', $tenantId);
      }

      $results = $query->execute()->fetchAll();

      $distribution = [];
      foreach ($results as $row) {
        $distribution[] = [
          'category_id' => $row->category_id ? (int) $row->category_id : NULL,
          'category_name' => $row->category_name ?? 'Sin categoría',
          'article_count' => (int) $row->article_count,
          'total_views' => (int) $row->total_views,
        ];
      }

      return $distribution;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo distribución por categoría KB: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

}
