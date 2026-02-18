<?php

namespace Drupal\jaraba_heatmap\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\jaraba_heatmap\Service\HeatmapScreenshotService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;

/**
 * Controlador API para consulta de datos de heatmap.
 *
 * Proporciona endpoints GET para:
 * - Listar páginas con datos de heatmap
 * - Obtener datos de clicks por página
 * - Obtener datos de scroll por página
 * - Obtener datos de movimiento por página
 * - Obtener resumen general de métricas
 * - Obtener/capturar screenshots de páginas
 *
 * Ref: Doc Técnico #180 - Native Heatmaps System
 * Ref: Spec 20260130a §6.1, §7.2
 */
class HeatmapApiController extends ControllerBase
{

    /**
     * Conexión a base de datos.
     */
    protected Connection $database;

    /**
     * Servicio de capturas de pantalla.
     */
    protected HeatmapScreenshotService $screenshotService;

    // AUDIT-SEC-N06: Server-side tenant resolution, prevents IDOR.
    /**
     * Servicio de contexto de tenant.
     */
    protected TenantContextService $tenantContext;

    /**
     * Constructor.
     *
     * @param \Drupal\Core\Database\Connection $database
     *   Conexión a base de datos.
     * @param \Drupal\jaraba_heatmap\Service\HeatmapScreenshotService $screenshot_service
     *   Servicio de capturas de página.
     * @param \Drupal\ecosistema_jaraba_core\Service\TenantContextService $tenant_context
     *   Servicio de contexto de tenant.
     */
    public function __construct(Connection $database, HeatmapScreenshotService $screenshot_service, TenantContextService $tenant_context)
    {
        $this->database = $database;
        $this->screenshotService = $screenshot_service;
        // AUDIT-SEC-N06: Server-side tenant resolution, prevents IDOR.
        $this->tenantContext = $tenant_context;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('database'),
            $container->get('jaraba_heatmap.screenshot'),
            $container->get('ecosistema_jaraba_core.tenant_context'), // AUDIT-SEC-N06: Server-side tenant resolution, prevents IDOR.
        );
    }

    /**
     * Lista páginas con datos de heatmap disponibles.
     *
     * También incluye páginas del Page Builder sin datos para que
     * el usuario pueda ver y generar tráfico en ellas.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON con lista de páginas y métricas básicas.
     */
    public function listPages(Request $request): JsonResponse
    {
        // AUDIT-SEC-N06: Server-side tenant resolution, prevents IDOR.
        $tenant_id = $this->getTenantId();
        if ($tenant_id <= 0) {
            return new JsonResponse(['success' => FALSE, 'error' => 'Tenant context required.'], 403);
        }
        $pages = [];
        $seen_paths = [];

        // 1. Consultar páginas CON datos de heatmap agregados.
        $query = $this->database->select('heatmap_aggregated', 'ha');
        $query->fields('ha', ['page_path']);
        $query->addExpression('SUM(ha.event_count)', 'total_events');
        $query->addExpression('SUM(ha.unique_sessions)', 'total_sessions');
        $query->addExpression('MAX(ha.date)', 'last_activity');
        $query->condition('ha.tenant_id', $tenant_id);
        $query->groupBy('ha.page_path');
        $query->orderBy('total_events', 'DESC');
        $query->range(0, 50);

        $results = $query->execute()->fetchAll();

        foreach ($results as $row) {
            $pages[] = [
                'path' => $row->page_path,
                'events' => (int) $row->total_events,
                'sessions' => (int) $row->total_sessions,
                'lastActivity' => $row->last_activity,
            ];
            $seen_paths[$row->page_path] = TRUE;
        }

        // 2. Incluir páginas del Page Builder SIN datos de heatmap.
        // Esto permite al usuario ver todas las páginas disponibles.
        try {
            $page_storage = \Drupal::entityTypeManager()->getStorage('page_content');
            $page_ids = $page_storage->getQuery()
                ->accessCheck(FALSE)
                ->condition('status', TRUE)
                ->condition('tenant_id', $tenant_id)
                ->range(0, 50)
                ->execute();

            if (!empty($page_ids)) {
                $page_entities = $page_storage->loadMultiple($page_ids);
                foreach ($page_entities as $page) {
                    // Usar path_alias si existe, sino la URL canónica.
                    $path_alias = $page->get('path_alias')->value;
                    $path = !empty($path_alias) ? $path_alias : '/page/' . $page->id();
                    if (!isset($seen_paths[$path])) {
                        $pages[] = [
                            'id' => (int) $page->id(),
                            'path' => $path,
                            'title' => $page->label(),
                            'events' => 0,
                            'sessions' => 0,
                            'lastActivity' => NULL,
                        ];
                        $seen_paths[$path] = TRUE;
                    }
                }
            }
        } catch (\Exception $e) {
            // Si page_content no existe o hay error, continuar sin fallo.
            \Drupal::logger('jaraba_heatmap')->warning('Could not load Page Builder pages: @error', ['@error' => $e->getMessage()]);
        }

        return new JsonResponse([
            'success' => TRUE,
            'data' => $pages,
            'count' => count($pages),
        ]);
    }

    /**
     * Obtiene datos de clicks para una página.
     *
     * @param string $path
     *   Path de la página (URL-encoded).
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON con datos de clicks por buckets.
     */
    public function getClickData(string $path, Request $request): JsonResponse
    {
        return $this->getEventData($path, 'click', $request);
    }

    /**
     * Obtiene datos de scroll para una página.
     *
     * @param string $path
     *   Path de la página.
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON con datos de scroll depth.
     */
    public function getScrollData(string $path, Request $request): JsonResponse
    {
        // AUDIT-SEC-N06: Server-side tenant resolution, prevents IDOR.
        $tenant_id = $this->getTenantId();
        if ($tenant_id <= 0) {
            return new JsonResponse(['success' => FALSE, 'error' => 'Tenant context required.'], 403);
        }
        $decoded_path = urldecode($path);

        // Obtener días de rango de filtro.
        $days = (int) $request->query->get('days', 7);
        $from_date = date('Y-m-d', strtotime("-{$days} days"));

        // Consultar datos de scroll.
        $query = $this->database->select('heatmap_scroll_depth', 'hsd');
        $query->fields('hsd', [
            'depth_25',
            'depth_50',
            'depth_75',
            'depth_100',
            'avg_max_depth',
            'total_sessions',
            'device_type',
        ]);
        $query->condition('hsd.tenant_id', $tenant_id);
        $query->condition('hsd.page_path', $decoded_path);
        $query->condition('hsd.date', $from_date, '>=');
        $query->orderBy('hsd.date', 'DESC');

        $results = $query->execute()->fetchAll();

        // Agregar datos por dispositivo.
        $aggregated = [];
        foreach ($results as $row) {
            $device = $row->device_type ?: 'all';
            if (!isset($aggregated[$device])) {
                $aggregated[$device] = [
                    'depth_25' => 0,
                    'depth_50' => 0,
                    'depth_75' => 0,
                    'depth_100' => 0,
                    'avg_depth' => 0,
                    'sessions' => 0,
                ];
            }
            $aggregated[$device]['depth_25'] += (int) $row->depth_25;
            $aggregated[$device]['depth_50'] += (int) $row->depth_50;
            $aggregated[$device]['depth_75'] += (int) $row->depth_75;
            $aggregated[$device]['depth_100'] += (int) $row->depth_100;
            $aggregated[$device]['sessions'] += (int) $row->total_sessions;
        }

        // Calcular porcentajes.
        foreach ($aggregated as $device => &$data) {
            if ($data['sessions'] > 0) {
                $data['pct_25'] = round($data['depth_25'] / $data['sessions'] * 100, 1);
                $data['pct_50'] = round($data['depth_50'] / $data['sessions'] * 100, 1);
                $data['pct_75'] = round($data['depth_75'] / $data['sessions'] * 100, 1);
                $data['pct_100'] = round($data['depth_100'] / $data['sessions'] * 100, 1);
            }
        }

        return new JsonResponse([
            'success' => TRUE,
            'path' => $decoded_path,
            'data' => $aggregated,
            'period' => [
                'from' => $from_date,
                'to' => date('Y-m-d'),
            ],
        ]);
    }

    /**
     * Obtiene datos de movimiento de mouse para una página.
     *
     * @param string $path
     *   Path de la página.
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON con datos de movimiento por buckets.
     */
    public function getMovementData(string $path, Request $request): JsonResponse
    {
        return $this->getEventData($path, 'move', $request);
    }

    /**
     * Obtiene resumen general de métricas de heatmap.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON con resumen de métricas.
     */
    public function getSummary(Request $request): JsonResponse
    {
        // AUDIT-SEC-N06: Server-side tenant resolution, prevents IDOR.
        $tenant_id = $this->getTenantId();
        if ($tenant_id <= 0) {
            return new JsonResponse(['success' => FALSE, 'error' => 'Tenant context required.'], 403);
        }

        // Obtener días de rango.
        $days = (int) $request->query->get('days', 30);
        $from_date = date('Y-m-d', strtotime("-{$days} days"));

        // Total de eventos.
        $query_events = $this->database->select('heatmap_aggregated', 'ha');
        $query_events->addExpression('SUM(ha.event_count)', 'total');
        $query_events->condition('ha.tenant_id', $tenant_id);
        $query_events->condition('ha.date', $from_date, '>=');
        $total_events = (int) $query_events->execute()->fetchField();

        // Páginas únicas.
        $query_pages = $this->database->select('heatmap_aggregated', 'ha');
        $query_pages->addExpression('COUNT(DISTINCT ha.page_path)', 'total');
        $query_pages->condition('ha.tenant_id', $tenant_id);
        $query_pages->condition('ha.date', $from_date, '>=');
        $total_pages = (int) $query_pages->execute()->fetchField();

        // Sesiones únicas (aproximado).
        $query_sessions = $this->database->select('heatmap_aggregated', 'ha');
        $query_sessions->addExpression('SUM(ha.unique_sessions)', 'total');
        $query_sessions->condition('ha.tenant_id', $tenant_id);
        $query_sessions->condition('ha.date', $from_date, '>=');
        $query_sessions->condition('ha.event_type', 'click');
        $total_sessions = (int) $query_sessions->execute()->fetchField();

        return new JsonResponse([
            'success' => TRUE,
            'summary' => [
                'totalEvents' => $total_events,
                'trackedPages' => $total_pages,
                'uniqueSessions' => $total_sessions,
                'period' => [
                    'days' => $days,
                    'from' => $from_date,
                    'to' => date('Y-m-d'),
                ],
            ],
        ]);
    }

    /**
     * Obtiene el screenshot de una página para overlay de heatmap.
     *
     * @param string $page_path
     *   Path de la página (URL-encoded).
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON con datos del screenshot o error 404.
     */
    public function getScreenshot(string $page_path): JsonResponse
    {
        // AUDIT-SEC-N06: Server-side tenant resolution, prevents IDOR.
        $tenantId = $this->getTenantId();
        if ($tenantId <= 0) {
            return new JsonResponse(['success' => FALSE, 'error' => 'Tenant context required.'], 403);
        }
        $decodedPath = urldecode($page_path);

        $screenshot = $this->screenshotService->getScreenshot($tenantId, $decodedPath);

        if (!$screenshot) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => (string) $this->t('No screenshot available for this page.'),
            ], 404);
        }

        $screenshotUrl = \Drupal::service('file_url_generator')
            ->generateAbsoluteString($screenshot['screenshot_uri']);

        return new JsonResponse([
            'success' => TRUE,
            'data' => [
                'screenshot_url' => $screenshotUrl,
                'page_height' => (int) $screenshot['page_height'],
                'viewport_width' => (int) $screenshot['viewport_width'],
                'captured_at' => (int) $screenshot['captured_at'],
            ],
        ]);
    }

    /**
     * Captura (o recaptura) el screenshot de una página.
     *
     * @param string $page_path
     *   Path de la página (URL-encoded).
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON con datos del nuevo screenshot o error 500.
     */
    public function captureScreenshot(string $page_path): JsonResponse
    {
        // AUDIT-SEC-N06: Server-side tenant resolution, prevents IDOR.
        $tenantId = $this->getTenantId();
        if ($tenantId <= 0) {
            return new JsonResponse(['success' => FALSE, 'error' => 'Tenant context required.'], 403);
        }
        $decodedPath = urldecode($page_path);

        $screenshot = $this->screenshotService->getScreenshot($tenantId, $decodedPath, TRUE);

        if (!$screenshot) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => (string) $this->t('Failed to capture screenshot for this page.'),
            ], 500);
        }

        $screenshotUrl = \Drupal::service('file_url_generator')
            ->generateAbsoluteString($screenshot['screenshot_uri']);

        return new JsonResponse([
            'success' => TRUE,
            'data' => [
                'screenshot_url' => $screenshotUrl,
                'page_height' => (int) $screenshot['page_height'],
                'viewport_width' => (int) $screenshot['viewport_width'],
                'captured_at' => (int) $screenshot['captured_at'],
            ],
        ]);
    }

    /**
     * Método interno para obtener datos de eventos por tipo.
     *
     * @param string $path
     *   Path de la página.
     * @param string $event_type
     *   Tipo de evento (click, move).
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON con datos por buckets.
     */
    protected function getEventData(string $path, string $event_type, Request $request): JsonResponse
    {
        // AUDIT-SEC-N06: Server-side tenant resolution, prevents IDOR.
        $tenant_id = $this->getTenantId();
        if ($tenant_id <= 0) {
            return new JsonResponse(['success' => FALSE, 'error' => 'Tenant context required.'], 403);
        }
        $decoded_path = urldecode($path);

        // Filtros opcionales.
        $days = (int) $request->query->get('days', 7);
        $device = $request->query->get('device', 'all');
        $from_date = date('Y-m-d', strtotime("-{$days} days"));

        // Consultar datos agregados.
        $query = $this->database->select('heatmap_aggregated', 'ha');
        $query->fields('ha', ['x_bucket', 'y_bucket', 'event_count', 'unique_sessions']);
        $query->condition('ha.tenant_id', $tenant_id);
        $query->condition('ha.page_path', $decoded_path);
        $query->condition('ha.event_type', $event_type);
        $query->condition('ha.date', $from_date, '>=');

        if ($device !== 'all') {
            $query->condition('ha.device_type', $device);
        }

        $results = $query->execute()->fetchAll();

        // Agregar por bucket.
        $buckets = [];
        $max_count = 0;
        foreach ($results as $row) {
            $key = $row->x_bucket . '_' . $row->y_bucket;
            if (!isset($buckets[$key])) {
                $buckets[$key] = [
                    'x' => (int) $row->x_bucket,
                    'y' => (int) $row->y_bucket,
                    'count' => 0,
                    'sessions' => 0,
                ];
            }
            $buckets[$key]['count'] += (int) $row->event_count;
            $buckets[$key]['sessions'] += (int) $row->unique_sessions;
            $max_count = max($max_count, $buckets[$key]['count']);
        }

        // Normalizar intensidades (0-1).
        foreach ($buckets as &$bucket) {
            $bucket['intensity'] = $max_count > 0 ? round($bucket['count'] / $max_count, 3) : 0;
        }

        return new JsonResponse([
            'success' => TRUE,
            'path' => $decoded_path,
            'eventType' => $event_type,
            'buckets' => array_values($buckets),
            'maxCount' => $max_count,
            'period' => [
                'from' => $from_date,
                'to' => date('Y-m-d'),
            ],
        ]);
    }

    /**
     * Obtiene el tenant_id del contexto actual del servidor.
     *
     * AUDIT-SEC-N06: Server-side tenant resolution, prevents IDOR.
     * Never uses client-supplied tenant_id.
     *
     * @return int
     *   ID del tenant o 0 si no hay multi-tenancy.
     */
    protected function getTenantId(): int
    {
        return (int) ($this->tenantContext->getCurrentTenantId() ?? 0);
    }

}
