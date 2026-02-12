<?php

declare(strict_types=1);

namespace Drupal\jaraba_insights_hub\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador API para Uptime Monitoring.
 *
 * PROPOSITO:
 * Expone endpoints para consultar el estado de disponibilidad de endpoints,
 * listar incidentes historicos y agregar nuevos endpoints a monitorear.
 *
 * FUNCIONALIDADES:
 * - Estado actual de todos los endpoints monitoreados del tenant
 * - Listado historico de incidentes con filtros
 * - Alta de nuevos endpoints para monitoreo
 * - Calculo de porcentaje de uptime por periodo
 *
 * RUTAS:
 * - GET /api/v1/insights/uptime/status -> status()
 * - GET /api/v1/insights/uptime/incidents -> incidents()
 * - POST /api/v1/insights/uptime/endpoints -> addEndpoint()
 *
 * @package Drupal\jaraba_insights_hub\Controller
 */
class UptimeApiController extends ControllerBase {

  /**
   * El servicio de monitoreo de uptime.
   *
   * @var \Drupal\jaraba_insights_hub\Service\UptimeMonitorService
   */
  protected $uptimeMonitor;

  /**
   * El servicio de contexto de tenant.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService
   */
  protected TenantContextService $tenantContext;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->uptimeMonitor = $container->get('jaraba_insights_hub.uptime_monitor');
    $instance->tenantContext = $container->get('ecosistema_jaraba_core.tenant_context');
    return $instance;
  }

  /**
   * Devuelve el estado actual de todos los endpoints monitoreados.
   *
   * GET /api/v1/insights/uptime/status
   *
   * Query params:
   * - date_range: 7d|30d|90d (default: 30d) para calculo de uptime %
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La peticion HTTP.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con estructura {success: bool, data: {endpoints: [...], overall_uptime: float}}.
   */
  public function status(Request $request): JsonResponse {
    try {
      $tenant = $this->tenantContext->getCurrentTenant();
      $tenant_id = $tenant ? (int) $tenant->id() : 0;

      $date_range = $request->query->get('date_range', '30d');
      $allowed_ranges = ['7d', '30d', '90d'];
      if (!in_array($date_range, $allowed_ranges, TRUE)) {
        $date_range = '30d';
      }

      $status = $this->uptimeMonitor->getCurrentStatus($tenant_id, $date_range);

      return new JsonResponse([
        'success' => TRUE,
        'data' => $status,
      ]);
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_insights_hub')->error('Error al obtener estado de uptime: @message', [
        '@message' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'success' => FALSE,
        'message' => $this->t('Error al obtener el estado de disponibilidad.'),
      ], 500);
    }
  }

  /**
   * Lista los incidentes de disponibilidad.
   *
   * GET /api/v1/insights/uptime/incidents
   *
   * Query params:
   * - status: ongoing|resolved (opcional, todos si no se especifica)
   * - date_range: 7d|30d|90d (default: 30d)
   * - endpoint: URL del endpoint para filtrar (opcional)
   * - page: pagina actual (default: 0)
   * - limit: resultados por pagina (default: 20, max: 100)
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La peticion HTTP.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con estructura {success: bool, data: {incidents: [...], total: int}}.
   */
  public function incidents(Request $request): JsonResponse {
    try {
      $tenant = $this->tenantContext->getCurrentTenant();
      $tenant_id = $tenant ? (int) $tenant->id() : 0;

      // Parsear filtros.
      $status = $request->query->get('status');
      $allowed_statuses = ['ongoing', 'resolved'];
      if ($status !== NULL && !in_array($status, $allowed_statuses, TRUE)) {
        $status = NULL;
      }

      $date_range = $request->query->get('date_range', '30d');
      $allowed_ranges = ['7d', '30d', '90d'];
      if (!in_array($date_range, $allowed_ranges, TRUE)) {
        $date_range = '30d';
      }

      $endpoint = $request->query->get('endpoint');
      if ($endpoint !== NULL) {
        $endpoint = mb_substr($endpoint, 0, 500);
      }

      $page = max(0, (int) $request->query->get('page', '0'));
      $limit = max(1, min((int) $request->query->get('limit', '20'), 100));

      $filters = [
        'tenant_id' => $tenant_id,
        'status' => $status,
        'date_range' => $date_range,
        'endpoint' => $endpoint,
        'offset' => $page * $limit,
        'limit' => $limit,
      ];

      $result = $this->uptimeMonitor->getIncidents($filters);

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'incidents' => $result['incidents'],
          'total' => $result['total'],
          'page' => $page,
          'limit' => $limit,
        ],
      ]);
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_insights_hub')->error('Error al listar incidentes de uptime: @message', [
        '@message' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'success' => FALSE,
        'message' => $this->t('Error al obtener la lista de incidentes.'),
      ], 500);
    }
  }

  /**
   * Agrega un nuevo endpoint para monitorear.
   *
   * POST /api/v1/insights/uptime/endpoints
   *
   * Body JSON esperado:
   * {
   *   "endpoint": "https://mi-sitio.com/api/health",
   *   "label": "API Health Check",
   *   "check_interval": 300,
   *   "expected_status_code": 200
   * }
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La peticion HTTP.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con estructura {success: bool, data: {id: int}}.
   */
  public function addEndpoint(Request $request): JsonResponse {
    try {
      $tenant = $this->tenantContext->getCurrentTenant();
      $tenant_id = $tenant ? (int) $tenant->id() : 0;

      if ($tenant_id === 0) {
        return new JsonResponse([
          'success' => FALSE,
          'message' => $this->t('No se pudo determinar el tenant actual.'),
        ], 403);
      }

      $content = json_decode($request->getContent(), TRUE);

      if (!$content || empty($content['endpoint'])) {
        return new JsonResponse([
          'success' => FALSE,
          'message' => $this->t('Datos invalidos: se requiere el campo endpoint.'),
        ], 400);
      }

      // Validar URL del endpoint.
      $endpoint_url = filter_var($content['endpoint'], FILTER_VALIDATE_URL);
      if (!$endpoint_url) {
        return new JsonResponse([
          'success' => FALSE,
          'message' => $this->t('La URL del endpoint no es valida.'),
        ], 400);
      }

      // Validar que el esquema sea HTTPS o HTTP.
      $scheme = parse_url($endpoint_url, PHP_URL_SCHEME);
      if (!in_array($scheme, ['http', 'https'], TRUE)) {
        return new JsonResponse([
          'success' => FALSE,
          'message' => $this->t('Solo se permiten endpoints HTTP o HTTPS.'),
        ], 400);
      }

      // Preparar datos del endpoint.
      $endpoint_data = [
        'tenant_id' => $tenant_id,
        'endpoint' => mb_substr($endpoint_url, 0, 500),
        'label' => mb_substr($content['label'] ?? '', 0, 255),
        'check_interval' => max(60, min((int) ($content['check_interval'] ?? 300), 3600)),
        'expected_status_code' => (int) ($content['expected_status_code'] ?? 200),
      ];

      $result = $this->uptimeMonitor->addEndpoint($endpoint_data);

      if (!$result['success']) {
        return new JsonResponse([
          'success' => FALSE,
          'message' => $result['message'],
        ], $result['code'] ?? 400);
      }

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'id' => $result['id'],
          'endpoint' => $endpoint_data['endpoint'],
        ],
      ]);
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_insights_hub')->error('Error al agregar endpoint de uptime: @message', [
        '@message' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'success' => FALSE,
        'message' => $this->t('Error al agregar el endpoint.'),
      ], 500);
    }
  }

}
