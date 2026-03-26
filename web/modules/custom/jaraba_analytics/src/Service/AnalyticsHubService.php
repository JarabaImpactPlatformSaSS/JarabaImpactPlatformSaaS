<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio del Analytics Hub.
 *
 * Agrega datos de analytics_event, ab_experiment y proactive_insight
 * para renderizar el dashboard unificado sin dependencias externas (GA4).
 *
 * TENANT-001: Todas las queries filtran por tenant_id.
 */
class AnalyticsHubService {

  /**
   * Conexion a base de datos.
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
   * Obtiene los KPIs principales del tenant.
   *
   * @param int $tenantId
   *   ID del tenant (0 = plataforma global).
   * @param int $days
   *   Numero de dias hacia atras para calcular.
   *
   * @return array
   *   Array con claves: total_visits, unique_visitors, avg_session_duration,
   *   bounce_rate, top_cta, conversion_rate.
   */
  public function getKpis(int $tenantId, int $days = 30): array {
    $defaults = [
      'total_visits' => 0,
      'unique_visitors' => 0,
      'avg_session_duration' => 0,
      'bounce_rate' => 0.0,
      'top_cta' => '',
      'conversion_rate' => 0.0,
    ];

    try {
      $since = \Drupal::time()->getRequestTime() - ($days * 86400);

      // Total visitas (page_view events).
      $totalVisits = (int) $this->database->select('analytics_event', 'ae')
        ->condition('ae.event_type', 'page_view')
        ->condition('ae.created', $since, '>=')
        ->condition('ae.tenant_id', $tenantId)
        ->countQuery()
        ->execute()
        ->fetchField();

      // Visitantes unicos (DISTINCT visitor_id).
      $uniqueQuery = $this->database->select('analytics_event', 'ae');
      $uniqueQuery->addExpression('COUNT(DISTINCT ae.visitor_id)', 'unique_count');
      $uniqueQuery->condition('ae.event_type', 'page_view');
      $uniqueQuery->condition('ae.created', $since, '>=');
      $uniqueQuery->condition('ae.tenant_id', $tenantId);
      $uniqueVisitors = (int) $uniqueQuery->execute()->fetchField();

      // Duracion media de sesion: promedio de time_on_page en event_data.
      // event_data es tipo 'map' (serializado), asi que calculamos via
      // diferencia entre primer y ultimo evento por session_id.
      $durationQuery = $this->database->query(
        "SELECT AVG(session_duration) AS avg_dur FROM (
          SELECT ae.session_id, (MAX(ae.created) - MIN(ae.created)) AS session_duration
          FROM {analytics_event} ae
          WHERE ae.event_type = :type
            AND ae.created >= :since
            AND ae.tenant_id = :tid
          GROUP BY ae.session_id
          HAVING COUNT(*) > 1
        ) sub",
        [
          ':type' => 'page_view',
          ':since' => $since,
          ':tid' => $tenantId,
        ]
      );
      $avgDuration = (int) ($durationQuery->fetchField() ?: 0);

      // Bounce rate: sesiones con 1 solo page_view / total sesiones * 100.
      $totalSessionsQuery = $this->database->select('analytics_event', 'ae');
      $totalSessionsQuery->addExpression('COUNT(DISTINCT ae.session_id)', 'total_sessions');
      $totalSessionsQuery->condition('ae.event_type', 'page_view');
      $totalSessionsQuery->condition('ae.created', $since, '>=');
      $totalSessionsQuery->condition('ae.tenant_id', $tenantId);
      $totalSessions = (int) $totalSessionsQuery->execute()->fetchField();

      $bounceRate = 0.0;
      if ($totalSessions > 0) {
        $bounceQuery = $this->database->query(
          "SELECT COUNT(*) AS bounce_count FROM (
            SELECT ae.session_id, COUNT(*) AS page_count
            FROM {analytics_event} ae
            WHERE ae.event_type = :type
              AND ae.created >= :since
              AND ae.tenant_id = :tid
            GROUP BY ae.session_id
            HAVING COUNT(*) = 1
          ) sub",
          [
            ':type' => 'page_view',
            ':since' => $since,
            ':tid' => $tenantId,
          ]
        );
        $bounceCount = (int) $bounceQuery->fetchField();
        $bounceRate = round(($bounceCount / $totalSessions) * 100, 1);
      }

      // Top CTA: el CTA mas clicado.
      $ctaQuery = $this->database->select('analytics_event', 'ae');
      $ctaQuery->addField('ae', 'event_data');
      $ctaQuery->addExpression('COUNT(*)', 'click_count');
      $ctaQuery->condition('ae.event_type', 'cta_click');
      $ctaQuery->condition('ae.created', $since, '>=');
      $ctaQuery->condition('ae.tenant_id', $tenantId);
      $ctaQuery->groupBy('ae.event_data');
      $ctaQuery->orderBy('click_count', 'DESC');
      $ctaQuery->range(0, 1);
      $topCtaRow = $ctaQuery->execute()->fetchObject();

      $topCta = '';
      if ($topCtaRow !== FALSE && $topCtaRow !== NULL) {
        $ctaData = @unserialize($topCtaRow->event_data, ['allowed_classes' => FALSE]);
        if (is_array($ctaData) && isset($ctaData['cta_id'])) {
          $topCta = (string) $ctaData['cta_id'];
        }
      }

      // Conversion rate: form_submit / page_view * 100.
      $formSubmits = (int) $this->database->select('analytics_event', 'ae')
        ->condition('ae.event_type', 'form_submit')
        ->condition('ae.created', $since, '>=')
        ->condition('ae.tenant_id', $tenantId)
        ->countQuery()
        ->execute()
        ->fetchField();

      $conversionRate = 0.0;
      if ($totalVisits > 0) {
        $conversionRate = round(($formSubmits / $totalVisits) * 100, 2);
      }

      return [
        'total_visits' => $totalVisits,
        'unique_visitors' => $uniqueVisitors,
        'avg_session_duration' => $avgDuration,
        'bounce_rate' => $bounceRate,
        'top_cta' => $topCta,
        'conversion_rate' => $conversionRate,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('AnalyticsHub getKpis error: @msg', ['@msg' => $e->getMessage()]);
      return $defaults;
    }
  }

  /**
   * Obtiene la tendencia de trafico de los ultimos N dias.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param int $days
   *   Numero de dias.
   *
   * @return array
   *   Array de arrays con claves: date, visits, unique.
   */
  public function getTrafficTrend(int $tenantId, int $days = 30): array {
    try {
      $since = \Drupal::time()->getRequestTime() - ($days * 86400);

      $query = $this->database->query(
        "SELECT
          DATE(FROM_UNIXTIME(ae.created)) AS event_date,
          COUNT(*) AS visits,
          COUNT(DISTINCT ae.visitor_id) AS unique_visitors
        FROM {analytics_event} ae
        WHERE ae.event_type = :type
          AND ae.created >= :since
          AND ae.tenant_id = :tid
        GROUP BY event_date
        ORDER BY event_date ASC",
        [
          ':type' => 'page_view',
          ':since' => $since,
          ':tid' => $tenantId,
        ]
      );

      $trend = [];
      foreach ($query as $row) {
        $trend[] = [
          'date' => $row->event_date,
          'visits' => (int) $row->visits,
          'unique' => (int) $row->unique_visitors,
        ];
      }

      return $trend;
    }
    catch (\Exception $e) {
      $this->logger->error('AnalyticsHub getTrafficTrend error: @msg', ['@msg' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Obtiene las paginas mas visitadas.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param int $limit
   *   Numero maximo de resultados.
   *
   * @return array
   *   Array de arrays con claves: url, title, views, avg_time.
   */
  public function getTopPages(int $tenantId, int $limit = 10): array {
    try {
      $since = \Drupal::time()->getRequestTime() - (30 * 86400);

      $query = $this->database->query(
        "SELECT
          ae.page_url AS url,
          ae.page_url AS title,
          COUNT(*) AS views,
          COALESCE(
            AVG(
              CASE
                WHEN sub.session_duration > 0 THEN sub.session_duration
                ELSE NULL
              END
            ), 0
          ) AS avg_time
        FROM {analytics_event} ae
        LEFT JOIN (
          SELECT session_id, page_url,
            (MAX(created) - MIN(created)) AS session_duration
          FROM {analytics_event}
          WHERE event_type = :type AND tenant_id = :tid AND created >= :since
          GROUP BY session_id, page_url
        ) sub ON sub.session_id = ae.session_id AND sub.page_url = ae.page_url
        WHERE ae.event_type = :type
          AND ae.tenant_id = :tid
          AND ae.created >= :since
        GROUP BY ae.page_url
        ORDER BY views DESC
        LIMIT :lim",
        [
          ':type' => 'page_view',
          ':tid' => $tenantId,
          ':since' => $since,
          ':lim' => $limit,
        ]
      );

      $pages = [];
      foreach ($query as $row) {
        $pages[] = [
          'url' => (string) $row->url,
          'title' => (string) $row->title,
          'views' => (int) $row->views,
          'avg_time' => (int) $row->avg_time,
        ];
      }

      return $pages;
    }
    catch (\Exception $e) {
      $this->logger->error('AnalyticsHub getTopPages error: @msg', ['@msg' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Obtiene el desglose por tipo de dispositivo.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return array
   *   Array con claves: desktop, mobile, tablet (porcentajes).
   */
  public function getDeviceBreakdown(int $tenantId): array {
    $defaults = ['desktop' => 0, 'mobile' => 0, 'tablet' => 0];

    try {
      $since = \Drupal::time()->getRequestTime() - (30 * 86400);

      $query = $this->database->select('analytics_event', 'ae');
      $query->addField('ae', 'device_type');
      $query->addExpression('COUNT(*)', 'device_count');
      $query->condition('ae.event_type', 'page_view');
      $query->condition('ae.created', $since, '>=');
      $query->condition('ae.tenant_id', $tenantId);
      $query->groupBy('ae.device_type');

      $result = $query->execute();
      $counts = ['desktop' => 0, 'mobile' => 0, 'tablet' => 0];
      $total = 0;

      foreach ($result as $row) {
        $device = strtolower((string) $row->device_type);
        if (isset($counts[$device])) {
          $counts[$device] = (int) $row->device_count;
        }
        $total += (int) $row->device_count;
      }

      if ($total > 0) {
        return [
          'desktop' => round(($counts['desktop'] / $total) * 100, 1),
          'mobile' => round(($counts['mobile'] / $total) * 100, 1),
          'tablet' => round(($counts['tablet'] / $total) * 100, 1),
        ];
      }

      return $defaults;
    }
    catch (\Exception $e) {
      $this->logger->error('AnalyticsHub getDeviceBreakdown error: @msg', ['@msg' => $e->getMessage()]);
      return $defaults;
    }
  }

  /**
   * Obtiene los datos del funnel de conversion.
   *
   * Etapas: page_view -> cta_click -> form_submit -> complete_registration.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return array
   *   Array de arrays con claves: step, name, count, rate.
   */
  public function getFunnelData(int $tenantId): array {
    try {
      $since = \Drupal::time()->getRequestTime() - (30 * 86400);

      $steps = [
        ['event_type' => 'page_view', 'name' => 'Visitas'],
        ['event_type' => 'cta_click', 'name' => 'Clics en CTA'],
        ['event_type' => 'form_submit', 'name' => 'Formularios enviados'],
        ['event_type' => 'complete_registration', 'name' => 'Registros completos'],
      ];

      $funnel = [];
      $firstCount = 0;

      foreach ($steps as $index => $step) {
        $count = (int) $this->database->select('analytics_event', 'ae')
          ->condition('ae.event_type', $step['event_type'])
          ->condition('ae.created', $since, '>=')
          ->condition('ae.tenant_id', $tenantId)
          ->countQuery()
          ->execute()
          ->fetchField();

        if ($index === 0) {
          $firstCount = $count;
        }

        $rate = 0.0;
        if ($firstCount > 0) {
          $rate = round(($count / $firstCount) * 100, 1);
        }

        $funnel[] = [
          'step' => $index + 1,
          'name' => $step['name'],
          'count' => $count,
          'rate' => $rate,
        ];
      }

      return $funnel;
    }
    catch (\Exception $e) {
      $this->logger->error('AnalyticsHub getFunnelData error: @msg', ['@msg' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Obtiene los experimentos A/B activos.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return array
   *   Array de arrays con claves: id, name, status, variants_count, winner.
   */
  public function getActiveExperiments(int $tenantId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('ab_experiment');
      $ids = $storage->getQuery()
        ->condition('status', 'running')
        ->condition('tenant_id', $tenantId)
        ->accessCheck(TRUE)
        ->range(0, 10)
        ->execute();

      if ($ids === []) {
        return [];
      }

      $experiments = $storage->loadMultiple($ids);
      $result = [];

      foreach ($experiments as $experiment) {
        $result[] = [
          'id' => (int) $experiment->id(),
          'name' => (string) ($experiment->label() ?? ''),
          'status' => 'running',
          'variants_count' => $experiment->hasField('variants_count')
            ? (int) $experiment->get('variants_count')->value
            : 0,
          'winner' => $experiment->hasField('winner')
            ? (string) ($experiment->get('winner')->value ?? '')
            : '',
        ];
      }

      return $result;
    }
    catch (\Exception $e) {
      $this->logger->error('AnalyticsHub getActiveExperiments error: @msg', ['@msg' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Obtiene los insights proactivos mas recientes.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param int $limit
   *   Numero maximo de resultados.
   *
   * @return array
   *   Array de arrays con claves: type, title, severity, created.
   */
  public function getRecentInsights(int $tenantId, int $limit = 5): array {
    try {
      $storage = $this->entityTypeManager->getStorage('proactive_insight');
      $ids = $storage->getQuery()
        ->condition('tenant_id', $tenantId)
        ->accessCheck(TRUE)
        ->sort('created', 'DESC')
        ->range(0, $limit)
        ->execute();

      if ($ids === []) {
        return [];
      }

      $insights = $storage->loadMultiple($ids);
      $result = [];

      foreach ($insights as $insight) {
        $result[] = [
          'type' => $insight->hasField('insight_type')
            ? (string) ($insight->get('insight_type')->value ?? 'info')
            : 'info',
          'title' => (string) ($insight->label() ?? ''),
          'severity' => $insight->hasField('severity')
            ? (string) ($insight->get('severity')->value ?? 'low')
            : 'low',
          'created' => $insight->hasField('created')
            ? (int) $insight->get('created')->value
            : 0,
        ];
      }

      return $result;
    }
    catch (\Exception $e) {
      $this->logger->error('AnalyticsHub getRecentInsights error: @msg', ['@msg' => $e->getMessage()]);
      return [];
    }
  }

}
