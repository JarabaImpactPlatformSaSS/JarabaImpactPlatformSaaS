<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Service;

use Drupal\Core\Database\Connection;
use Drupal\jaraba_ai_agents\Service\ProactiveInsightsService;
use Psr\Log\LoggerInterface;

/**
 * Analiza datos de analytics_event para detectar anomalias de conversion.
 *
 * Genera insights proactivos comparando periodos, identificando
 * bottlenecks en el funnel y produciendo reportes de conversion
 * estructurados para consumo por agentes IA y dashboards.
 *
 * PRESAVE-RESILIENCE-001: ProactiveInsightsService es opcional (@?).
 * TENANT-001: TODA query filtra por tenant_id.
 */
class ConversionInsightsService {

  /**
   * Umbral de caida de trafico para generar anomalia (%).
   */
  private const TRAFFIC_DROP_THRESHOLD = 20.0;

  /**
   * Umbral de caida de tasa de conversion (%).
   */
  private const CONVERSION_DROP_THRESHOLD = 15.0;

  /**
   * Umbral de incremento de bounce rate (%).
   */
  private const BOUNCE_SPIKE_THRESHOLD = 25.0;

  /**
   * Umbral de spike de fuente de trafico (%).
   */
  private const TRAFFIC_SOURCE_SPIKE_THRESHOLD = 50.0;

  /**
   * Pasos del funnel de conversion en orden.
   */
  private const FUNNEL_STEPS = [
    'page_view',
    'cta_click',
    'form_start',
    'form_submit',
    'confirmation',
  ];

  /**
   * Constructs a ConversionInsightsService.
   *
   * OPTIONAL-PARAM-ORDER-001: Parametro opcional al final.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   * @param \Drupal\jaraba_ai_agents\Service\ProactiveInsightsService|null $proactiveInsights
   *   Optional proactive insights service from jaraba_ai_agents.
   */
  public function __construct(
    protected readonly Connection $database,
    protected readonly LoggerInterface $logger,
    protected readonly ?ProactiveInsightsService $proactiveInsights = NULL,
  ) {}

  /**
   * Detecta anomalias comparando ultimos 7 dias vs 7 dias anteriores.
   *
   * @param int $tenantId
   *   El ID del tenant a analizar.
   *
   * @return array
   *   Array de anomalias, cada una con: type, severity, current_value,
   *   previous_value, change_pct, description.
   */
  public function detectAnomalies(int $tenantId): array {
    $anomalies = [];
    $now = \Drupal::time()->getRequestTime();
    $sevenDaysAgo = $now - (7 * 86400);
    $fourteenDaysAgo = $now - (14 * 86400);

    try {
      // Trafico: comparar page_view count.
      $currentTraffic = $this->countEvents($tenantId, 'page_view', $sevenDaysAgo, $now);
      $previousTraffic = $this->countEvents($tenantId, 'page_view', $fourteenDaysAgo, $sevenDaysAgo);

      if ($previousTraffic > 0) {
        $trafficChange = (($currentTraffic - $previousTraffic) / $previousTraffic) * 100;
        if ($trafficChange <= -self::TRAFFIC_DROP_THRESHOLD) {
          $anomalies[] = [
            'type' => 'traffic_drop',
            'severity' => abs($trafficChange) >= 40 ? 'critical' : 'warning',
            'current_value' => $currentTraffic,
            'previous_value' => $previousTraffic,
            'change_pct' => round($trafficChange, 2),
            'description' => sprintf(
              'El trafico ha caido un %.1f%% en los ultimos 7 dias (%d vs %d visitas).',
              abs($trafficChange),
              $currentTraffic,
              $previousTraffic
            ),
          ];
        }
      }

      // Tasa de conversion: form_submit / page_view.
      $currentSubmits = $this->countEvents($tenantId, 'form_submit', $sevenDaysAgo, $now);
      $previousSubmits = $this->countEvents($tenantId, 'form_submit', $fourteenDaysAgo, $sevenDaysAgo);

      $currentRate = $currentTraffic > 0 ? ($currentSubmits / $currentTraffic) * 100 : 0;
      $previousRate = $previousTraffic > 0 ? ($previousSubmits / $previousTraffic) * 100 : 0;

      if ($previousRate > 0) {
        $conversionChange = (($currentRate - $previousRate) / $previousRate) * 100;
        if ($conversionChange <= -self::CONVERSION_DROP_THRESHOLD) {
          $anomalies[] = [
            'type' => 'conversion_drop',
            'severity' => abs($conversionChange) >= 30 ? 'critical' : 'warning',
            'current_value' => round($currentRate, 2),
            'previous_value' => round($previousRate, 2),
            'change_pct' => round($conversionChange, 2),
            'description' => sprintf(
              'La tasa de conversion ha caido un %.1f%% (de %.2f%% a %.2f%%).',
              abs($conversionChange),
              $previousRate,
              $currentRate
            ),
          ];
        }
      }

      // Bounce rate: sesiones con solo 1 page_view / total sesiones.
      $currentBounce = $this->calculateBounceRate($tenantId, $sevenDaysAgo, $now);
      $previousBounce = $this->calculateBounceRate($tenantId, $fourteenDaysAgo, $sevenDaysAgo);

      if ($previousBounce > 0) {
        $bounceChange = (($currentBounce - $previousBounce) / $previousBounce) * 100;
        if ($bounceChange >= self::BOUNCE_SPIKE_THRESHOLD) {
          $anomalies[] = [
            'type' => 'bounce_spike',
            'severity' => $bounceChange >= 50 ? 'critical' : 'warning',
            'current_value' => round($currentBounce, 2),
            'previous_value' => round($previousBounce, 2),
            'change_pct' => round($bounceChange, 2),
            'description' => sprintf(
              'El bounce rate ha subido un %.1f%% (de %.1f%% a %.1f%%).',
              $bounceChange,
              $previousBounce,
              $currentBounce
            ),
          ];
        }
      }

      // Spike de fuente de trafico nueva.
      $sourceSpike = $this->detectTrafficSourceSpike($tenantId, $sevenDaysAgo, $now, $fourteenDaysAgo);
      if ($sourceSpike !== NULL) {
        $anomalies[] = $sourceSpike;
      }
    }
    catch (\Throwable $e) {
      $this->logger->error('Error detectando anomalias para tenant @tid: @msg', [
        '@tid' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
    }

    return $anomalies;
  }

  /**
   * Genera un reporte de conversion estructurado.
   *
   * @param int $tenantId
   *   El ID del tenant.
   * @param int $days
   *   Numero de dias a analizar (default 30).
   *
   * @return array
   *   Reporte con: overview, trends, top_converting_pages,
   *   worst_converting_pages, cta_performance, recommendations.
   */
  public function generateConversionReport(int $tenantId, int $days = 30): array {
    $now = \Drupal::time()->getRequestTime();
    $startTime = $now - ($days * 86400);

    $report = [
      'tenant_id' => $tenantId,
      'period_days' => $days,
      'generated_at' => date('Y-m-d\TH:i:s', $now),
      'overview' => [],
      'trends' => [],
      'top_converting_pages' => [],
      'worst_converting_pages' => [],
      'cta_performance' => [],
      'recommendations' => [],
    ];

    try {
      // Overview.
      $visits = $this->countEvents($tenantId, 'page_view', $startTime, $now);
      $conversions = $this->countEvents($tenantId, 'form_submit', $startTime, $now);
      $rate = $visits > 0 ? round(($conversions / $visits) * 100, 2) : 0;
      $avgTime = $this->calculateAvgSessionTime($tenantId, $startTime, $now);

      $report['overview'] = [
        'visits' => $visits,
        'conversions' => $conversions,
        'rate' => $rate,
        'avg_time_seconds' => $avgTime,
      ];

      // Trends: tasa de conversion diaria.
      $report['trends'] = $this->getDailyConversionTrends($tenantId, $startTime, $now);

      // Top converting pages: mayor ratio form_submit/page_view.
      $report['top_converting_pages'] = $this->getPageConversionRanking(
        $tenantId,
        $startTime,
        $now,
        'DESC',
        10
      );

      // Worst converting pages: menor ratio pero con trafico alto.
      $report['worst_converting_pages'] = $this->getPageConversionRanking(
        $tenantId,
        $startTime,
        $now,
        'ASC',
        10,
        50
      );

      // CTA performance.
      $report['cta_performance'] = $this->getCtaPerformance($tenantId, $startTime, $now);

      // Recommendations: IA si disponible, sino rule-based.
      $report['recommendations'] = $this->generateRecommendations($tenantId, $report);
    }
    catch (\Throwable $e) {
      $this->logger->error('Error generando reporte de conversion para tenant @tid: @msg', [
        '@tid' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
    }

    return $report;
  }

  /**
   * Identifica bottlenecks en el funnel de conversion.
   *
   * Funnel: page_view -> cta_click -> form_start -> form_submit -> confirmation.
   *
   * @param int $tenantId
   *   El ID del tenant.
   *
   * @return array
   *   Array de pasos con: step, dropoff_rate, absolute_loss, recommendation.
   */
  public function getFunnelBottlenecks(int $tenantId): array {
    $bottlenecks = [];
    $now = \Drupal::time()->getRequestTime();
    $startTime = $now - (30 * 86400);

    try {
      $stepCounts = [];
      foreach (self::FUNNEL_STEPS as $step) {
        $stepCounts[$step] = $this->countEvents($tenantId, $step, $startTime, $now);
      }

      for ($i = 0; $i < count(self::FUNNEL_STEPS) - 1; $i++) {
        $currentStep = self::FUNNEL_STEPS[$i];
        $nextStep = self::FUNNEL_STEPS[$i + 1];
        $currentCount = $stepCounts[$currentStep];
        $nextCount = $stepCounts[$nextStep];

        $dropoffRate = $currentCount > 0
          ? round((1 - ($nextCount / $currentCount)) * 100, 2)
          : 0;
        $absoluteLoss = $currentCount - $nextCount;

        $bottlenecks[] = [
          'step_from' => $currentStep,
          'step_to' => $nextStep,
          'count_from' => $currentCount,
          'count_to' => $nextCount,
          'dropoff_rate' => $dropoffRate,
          'absolute_loss' => $absoluteLoss,
          'recommendation' => $this->getStepRecommendation($currentStep, $nextStep, $dropoffRate),
        ];
      }
    }
    catch (\Throwable $e) {
      $this->logger->error('Error analizando funnel para tenant @tid: @msg', [
        '@tid' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
    }

    return $bottlenecks;
  }

  /**
   * Evalua anomalias para todos los tenants activos (llamado desde cron).
   *
   * @return int
   *   Numero de insights creados.
   */
  public function evaluarAnomalias(): int {
    $insightsCreated = 0;

    try {
      $tenantIds = $this->getActiveTenantIds();

      foreach ($tenantIds as $tenantId) {
        try {
          $anomalies = $this->detectAnomalies((int) $tenantId);

          foreach ($anomalies as $anomaly) {
            if ($this->proactiveInsights !== NULL) {
              try {
                $this->proactiveInsights->createInsight([
                  'tenant_id' => $tenantId,
                  'type' => 'conversion_anomaly',
                  'subtype' => $anomaly['type'],
                  'severity' => $anomaly['severity'],
                  'data' => $anomaly,
                  'message' => $anomaly['description'],
                ]);
                $insightsCreated++;
              }
              catch (\Throwable $e) {
                $this->logger->warning('No se pudo crear insight para tenant @tid: @msg', [
                  '@tid' => $tenantId,
                  '@msg' => $e->getMessage(),
                ]);
              }
            }
            else {
              // Sin ProactiveInsightsService, solo logueamos.
              $this->logger->notice('Anomalia detectada para tenant @tid: @type — @desc', [
                '@tid' => $tenantId,
                '@type' => $anomaly['type'],
                '@desc' => $anomaly['description'],
              ]);
              $insightsCreated++;
            }
          }
        }
        catch (\Throwable $e) {
          $this->logger->error('Error evaluando anomalias para tenant @tid: @msg', [
            '@tid' => $tenantId,
            '@msg' => $e->getMessage(),
          ]);
        }
      }
    }
    catch (\Throwable $e) {
      $this->logger->error('Error en evaluarAnomalias: @msg', [
        '@msg' => $e->getMessage(),
      ]);
    }

    $this->logger->info('evaluarAnomalias completado: @count insights creados.', [
      '@count' => $insightsCreated,
    ]);

    return $insightsCreated;
  }

  /**
   * Cuenta eventos de un tipo en un rango de tiempo para un tenant.
   *
   * @param int $tenantId
   *   El tenant ID.
   * @param string $eventType
   *   El tipo de evento.
   * @param int $from
   *   Timestamp inicio (unix).
   * @param int $to
   *   Timestamp fin (unix).
   *
   * @return int
   *   Numero de eventos.
   */
  protected function countEvents(int $tenantId, string $eventType, int $from, int $to): int {
    // DB API directo para rendimiento (tabla analytics_event con campo created = INT).
    $result = $this->database->select('analytics_event', 'ae')
      ->condition('ae.tenant_id', $tenantId)
      ->condition('ae.event_type', $eventType)
      ->condition('ae.created', $from, '>=')
      ->condition('ae.created', $to, '<')
      ->countQuery()
      ->execute();

    return $result ? (int) $result->fetchField() : 0;
  }

  /**
   * Calcula bounce rate: porcentaje de sesiones con solo 1 page_view.
   *
   * @param int $tenantId
   *   El tenant ID.
   * @param int $from
   *   Timestamp inicio.
   * @param int $to
   *   Timestamp fin.
   *
   * @return float
   *   Bounce rate en porcentaje.
   */
  protected function calculateBounceRate(int $tenantId, int $from, int $to): float {
    // Total de sesiones unicas.
    $totalSessions = $this->database->select('analytics_event', 'ae')
      ->condition('ae.tenant_id', $tenantId)
      ->condition('ae.event_type', 'page_view')
      ->condition('ae.created', $from, '>=')
      ->condition('ae.created', $to, '<');
    $totalSessions->addExpression('COUNT(DISTINCT ae.session_id)', 'total');
    $totalResult = $totalSessions->execute();
    $total = $totalResult ? (int) $totalResult->fetchField() : 0;

    if ($total === 0) {
      return 0;
    }

    // Sesiones con exactamente 1 page_view (bounce).
    $bounceQuery = $this->database->query(
      "SELECT COUNT(*) AS bounce_count FROM (
        SELECT session_id, COUNT(*) AS pv_count
        FROM {analytics_event}
        WHERE tenant_id = :tid
          AND event_type = 'page_view'
          AND created >= :from_ts
          AND created < :to_ts
        GROUP BY session_id
        HAVING pv_count = 1
      ) AS bounced",
      [
        ':tid' => $tenantId,
        ':from_ts' => $from,
        ':to_ts' => $to,
      ]
    );
    $bounceCount = $bounceQuery ? (int) $bounceQuery->fetchField() : 0;

    return ($bounceCount / $total) * 100;
  }

  /**
   * Detecta spikes en fuentes de trafico nuevas o inusuales.
   *
   * @param int $tenantId
   *   El tenant ID.
   * @param int $currentFrom
   *   Inicio periodo actual.
   * @param int $currentTo
   *   Fin periodo actual.
   * @param int $previousFrom
   *   Inicio periodo anterior.
   *
   * @return array|null
   *   Anomalia o NULL si no hay spike.
   */
  protected function detectTrafficSourceSpike(int $tenantId, int $currentFrom, int $currentTo, int $previousFrom): ?array {
    // Top fuente actual.
    $currentSources = $this->getTopTrafficSources($tenantId, $currentFrom, $currentTo, 5);
    $previousSources = $this->getTopTrafficSources($tenantId, $previousFrom, $currentFrom, 5);

    $previousMap = [];
    foreach ($previousSources as $source) {
      $previousMap[$source['source']] = (int) $source['count'];
    }

    foreach ($currentSources as $source) {
      $sourceName = $source['source'];
      $currentCount = (int) $source['count'];
      $previousCount = $previousMap[$sourceName] ?? 0;

      if ($previousCount === 0 && $currentCount >= 10) {
        // Fuente completamente nueva con volumen significativo.
        return [
          'type' => 'traffic_source_spike',
          'severity' => 'info',
          'current_value' => $currentCount,
          'previous_value' => 0,
          'change_pct' => 100.0,
          'description' => sprintf(
            'Nueva fuente de trafico detectada: "%s" con %d visitas (no existia en el periodo anterior).',
            $sourceName,
            $currentCount
          ),
        ];
      }
      elseif ($previousCount > 0) {
        $change = (($currentCount - $previousCount) / $previousCount) * 100;
        if ($change >= self::TRAFFIC_SOURCE_SPIKE_THRESHOLD) {
          return [
            'type' => 'traffic_source_spike',
            'severity' => 'info',
            'current_value' => $currentCount,
            'previous_value' => $previousCount,
            'change_pct' => round($change, 2),
            'description' => sprintf(
              'La fuente "%s" ha crecido un %.1f%% (%d vs %d visitas).',
              $sourceName,
              $change,
              $currentCount,
              $previousCount
            ),
          ];
        }
      }
    }

    return NULL;
  }

  /**
   * Obtiene las top fuentes de trafico por referrer/utm_source.
   *
   * @param int $tenantId
   *   El tenant ID.
   * @param int $from
   *   Timestamp inicio.
   * @param int $to
   *   Timestamp fin.
   * @param int $limit
   *   Numero maximo de resultados.
   *
   * @return array
   *   Array de ['source' => string, 'count' => int].
   */
  protected function getTopTrafficSources(int $tenantId, int $from, int $to, int $limit): array {
    $query = $this->database->select('analytics_event', 'ae')
      ->condition('ae.tenant_id', $tenantId)
      ->condition('ae.event_type', 'page_view')
      ->condition('ae.created', $from, '>=')
      ->condition('ae.created', $to, '<');

    // QUERY-CHAIN-001: addExpression y groupBy no se encadenan.
    $query->addExpression("COALESCE(NULLIF(ae.utm_source, ''), COALESCE(NULLIF(ae.referrer, ''), 'direct'))", 'source');
    $query->addExpression('COUNT(*)', 'count');
    $query->groupBy('source');
    $query->orderBy('count', 'DESC');
    $query->range(0, $limit);

    $result = $query->execute();
    return $result ? $result->fetchAll(\PDO::FETCH_ASSOC) : [];
  }

  /**
   * Calcula el tiempo medio de sesion.
   *
   * @param int $tenantId
   *   El tenant ID.
   * @param int $from
   *   Timestamp inicio.
   * @param int $to
   *   Timestamp fin.
   *
   * @return int
   *   Tiempo medio de sesion en segundos.
   */
  protected function calculateAvgSessionTime(int $tenantId, int $from, int $to): int {
    $result = $this->database->query(
      "SELECT AVG(session_duration) AS avg_duration FROM (
        SELECT session_id, (MAX(created) - MIN(created)) AS session_duration
        FROM {analytics_event}
        WHERE tenant_id = :tid
          AND created >= :from_ts
          AND created < :to_ts
        GROUP BY session_id
        HAVING COUNT(*) > 1
      ) AS sessions",
      [
        ':tid' => $tenantId,
        ':from_ts' => $from,
        ':to_ts' => $to,
      ]
    );

    return $result ? (int) round((float) $result->fetchField()) : 0;
  }

  /**
   * Obtiene tendencias diarias de tasa de conversion.
   *
   * @param int $tenantId
   *   El tenant ID.
   * @param int $from
   *   Timestamp inicio.
   * @param int $to
   *   Timestamp fin.
   *
   * @return array
   *   Array de ['date' => 'Y-m-d', 'views' => int, 'conversions' => int, 'rate' => float].
   */
  protected function getDailyConversionTrends(int $tenantId, int $from, int $to): array {
    $viewsQuery = $this->database->query(
      "SELECT DATE(FROM_UNIXTIME(created)) AS day, COUNT(*) AS cnt
       FROM {analytics_event}
       WHERE tenant_id = :tid AND event_type = 'page_view'
         AND created >= :from_ts AND created < :to_ts
       GROUP BY day ORDER BY day",
      [':tid' => $tenantId, ':from_ts' => $from, ':to_ts' => $to]
    );
    $views = [];
    if ($viewsQuery) {
      foreach ($viewsQuery as $row) {
        $views[$row->day] = (int) $row->cnt;
      }
    }

    $submitsQuery = $this->database->query(
      "SELECT DATE(FROM_UNIXTIME(created)) AS day, COUNT(*) AS cnt
       FROM {analytics_event}
       WHERE tenant_id = :tid AND event_type = 'form_submit'
         AND created >= :from_ts AND created < :to_ts
       GROUP BY day ORDER BY day",
      [':tid' => $tenantId, ':from_ts' => $from, ':to_ts' => $to]
    );
    $submits = [];
    if ($submitsQuery) {
      foreach ($submitsQuery as $row) {
        $submits[$row->day] = (int) $row->cnt;
      }
    }

    $allDays = array_unique(array_merge(array_keys($views), array_keys($submits)));
    sort($allDays);

    $trends = [];
    foreach ($allDays as $day) {
      $v = $views[$day] ?? 0;
      $c = $submits[$day] ?? 0;
      $trends[] = [
        'date' => $day,
        'views' => $v,
        'conversions' => $c,
        'rate' => $v > 0 ? round(($c / $v) * 100, 2) : 0,
      ];
    }

    return $trends;
  }

  /**
   * Obtiene ranking de paginas por tasa de conversion.
   *
   * @param int $tenantId
   *   El tenant ID.
   * @param int $from
   *   Timestamp inicio.
   * @param int $to
   *   Timestamp fin.
   * @param string $order
   *   'ASC' o 'DESC'.
   * @param int $limit
   *   Maximo de resultados.
   * @param int $minViews
   *   Minimo de page views para incluir (evita paginas sin trafico).
   *
   * @return array
   *   Array de ['page_url', 'views', 'conversions', 'rate'].
   */
  protected function getPageConversionRanking(int $tenantId, int $from, int $to, string $order, int $limit, int $minViews = 5): array {
    $result = $this->database->query(
      "SELECT
         pv.page_url,
         pv.views,
         COALESCE(fs.submits, 0) AS conversions,
         ROUND((COALESCE(fs.submits, 0) / pv.views) * 100, 2) AS rate
       FROM (
         SELECT page_url, COUNT(*) AS views
         FROM {analytics_event}
         WHERE tenant_id = :tid AND event_type = 'page_view'
           AND created >= :from_ts AND created < :to_ts
         GROUP BY page_url
         HAVING views >= :min_views
       ) pv
       LEFT JOIN (
         SELECT page_url, COUNT(*) AS submits
         FROM {analytics_event}
         WHERE tenant_id = :tid2 AND event_type = 'form_submit'
           AND created >= :from_ts2 AND created < :to_ts2
         GROUP BY page_url
       ) fs ON pv.page_url = fs.page_url
       ORDER BY rate {$order}, pv.views DESC
       LIMIT :lim",
      [
        ':tid' => $tenantId,
        ':tid2' => $tenantId,
        ':from_ts' => $from,
        ':from_ts2' => $from,
        ':to_ts' => $to,
        ':to_ts2' => $to,
        ':min_views' => $minViews,
        ':lim' => $limit,
      ]
    );

    $pages = [];
    if ($result) {
      foreach ($result as $row) {
        $pages[] = [
          'page_url' => $row->page_url,
          'views' => (int) $row->views,
          'conversions' => (int) $row->conversions,
          'rate' => (float) $row->rate,
        ];
      }
    }

    return $pages;
  }

  /**
   * Obtiene rendimiento de CTAs.
   *
   * @param int $tenantId
   *   El tenant ID.
   * @param int $from
   *   Timestamp inicio.
   * @param int $to
   *   Timestamp fin.
   *
   * @return array
   *   Array de CTAs con clicks, position, conversion rate.
   */
  protected function getCtaPerformance(int $tenantId, int $from, int $to): array {
    // cta_click events almacenan datos en event_data (map field).
    // Usamos SQL directo para rendimiento en tablas grandes.
    $ctaClicks = $this->database->query(
      "SELECT ae.event_data__value, COUNT(*) AS clicks
       FROM {analytics_event} ae
       WHERE ae.tenant_id = :tid
         AND ae.event_type = 'cta_click'
         AND ae.created >= :from_ts
         AND ae.created < :to_ts
       GROUP BY ae.event_data__value
       ORDER BY clicks DESC
       LIMIT 20",
      [
        ':tid' => $tenantId,
        ':from_ts' => $from,
        ':to_ts' => $to,
      ]
    );

    $ctas = [];
    if ($ctaClicks) {
      $totalPageViews = $this->countEvents($tenantId, 'page_view', $from, $to);
      foreach ($ctaClicks as $row) {
        $data = [];
        if (!empty($row->event_data__value)) {
          $decoded = @json_decode($row->event_data__value, TRUE);
          if (is_array($decoded)) {
            $data = $decoded;
          }
        }
        $clicks = (int) $row->clicks;
        $ctas[] = [
          'cta_label' => $data['cta_label'] ?? 'desconocido',
          'position' => $data['position'] ?? 'desconocido',
          'page_url' => $data['page_url'] ?? '',
          'clicks' => $clicks,
          'ctr' => $totalPageViews > 0 ? round(($clicks / $totalPageViews) * 100, 2) : 0,
        ];
      }
    }

    return $ctas;
  }

  /**
   * Genera recomendaciones basadas en los datos del reporte.
   *
   * Si ProactiveInsightsService esta disponible, solicita recomendaciones IA.
   * En caso contrario, genera recomendaciones basadas en reglas.
   *
   * @param int $tenantId
   *   El tenant ID.
   * @param array $report
   *   El reporte parcial ya generado.
   *
   * @return array
   *   Array de recomendaciones con: action, impact, effort, priority.
   */
  protected function generateRecommendations(int $tenantId, array $report): array {
    // PRESAVE-RESILIENCE-001: ProactiveInsightsService es opcional.
    if ($this->proactiveInsights !== NULL) {
      try {
        $aiRecommendations = $this->proactiveInsights->generateRecommendations(
          $tenantId,
          'conversion_optimization',
          $report
        );
        if (!empty($aiRecommendations)) {
          return $aiRecommendations;
        }
      }
      catch (\Throwable $e) {
        $this->logger->warning('Fallback a recomendaciones rule-based: @msg', [
          '@msg' => $e->getMessage(),
        ]);
      }
    }

    // Recomendaciones rule-based como fallback.
    return $this->generateRuleBasedRecommendations($report);
  }

  /**
   * Genera recomendaciones basadas en reglas heuristicas.
   *
   * @param array $report
   *   El reporte de conversion.
   *
   * @return array
   *   Array de recomendaciones.
   */
  protected function generateRuleBasedRecommendations(array $report): array {
    $recommendations = [];

    $rate = $report['overview']['rate'] ?? 0;
    $visits = $report['overview']['visits'] ?? 0;
    $avgTime = $report['overview']['avg_time_seconds'] ?? 0;

    // Tasa de conversion baja.
    if ($rate < 2.0 && $visits > 100) {
      $recommendations[] = [
        'action' => 'Revisar los CTAs principales: texto, posicion y contraste de color. Considerar test A/B con variantes mas directas.',
        'impact' => '15-30%',
        'effort' => 'bajo',
        'priority' => 'alta',
      ];
    }

    // Tiempo medio de sesion muy bajo.
    if ($avgTime > 0 && $avgTime < 30 && $visits > 50) {
      $recommendations[] = [
        'action' => 'El tiempo de sesion es muy bajo (<30s). Revisar velocidad de carga, relevancia del contenido y above-the-fold.',
        'impact' => '10-20%',
        'effort' => 'medio',
        'priority' => 'alta',
      ];
    }

    // Paginas con trafico pero sin conversion.
    $worstPages = $report['worst_converting_pages'] ?? [];
    if (count($worstPages) >= 3) {
      $pageUrls = array_slice(array_column($worstPages, 'page_url'), 0, 3);
      $recommendations[] = [
        'action' => sprintf(
          'Las paginas %s reciben trafico pero no convierten. Anadir CTAs visibles y formularios inline.',
          implode(', ', $pageUrls)
        ),
        'impact' => '20-40%',
        'effort' => 'medio',
        'priority' => 'media',
      ];
    }

    // Poco trafico.
    if ($visits < 50) {
      $recommendations[] = [
        'action' => 'El volumen de trafico es insuficiente para analisis fiable. Priorizar adquisicion: SEO, redes sociales y email marketing.',
        'impact' => 'N/A',
        'effort' => 'alto',
        'priority' => 'alta',
      ];
    }

    // Sin CTAs trackados.
    $ctaPerf = $report['cta_performance'] ?? [];
    if (empty($ctaPerf) && $visits > 100) {
      $recommendations[] = [
        'action' => 'No se detectan clicks en CTAs. Verificar que los botones tienen data-track-cta (FUNNEL-COMPLETENESS-001).',
        'impact' => 'N/A — diagnostico requerido',
        'effort' => 'bajo',
        'priority' => 'critica',
      ];
    }

    return $recommendations;
  }

  /**
   * Genera recomendacion contextual para un paso del funnel.
   *
   * @param string $fromStep
   *   Paso de origen.
   * @param string $toStep
   *   Paso de destino.
   * @param float $dropoffRate
   *   Tasa de abandono en porcentaje.
   *
   * @return string
   *   Recomendacion textual.
   */
  protected function getStepRecommendation(string $fromStep, string $toStep, float $dropoffRate): string {
    if ($dropoffRate < 30) {
      return 'Rendimiento aceptable para esta fase del funnel.';
    }

    $recommendations = [
      'page_view_cta_click' => 'Alto abandono antes del click en CTA. Revisar visibilidad, copy del boton y posicion above-the-fold.',
      'cta_click_form_start' => 'Los usuarios hacen click en el CTA pero no inician el formulario. Verificar que el formulario carga correctamente y que la transicion es fluida.',
      'form_start_form_submit' => 'Abandono en el formulario. Reducir campos, anadir indicadores de progreso y validacion en tiempo real.',
      'form_submit_confirmation' => 'Problema tecnico post-submit. Verificar que la pagina de confirmacion carga correctamente y que no hay errores de servidor.',
    ];

    $key = $fromStep . '_' . $toStep;
    return $recommendations[$key] ?? sprintf(
      'Dropoff del %.1f%% entre %s y %s. Requiere investigacion especifica.',
      $dropoffRate,
      $fromStep,
      $toStep
    );
  }

  /**
   * Obtiene los IDs de tenants activos con eventos recientes.
   *
   * @return array
   *   Array de tenant IDs.
   */
  protected function getActiveTenantIds(): array {
    $thirtyDaysAgo = \Drupal::time()->getRequestTime() - (30 * 86400);

    $result = $this->database->query(
      "SELECT DISTINCT tenant_id FROM {analytics_event}
       WHERE tenant_id IS NOT NULL AND created >= :since",
      [':since' => $thirtyDaysAgo]
    );

    return $result ? $result->fetchCol() : [];
  }

}
