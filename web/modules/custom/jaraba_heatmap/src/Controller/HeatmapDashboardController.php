<?php

declare(strict_types=1);

namespace Drupal\jaraba_heatmap\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\jaraba_heatmap\Service\HeatmapAggregatorService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;

/**
 * Controller for the Heatmap Analytics Dashboard.
 *
 * Renderiza la página de dashboard frontend con datos iniciales
 * siguiendo el patrón Zero Region (sin sidebars).
 *
 * Ref: Spec 20260130a §8, §9
 */
class HeatmapDashboardController extends ControllerBase {

  /**
   * Database connection.
   */
  protected Connection $database;

  /**
   * Aggregator service.
   */
  protected HeatmapAggregatorService $aggregator;

  // AUDIT-SEC-N06: Server-side tenant resolution, prevents IDOR.
  /**
   * Tenant context service.
   */
  protected TenantContextService $tenantContext;

  /**
   * Constructs a HeatmapDashboardController.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection.
   * @param \Drupal\jaraba_heatmap\Service\HeatmapAggregatorService $aggregator
   *   Aggregator service.
   * @param \Drupal\ecosistema_jaraba_core\Service\TenantContextService $tenantContext
   *   Tenant context service.
   */
  public function __construct(
    Connection $database,
    HeatmapAggregatorService $aggregator,
    TenantContextService $tenantContext, // AUDIT-SEC-N06: Server-side tenant resolution, prevents IDOR.
  ) {
    $this->database = $database;
    $this->aggregator = $aggregator;
    // AUDIT-SEC-N06: Server-side tenant resolution, prevents IDOR.
    $this->tenantContext = $tenantContext;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('database'),
      $container->get('jaraba_heatmap.aggregator'),
      $container->get('ecosistema_jaraba_core.tenant_context'), // AUDIT-SEC-N06: Server-side tenant resolution, prevents IDOR.
    );
  }

  /**
   * Renders the heatmap analytics dashboard.
   *
   * @return array
   *   A render array.
   */
  public function dashboard(): array {
    // AUDIT-SEC-N06: Server-side tenant resolution, prevents IDOR.
    $tenantId = $this->getTenantId();
    if ($tenantId <= 0) {
      throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException('Tenant context required.');
    }

    // Load tracked pages with basic metrics.
    $pages = $this->getTrackedPages($tenantId);

    // Load summary metrics for the last 30 days.
    $summary = $this->getSummaryMetrics($tenantId, 30);

    return [
      '#theme' => 'heatmap_analytics_dashboard',
      '#pages' => $pages,
      '#summary' => $summary,
      '#tenant_id' => $tenantId,
      '#attached' => [
        'library' => ['jaraba_heatmap/heatmap-dashboard'],
        'drupalSettings' => [
          'jarabaHeatmap' => [
            'tenantId' => $tenantId,
            'apiBase' => '/api/v1/heatmap', // AUDIT-CONS-N07: Added API versioning prefix.
          ],
        ],
      ],
      '#cache' => [
        'contexts' => ['user'],
        'tags' => ['heatmap_data'],
        'max-age' => 300,
      ],
    ];
  }

  /**
   * Returns the dashboard page title.
   *
   * @return string
   *   Translated title.
   */
  public function getTitle(): string {
    return (string) $this->t('Heatmap Analytics');
  }

  /**
   * Gets tracked pages with basic metrics.
   *
   * @param int $tenantId
   *   Tenant ID.
   *
   * @return array
   *   Array of page data.
   */
  protected function getTrackedPages(int $tenantId): array {
    $query = $this->database->select('heatmap_aggregated', 'ha');
    $query->fields('ha', ['page_path']);
    $query->addExpression('SUM(ha.event_count)', 'total_events');
    $query->addExpression('SUM(ha.unique_sessions)', 'total_sessions');
    $query->addExpression('MAX(ha.date)', 'last_activity');
    $query->condition('ha.tenant_id', $tenantId);
    $query->groupBy('ha.page_path');
    $query->orderBy('total_events', 'DESC');
    $query->range(0, 50);

    $pages = [];
    foreach ($query->execute()->fetchAll() as $row) {
      $pages[] = [
        'path' => $row->page_path,
        'events' => (int) $row->total_events,
        'sessions' => (int) $row->total_sessions,
        'last_activity' => $row->last_activity,
      ];
    }

    return $pages;
  }

  /**
   * Gets summary metrics for the dashboard cards.
   *
   * @param int $tenantId
   *   Tenant ID.
   * @param int $days
   *   Number of days to aggregate.
   *
   * @return array
   *   Summary metrics.
   */
  protected function getSummaryMetrics(int $tenantId, int $days): array {
    $from_date = date('Y-m-d', strtotime("-{$days} days"));

    // Total events.
    $query = $this->database->select('heatmap_aggregated', 'ha');
    $query->addExpression('SUM(ha.event_count)', 'total');
    $query->condition('ha.tenant_id', $tenantId);
    $query->condition('ha.date', $from_date, '>=');
    $totalEvents = (int) $query->execute()->fetchField();

    // Total pages tracked.
    $query2 = $this->database->select('heatmap_aggregated', 'ha');
    $query2->addExpression('COUNT(DISTINCT ha.page_path)', 'total');
    $query2->condition('ha.tenant_id', $tenantId);
    $query2->condition('ha.date', $from_date, '>=');
    $totalPages = (int) $query2->execute()->fetchField();

    // Total sessions.
    $query3 = $this->database->select('heatmap_aggregated', 'ha');
    $query3->addExpression('SUM(ha.unique_sessions)', 'total');
    $query3->condition('ha.tenant_id', $tenantId);
    $query3->condition('ha.date', $from_date, '>=');
    $query3->condition('ha.event_type', 'click');
    $totalSessions = (int) $query3->execute()->fetchField();

    // Average scroll depth.
    $query4 = $this->database->select('heatmap_scroll_depth', 'hsd');
    $query4->addExpression('AVG(hsd.avg_max_depth)', 'avg');
    $query4->condition('hsd.tenant_id', $tenantId);
    $query4->condition('hsd.date', $from_date, '>=');
    $avgScrollDepth = round((float) $query4->execute()->fetchField(), 1);

    return [
      'total_events' => $totalEvents,
      'tracked_pages' => $totalPages,
      'unique_sessions' => $totalSessions,
      'avg_scroll_depth' => $avgScrollDepth,
      'period_days' => $days,
    ];
  }

  /**
   * Gets the tenant ID from server-side context.
   *
   * AUDIT-SEC-N06: Server-side tenant resolution, prevents IDOR.
   * Never uses client-supplied tenant_id.
   *
   * @return int
   *   Tenant ID or 0.
   */
  protected function getTenantId(): int {
    return (int) ($this->tenantContext->getCurrentTenantId() ?? 0);
  }

}
