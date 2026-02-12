<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de consultas de datos de analytics.
 *
 * PROPOSITO:
 * Proporciona un motor de consultas flexible para widgets de dashboard,
 * incluyendo ejecucion de queries configurables, metricas disponibles,
 * dimensiones y series temporales.
 *
 * LOGICA:
 * - executeQuery: ejecuta una consulta definida por queryConfig contra
 *   analytics_event con aislamiento multi-tenant.
 * - getAvailableMetrics: devuelve la lista de metricas consultables.
 * - getAvailableDimensions: devuelve las dimensiones de agrupacion disponibles.
 * - getTimeSeries: genera series temporales para una metrica y periodo.
 */
class AnalyticsDataService {

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Constructor.
   */
  public function __construct(
    Connection $database,
    EntityTypeManagerInterface $entity_type_manager,
    LoggerInterface $logger,
  ) {
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
  }

  /**
   * Executes a query based on configuration.
   *
   * @param array $queryConfig
   *   Query configuration with keys:
   *   - metric (string): The metric to calculate (e.g. page_views, sessions).
   *   - dimensions (array): Grouping dimensions (e.g. ['date', 'device_type']).
   *   - filters (array): Filter conditions (e.g. ['country' => 'ES']).
   *   - date_range (string): Date range key (e.g. 'last_7_days', 'last_30_days').
   *   - limit (int): Maximum number of results.
   * @param int|null $tenantId
   *   Optional tenant ID for multi-tenant isolation.
   *
   * @return array
   *   Query results as an array of associative arrays.
   */
  public function executeQuery(array $queryConfig, ?int $tenantId = NULL): array {
    try {
      $metric = $queryConfig['metric'] ?? 'page_views';
      $dimensions = $queryConfig['dimensions'] ?? [];
      $filters = $queryConfig['filters'] ?? [];
      $dateRange = $queryConfig['date_range'] ?? 'last_30_days';
      $limit = $queryConfig['limit'] ?? 100;

      $query = $this->database->select('analytics_event', 'ae');

      // Add metric aggregation.
      $this->addMetricExpression($query, $metric);

      // Add dimension groupings.
      foreach ($dimensions as $dimension) {
        $this->addDimensionField($query, $dimension);
      }

      // Apply date range filter.
      $dateConditions = $this->getDateRangeConditions($dateRange);
      if ($dateConditions) {
        $query->condition('ae.created', $dateConditions['start'], '>=');
        $query->condition('ae.created', $dateConditions['end'], '<=');
      }

      // Apply tenant filter.
      if ($tenantId !== NULL) {
        $query->condition('ae.tenant_id', $tenantId);
      }

      // Apply additional filters.
      foreach ($filters as $field => $value) {
        $allowedFields = ['event_type', 'device_type', 'country', 'utm_source', 'utm_campaign'];
        if (in_array($field, $allowedFields, TRUE)) {
          $query->condition('ae.' . $field, $value);
        }
      }

      $query->range(0, (int) $limit);

      $results = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
      return array_values($results);
    }
    catch (\Exception $e) {
      $this->logger->error('Query execution failed: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Gets all available metrics.
   *
   * @return array
   *   Associative array of metric_key => [label, description, type].
   */
  public function getAvailableMetrics(): array {
    return [
      'page_views' => [
        'label' => 'Page Views',
        'description' => 'Total number of page view events.',
        'type' => 'count',
      ],
      'unique_visitors' => [
        'label' => 'Unique Visitors',
        'description' => 'Count of distinct visitor identifiers.',
        'type' => 'count_distinct',
      ],
      'sessions' => [
        'label' => 'Sessions',
        'description' => 'Total number of user sessions.',
        'type' => 'count_distinct',
      ],
      'events' => [
        'label' => 'Total Events',
        'description' => 'Total count of all tracked events.',
        'type' => 'count',
      ],
      'conversions' => [
        'label' => 'Conversions',
        'description' => 'Number of purchase/conversion events.',
        'type' => 'count',
      ],
      'bounce_rate' => [
        'label' => 'Bounce Rate',
        'description' => 'Percentage of single-page sessions.',
        'type' => 'percentage',
      ],
      'avg_session_duration' => [
        'label' => 'Avg. Session Duration',
        'description' => 'Average duration of user sessions in seconds.',
        'type' => 'average',
      ],
    ];
  }

  /**
   * Gets all available dimensions.
   *
   * @return array
   *   Associative array of dimension_key => [label, description].
   */
  public function getAvailableDimensions(): array {
    return [
      'date' => [
        'label' => 'Date',
        'description' => 'Group by calendar date.',
      ],
      'event_type' => [
        'label' => 'Event Type',
        'description' => 'Group by event type (page_view, purchase, etc).',
      ],
      'device_type' => [
        'label' => 'Device Type',
        'description' => 'Group by device type (desktop, mobile, tablet).',
      ],
      'country' => [
        'label' => 'Country',
        'description' => 'Group by visitor country.',
      ],
      'utm_source' => [
        'label' => 'UTM Source',
        'description' => 'Group by traffic source (utm_source).',
      ],
      'utm_campaign' => [
        'label' => 'UTM Campaign',
        'description' => 'Group by marketing campaign (utm_campaign).',
      ],
      'page_path' => [
        'label' => 'Page Path',
        'description' => 'Group by page URL path.',
      ],
    ];
  }

  /**
   * Gets time series data for a metric.
   *
   * @param string $metric
   *   The metric key (e.g. 'page_views', 'unique_visitors').
   * @param string $period
   *   The time period ('hourly', 'daily', 'weekly', 'monthly').
   * @param int|null $tenantId
   *   Optional tenant ID for multi-tenant isolation.
   *
   * @return array
   *   Array of [timestamp, value] data points.
   */
  public function getTimeSeries(string $metric, string $period, ?int $tenantId = NULL): array {
    try {
      $query = $this->database->select('analytics_event', 'ae');

      // Add metric expression.
      $this->addMetricExpression($query, $metric);

      // Add time dimension based on period.
      $dateExpression = match ($period) {
        'hourly' => "FROM_UNIXTIME(ae.created, '%Y-%m-%d %H:00:00')",
        'daily' => "FROM_UNIXTIME(ae.created, '%Y-%m-%d')",
        'weekly' => "FROM_UNIXTIME(ae.created, '%Y-%u')",
        'monthly' => "FROM_UNIXTIME(ae.created, '%Y-%m')",
        default => "FROM_UNIXTIME(ae.created, '%Y-%m-%d')",
      };

      $query->addExpression($dateExpression, 'time_bucket');
      $query->groupBy('time_bucket');
      $query->orderBy('time_bucket', 'ASC');

      // Apply tenant filter.
      if ($tenantId !== NULL) {
        $query->condition('ae.tenant_id', $tenantId);
      }

      // Default to last 30 days.
      $query->condition('ae.created', time() - (30 * 86400), '>=');

      $results = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);

      $timeSeries = [];
      foreach ($results as $row) {
        $timeSeries[] = [
          'period' => $row['time_bucket'] ?? '',
          'value' => (float) ($row['metric_value'] ?? 0),
        ];
      }

      return $timeSeries;
    }
    catch (\Exception $e) {
      $this->logger->error('Time series query failed for @metric/@period: @message', [
        '@metric' => $metric,
        '@period' => $period,
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Adds a metric expression to the query.
   *
   * @param \Drupal\Core\Database\Query\Select $query
   *   The select query.
   * @param string $metric
   *   The metric key.
   */
  protected function addMetricExpression($query, string $metric): void {
    match ($metric) {
      'page_views' => $query->addExpression("COUNT(CASE WHEN ae.event_type = 'page_view' THEN 1 END)", 'metric_value'),
      'unique_visitors' => $query->addExpression('COUNT(DISTINCT ae.visitor_id)', 'metric_value'),
      'sessions' => $query->addExpression('COUNT(DISTINCT ae.session_id)', 'metric_value'),
      'conversions' => $query->addExpression("COUNT(CASE WHEN ae.event_type = 'purchase' THEN 1 END)", 'metric_value'),
      default => $query->addExpression('COUNT(*)', 'metric_value'),
    };
  }

  /**
   * Adds a dimension field and groupBy to the query.
   *
   * @param \Drupal\Core\Database\Query\Select $query
   *   The select query.
   * @param string $dimension
   *   The dimension key.
   */
  protected function addDimensionField($query, string $dimension): void {
    $fieldMap = [
      'date' => "FROM_UNIXTIME(ae.created, '%Y-%m-%d')",
      'event_type' => 'ae.event_type',
      'device_type' => 'ae.device_type',
      'country' => 'ae.country',
      'utm_source' => 'ae.utm_source',
      'utm_campaign' => 'ae.utm_campaign',
      'page_path' => 'ae.page_path',
    ];

    if (isset($fieldMap[$dimension])) {
      $expression = $fieldMap[$dimension];
      if (str_contains($expression, '(')) {
        $query->addExpression($expression, 'dim_' . $dimension);
      }
      else {
        $query->addField('ae', str_replace('ae.', '', $expression), 'dim_' . $dimension);
      }
      $query->groupBy('dim_' . $dimension);
    }
  }

  /**
   * Gets date range start/end timestamps from a named range.
   *
   * @param string $dateRange
   *   Named date range (e.g. 'last_7_days', 'last_30_days', 'last_90_days').
   *
   * @return array|null
   *   Array with 'start' and 'end' timestamps, or NULL if invalid.
   */
  protected function getDateRangeConditions(string $dateRange): ?array {
    $now = time();

    return match ($dateRange) {
      'today' => ['start' => strtotime('today midnight'), 'end' => $now],
      'yesterday' => ['start' => strtotime('yesterday midnight'), 'end' => strtotime('today midnight') - 1],
      'last_7_days' => ['start' => $now - (7 * 86400), 'end' => $now],
      'last_30_days' => ['start' => $now - (30 * 86400), 'end' => $now],
      'last_90_days' => ['start' => $now - (90 * 86400), 'end' => $now],
      'last_year' => ['start' => $now - (365 * 86400), 'end' => $now],
      default => ['start' => $now - (30 * 86400), 'end' => $now],
    };
  }

}
