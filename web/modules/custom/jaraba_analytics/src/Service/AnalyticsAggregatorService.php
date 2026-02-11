<?php

namespace Drupal\jaraba_analytics\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de agregación de métricas.
 *
 * Ejecuta agregación diaria de eventos para generar métricas
 * precalculadas en analytics_daily.
 */
class AnalyticsAggregatorService
{

    /**
     * Conexión a base de datos.
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
     * Cache backend.
     *
     * @var \Drupal\Core\Cache\CacheBackendInterface
     */
    protected CacheBackendInterface $cache;

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
        CacheBackendInterface $cache,
        $logger_factory,
    ) {
        $this->database = $database;
        $this->entityTypeManager = $entity_type_manager;
        $this->cache = $cache;
        $this->logger = $logger_factory->get('jaraba_analytics');
    }

    /**
     * Agrega métricas del día anterior para todos los tenants.
     *
     * Este método se ejecuta via cron a las 02:00 UTC.
     */
    public function aggregateDailyMetrics(): void
    {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $startTs = strtotime($yesterday . ' 00:00:00');
        $endTs = strtotime($yesterday . ' 23:59:59');

        // Obtener lista de tenants con eventos ayer.
        $tenantIds = $this->getActiveTenants($startTs, $endTs);

        foreach ($tenantIds as $tenantId) {
            $this->aggregateForTenant((int) $tenantId, $yesterday, $startTs, $endTs);
        }

        $this->logger->info('Daily aggregation completed for @count tenants, date: @date', [
            '@count' => count($tenantIds),
            '@date' => $yesterday,
        ]);
    }

    /**
     * Agrega métricas para un tenant específico.
     *
     * @param int $tenant_id
     *   ID del tenant.
     * @param string $date
     *   Fecha en formato Y-m-d.
     * @param int $start_ts
     *   Timestamp inicio del día.
     * @param int $end_ts
     *   Timestamp fin del día.
     */
    public function aggregateForTenant(int $tenant_id, string $date, int $start_ts, int $end_ts): void
    {
        try {
            // Calcular métricas básicas.
            $metrics = $this->calculateBasicMetrics($tenant_id, $start_ts, $end_ts);
            $ecommerceMetrics = $this->calculateEcommerceMetrics($tenant_id, $start_ts, $end_ts);
            $topPages = $this->calculateTopPages($tenant_id, $start_ts, $end_ts);
            $topReferrers = $this->calculateTopReferrers($tenant_id, $start_ts, $end_ts);
            $deviceBreakdown = $this->calculateDeviceBreakdown($tenant_id, $start_ts, $end_ts);

            // Verificar si ya existe registro para esta fecha.
            $storage = $this->entityTypeManager->getStorage('analytics_daily');
            $existing = $storage->getQuery()
                ->accessCheck(FALSE)
                ->condition('tenant_id', $tenant_id)
                ->condition('date', $date)
                ->execute();

            if (!empty($existing)) {
                // Actualizar existente.
                $entity = $storage->load(reset($existing));
            } else {
                // Crear nuevo.
                $entity = $storage->create([
                    'tenant_id' => $tenant_id,
                    'date' => $date,
                ]);
            }

            // Establecer métricas.
            $entity->set('page_views', $metrics['page_views']);
            $entity->set('unique_visitors', $metrics['unique_visitors']);
            $entity->set('sessions', $metrics['sessions']);
            $entity->set('bounce_rate', $metrics['bounce_rate']);
            $entity->set('avg_session_duration', $metrics['avg_session_duration']);

            // E-commerce.
            $entity->set('total_revenue', $ecommerceMetrics['total_revenue']);
            $entity->set('orders_count', $ecommerceMetrics['orders_count']);
            $entity->set('avg_order_value', $ecommerceMetrics['avg_order_value']);
            $entity->set('conversion_rate', $ecommerceMetrics['conversion_rate']);
            $entity->set('new_users', $ecommerceMetrics['new_users']);

            // JSONs.
            $entity->set('top_pages', $topPages);
            $entity->set('top_referrers', $topReferrers);
            $entity->set('device_breakdown', $deviceBreakdown);

            $entity->save();

            // Invalidar cache.
            $this->cache->invalidate("analytics_daily:{$tenant_id}");

        } catch (\Exception $e) {
            $this->logger->error('Error aggregating tenant @id: @message', [
                '@id' => $tenant_id,
                '@message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Obtiene lista de tenants con eventos en el rango.
     */
    protected function getActiveTenants(int $start_ts, int $end_ts): array
    {
        return $this->database->select('analytics_event', 'ae')
            ->fields('ae', ['tenant_id'])
            ->condition('ae.created', $start_ts, '>=')
            ->condition('ae.created', $end_ts, '<=')
            ->isNotNull('ae.tenant_id')
            ->distinct()
            ->execute()
            ->fetchCol();
    }

    /**
     * Calcula métricas básicas de tráfico.
     */
    protected function calculateBasicMetrics(int $tenant_id, int $start_ts, int $end_ts): array
    {
        // Page views.
        $pageViews = $this->database->select('analytics_event', 'ae')
            ->condition('ae.tenant_id', $tenant_id)
            ->condition('ae.event_type', 'page_view')
            ->condition('ae.created', $start_ts, '>=')
            ->condition('ae.created', $end_ts, '<=')
            ->countQuery()
            ->execute()
            ->fetchField();

        // Unique visitors.
        $uniqueVisitors = $this->database->select('analytics_event', 'ae')
            ->fields('ae', ['visitor_id'])
            ->condition('ae.tenant_id', $tenant_id)
            ->condition('ae.created', $start_ts, '>=')
            ->condition('ae.created', $end_ts, '<=')
            ->distinct()
            ->countQuery()
            ->execute()
            ->fetchField();

        // Sessions.
        $sessions = $this->database->select('analytics_event', 'ae')
            ->fields('ae', ['session_id'])
            ->condition('ae.tenant_id', $tenant_id)
            ->condition('ae.created', $start_ts, '>=')
            ->condition('ae.created', $end_ts, '<=')
            ->distinct()
            ->countQuery()
            ->execute()
            ->fetchField();

        // Bounce rate: sessions with exactly 1 page_view.
        $bounceSessions = 0;
        if ($sessions > 0) {
            $bounceQuery = $this->database->select('analytics_event', 'ae');
            $bounceQuery->addField('ae', 'session_id');
            $bounceQuery->condition('ae.tenant_id', $tenant_id);
            $bounceQuery->condition('ae.event_type', 'page_view');
            $bounceQuery->condition('ae.created', $start_ts, '>=');
            $bounceQuery->condition('ae.created', $end_ts, '<=');
            $bounceQuery->addExpression('COUNT(*)', 'pv_count');
            $bounceQuery->groupBy('ae.session_id');
            $bounceQuery->having('COUNT(*) = 1');

            $bounceSessions = (int) $this->database->select($bounceQuery, 'bounce')
                ->countQuery()
                ->execute()
                ->fetchField();
        }

        // Avg session duration: MAX(created) - MIN(created) per session.
        $avgSessionDuration = 0;
        if ($sessions > 0) {
            $durationQuery = $this->database->select('analytics_event', 'ae');
            $durationQuery->condition('ae.tenant_id', $tenant_id);
            $durationQuery->condition('ae.created', $start_ts, '>=');
            $durationQuery->condition('ae.created', $end_ts, '<=');
            $durationQuery->addField('ae', 'session_id');
            $durationQuery->addExpression('MAX(ae.created) - MIN(ae.created)', 'duration');
            $durationQuery->groupBy('ae.session_id');

            $durations = $durationQuery->execute()->fetchCol(1);
            if (!empty($durations)) {
                $avgSessionDuration = round(array_sum($durations) / count($durations));
            }
        }

        return [
            'page_views' => (int) $pageViews,
            'unique_visitors' => (int) $uniqueVisitors,
            'sessions' => (int) $sessions,
            'bounce_rate' => $sessions > 0 ? round($bounceSessions / $sessions, 4) : 0,
            'avg_session_duration' => $avgSessionDuration,
        ];
    }

    /**
     * Calcula métricas e-commerce.
     */
    protected function calculateEcommerceMetrics(int $tenant_id, int $start_ts, int $end_ts): array
    {
        // Purchases.
        $purchaseQuery = $this->database->select('analytics_event', 'ae')
            ->condition('ae.tenant_id', $tenant_id)
            ->condition('ae.event_type', 'purchase')
            ->condition('ae.created', $start_ts, '>=')
            ->condition('ae.created', $end_ts, '<=');

        $ordersCount = (clone $purchaseQuery)->countQuery()->execute()->fetchField();

        // Sum event_data.value for purchase events.
        $totalRevenue = 0;
        $revenueQuery = (clone $purchaseQuery)->fields('ae', ['event_data']);
        $purchaseRows = $revenueQuery->execute()->fetchAll();
        foreach ($purchaseRows as $row) {
            $eventData = is_string($row->event_data) ? json_decode($row->event_data, TRUE) : $row->event_data;
            if (is_array($eventData) && isset($eventData['value'])) {
                $totalRevenue += (float) $eventData['value'];
            }
        }

        // Nuevos users.
        $newUsers = $this->database->select('analytics_event', 'ae')
            ->condition('ae.tenant_id', $tenant_id)
            ->condition('ae.event_type', 'signup')
            ->condition('ae.created', $start_ts, '>=')
            ->condition('ae.created', $end_ts, '<=')
            ->countQuery()
            ->execute()
            ->fetchField();

        // Calcular métricas derivadas.
        $avgOrderValue = $ordersCount > 0 ? $totalRevenue / $ordersCount : 0;

        // Conversion rate: purchases / unique_visitors.
        $uniqueVisitors = (int) $this->database->select('analytics_event', 'ae')
            ->fields('ae', ['visitor_id'])
            ->condition('ae.tenant_id', $tenant_id)
            ->condition('ae.created', $start_ts, '>=')
            ->condition('ae.created', $end_ts, '<=')
            ->distinct()
            ->countQuery()
            ->execute()
            ->fetchField();
        $conversionRate = $uniqueVisitors > 0 ? round((int) $ordersCount / $uniqueVisitors, 4) : 0;

        return [
            'total_revenue' => $totalRevenue,
            'orders_count' => (int) $ordersCount,
            'avg_order_value' => round($avgOrderValue, 2),
            'conversion_rate' => $conversionRate,
            'new_users' => (int) $newUsers,
        ];
    }

    /**
     * Calcula top 10 páginas.
     */
    protected function calculateTopPages(int $tenant_id, int $start_ts, int $end_ts): array
    {
        $query = $this->database->select('analytics_event', 'ae')
            ->fields('ae', ['page_url'])
            ->condition('ae.tenant_id', $tenant_id)
            ->condition('ae.event_type', 'page_view')
            ->condition('ae.created', $start_ts, '>=')
            ->condition('ae.created', $end_ts, '<=');

        $query->addExpression('COUNT(*)', 'views');
        $query->groupBy('ae.page_url');
        $query->orderBy('views', 'DESC');
        $query->range(0, 10);

        $results = $query->execute()->fetchAll();

        $pages = [];
        foreach ($results as $row) {
            $pages[] = [
                'url' => $row->page_url,
                'views' => (int) $row->views,
            ];
        }

        return $pages;
    }

    /**
     * Calcula top 10 referrers.
     */
    protected function calculateTopReferrers(int $tenant_id, int $start_ts, int $end_ts): array
    {
        $query = $this->database->select('analytics_event', 'ae')
            ->fields('ae', ['referrer'])
            ->condition('ae.tenant_id', $tenant_id)
            ->condition('ae.event_type', 'page_view')
            ->condition('ae.created', $start_ts, '>=')
            ->condition('ae.created', $end_ts, '<=')
            ->isNotNull('ae.referrer');

        $query->addExpression('COUNT(*)', 'count');
        $query->groupBy('ae.referrer');
        $query->orderBy('count', 'DESC');
        $query->range(0, 10);

        $results = $query->execute()->fetchAll();

        $referrers = [];
        foreach ($results as $row) {
            $referrers[] = [
                'referrer' => $row->referrer,
                'count' => (int) $row->count,
            ];
        }

        return $referrers;
    }

    /**
     * Calcula distribución por dispositivo.
     */
    protected function calculateDeviceBreakdown(int $tenant_id, int $start_ts, int $end_ts): array
    {
        $query = $this->database->select('analytics_event', 'ae')
            ->fields('ae', ['device_type'])
            ->condition('ae.tenant_id', $tenant_id)
            ->condition('ae.created', $start_ts, '>=')
            ->condition('ae.created', $end_ts, '<=');

        $query->addExpression('COUNT(*)', 'count');
        $query->groupBy('ae.device_type');

        $results = $query->execute()->fetchAllKeyed();

        $total = array_sum($results);

        $breakdown = [];
        foreach ($results as $device => $count) {
            $breakdown[$device ?: 'unknown'] = $total > 0 ? round($count / $total * 100, 2) : 0;
        }

        return $breakdown;
    }

}
