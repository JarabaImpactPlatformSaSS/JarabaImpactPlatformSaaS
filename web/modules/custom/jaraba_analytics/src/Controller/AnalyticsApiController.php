<?php

namespace Drupal\jaraba_analytics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_analytics\Service\AnalyticsService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador de API REST para Analytics.
 *
 * Proporciona endpoints para tracking de eventos y consulta de métricas.
 */
class AnalyticsApiController extends ControllerBase
{

    /**
     * Servicio de analytics.
     *
     * @var \Drupal\jaraba_analytics\Service\AnalyticsService
     */
    protected AnalyticsService $analyticsService;

    /**
     * Conexión a base de datos.
     *
     * @var \Drupal\Core\Database\Connection
     */
    protected Connection $database;

    /**
     * Servicio de contexto de tenant.
     *
     * @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService
     */
    protected TenantContextService $tenantContext;

    /**
     * Constructor.
     */
    public function __construct(AnalyticsService $analytics_service, Connection $database, TenantContextService $tenant_context)
    {
        $this->analyticsService = $analytics_service;
        $this->database = $database;
        $this->tenantContext = $tenant_context;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('jaraba_analytics.analytics_service'),
            $container->get('database'),
            $container->get('ecosistema_jaraba_core.tenant_context')
        );
    }

    /**
     * POST /api/v1/analytics/event.
     *
     * Registra un evento de tracking.
     */
    public function trackEvent(Request $request): JsonResponse
    {
        $content = json_decode($request->getContent(), TRUE);

        if (!$content || !isset($content['event_type'])) {
            return new JsonResponse([
                'error' => 'Missing event_type',
            ], 400);
        }

        $event = $this->analyticsService->trackEvent(
            $content['event_type'],
            $content['data'] ?? [],
            $this->tenantContext->getCurrentTenantId() ?? ($content['tenant_id'] ?? NULL)
        );

        if ($event) {
            return new JsonResponse([
                'success' => TRUE,
                'event_id' => $event->id(),
            ], 201);
        }

        return new JsonResponse([
            'error' => 'Failed to track event',
        ], 500);
    }

    /**
     * POST /api/v1/analytics/batch.
     *
     * Registra múltiples eventos en batch.
     */
    public function trackBatch(Request $request): JsonResponse
    {
        $content = json_decode($request->getContent(), TRUE);

        if (!$content || !isset($content['events']) || !is_array($content['events'])) {
            return new JsonResponse([
                'error' => 'Missing events array',
            ], 400);
        }

        $results = [];
        foreach ($content['events'] as $eventData) {
            if (!isset($eventData['event_type'])) {
                $results[] = ['error' => 'Missing event_type'];
                continue;
            }

            $event = $this->analyticsService->trackEvent(
                $eventData['event_type'],
                $eventData['data'] ?? [],
                $this->tenantContext->getCurrentTenantId() ?? ($eventData['tenant_id'] ?? NULL)
            );

            $results[] = [
                'success' => $event !== NULL,
                'event_id' => $event?->id(),
            ];
        }

        return new JsonResponse([
            'results' => $results,
            'total' => count($results),
        ], 201);
    }

    /**
     * GET /api/v1/analytics/dashboard.
     *
     * Devuelve KPIs principales del dashboard.
     */
    public function getDashboard(Request $request): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId() ?? $request->query->get('tenant_id');
        $startDate = $request->query->get('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $request->query->get('end_date', date('Y-m-d'));

        if (!$tenantId) {
            return new JsonResponse(['error' => 'Missing tenant_id'], 400);
        }

        $metrics = $this->analyticsService->getDailyMetrics((int) $tenantId, $startDate, $endDate);

        // Calcular totales.
        $totals = [
            'page_views' => 0,
            'unique_visitors' => 0,
            'sessions' => 0,
            'total_revenue' => 0,
        ];

        foreach ($metrics as $day) {
            $totals['page_views'] += $day['page_views'];
            $totals['sessions'] += $day['sessions'];
            $totals['total_revenue'] += $day['total_revenue'];
        }

        // Visitantes únicos no se suman, se toma el máximo como aproximación.
        $totals['unique_visitors'] = count($metrics) > 0
            ? max(array_column($metrics, 'unique_visitors'))
            : 0;

        return new JsonResponse([
            'tenant_id' => (int) $tenantId,
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'totals' => $totals,
            'daily' => $metrics,
        ]);
    }

    /**
     * GET /api/v1/analytics/realtime.
     *
     * Devuelve visitantes en tiempo real.
     */
    public function getRealtime(Request $request): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId() ?? $request->query->get('tenant_id');

        if (!$tenantId) {
            return new JsonResponse(['error' => 'Missing tenant_id'], 400);
        }

        $count = $this->analyticsService->getRealtimeVisitors((int) $tenantId);

        return new JsonResponse([
            'tenant_id' => (int) $tenantId,
            'active_visitors' => $count,
            'timestamp' => time(),
        ]);
    }

    /**
     * GET /api/v1/analytics/funnel.
     *
     * Devuelve datos de funnel de conversión.
     */
    public function getFunnel(Request $request): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId() ?? $request->query->get('tenant_id');

        if (!$tenantId) {
            return new JsonResponse(['error' => 'Missing tenant_id'], 400);
        }

        $startDate = $request->query->get('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $request->query->get('end_date', date('Y-m-d'));
        $startTs = strtotime($startDate . ' 00:00:00');
        $endTs = strtotime($endDate . ' 23:59:59');

        // Funnel steps: count unique sessions per event type.
        $steps = ['page_view', 'product_view', 'add_to_cart', 'begin_checkout', 'purchase'];
        $funnel = [];

        $firstCount = 0;
        foreach ($steps as $index => $step) {
            $query = $this->database->select('analytics_event', 'ae')
                ->fields('ae', ['session_id'])
                ->condition('ae.tenant_id', (int) $tenantId)
                ->condition('ae.event_type', $step)
                ->condition('ae.created', $startTs, '>=')
                ->condition('ae.created', $endTs, '<=')
                ->distinct()
                ->countQuery();

            $count = (int) $query->execute()->fetchField();

            if ($index === 0) {
                $firstCount = $count;
            }

            $funnel[] = [
                'step' => $step,
                'count' => $count,
                'rate' => $firstCount > 0 ? round($count / $firstCount * 100, 2) : 0,
            ];
        }

        return new JsonResponse([
            'tenant_id' => (int) $tenantId,
            'period' => ['start' => $startDate, 'end' => $endDate],
            'funnel' => $funnel,
        ]);
    }

    /**
     * GET /api/v1/analytics/pages/top.
     *
     * Devuelve top páginas por visitas.
     */
    public function getTopPages(Request $request): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId() ?? $request->query->get('tenant_id');
        $limit = $request->query->get('limit', 10);
        $startDate = $request->query->get('start_date', '');
        $endDate = $request->query->get('end_date', '');

        if (!$tenantId) {
            return new JsonResponse(['error' => 'Missing tenant_id'], 400);
        }

        $pages = $this->analyticsService->getTopPages(
            (int) $tenantId,
            (int) $limit,
            $startDate,
            $endDate
        );

        return new JsonResponse([
            'tenant_id' => (int) $tenantId,
            'pages' => $pages,
        ]);
    }

    /**
     * GET /api/v1/analytics/traffic-sources.
     *
     * Devuelve fuentes de tráfico.
     */
    public function getTrafficSources(Request $request): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId() ?? $request->query->get('tenant_id');
        $startDate = $request->query->get('start_date', '');
        $endDate = $request->query->get('end_date', '');

        if (!$tenantId) {
            return new JsonResponse(['error' => 'Missing tenant_id'], 400);
        }

        $sources = $this->analyticsService->getTrafficSources(
            (int) $tenantId,
            $startDate,
            $endDate
        );

        return new JsonResponse([
            'tenant_id' => (int) $tenantId,
            'sources' => $sources,
        ]);
    }

}
