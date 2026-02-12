<?php

declare(strict_types=1);

namespace Drupal\jaraba_insights_hub\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador API para Error Tracking.
 *
 * PROPOSITO:
 * Expone endpoints para recopilar errores del frontend (JS), backend (PHP)
 * y APIs, asi como para consultar y gestionar los errores registrados.
 *
 * FUNCIONALIDADES:
 * - POST para reportar errores (anonimo para JS, autenticado para admin)
 * - GET para listar errores con filtros
 * - POST para resolver errores
 * - Deduplicacion automatica via error_hash
 *
 * RUTAS:
 * - POST /api/v1/insights/errors -> collect()
 * - GET /api/v1/insights/errors/list -> listErrors()
 * - POST /api/v1/insights/errors/{id}/resolve -> resolve()
 *
 * @package Drupal\jaraba_insights_hub\Controller
 */
class ErrorTrackingApiController extends ControllerBase {

  /**
   * El servicio de error tracking.
   *
   * @var \Drupal\jaraba_insights_hub\Service\ErrorTrackingService
   */
  protected $errorTracking;

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
    $instance->errorTracking = $container->get('jaraba_insights_hub.error_tracking');
    $instance->tenantContext = $container->get('ecosistema_jaraba_core.tenant_context');
    return $instance;
  }

  /**
   * Recopila un error reportado desde el frontend o backend.
   *
   * POST /api/v1/insights/errors
   *
   * Endpoint anonimo para captura de errores JS. Los errores se
   * deduplican por hash (message + file + line) e incrementan
   * el campo occurrences cuando se repiten.
   *
   * Body JSON esperado:
   * {
   *   "error_type": "js|php|api",
   *   "severity": "error|warning|info",
   *   "message": "Error message",
   *   "stack_trace": "...",
   *   "file_path": "/js/app.js",
   *   "line_number": 42,
   *   "url": "/pagina-donde-ocurrio",
   *   "tenant_id": 1
   * }
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La peticion HTTP.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con estructura {success: bool, data: {id: int, is_new: bool}}.
   */
  public function collect(Request $request): JsonResponse {
    try {
      $content = json_decode($request->getContent(), TRUE);

      if (!$content || empty($content['message'])) {
        return new JsonResponse([
          'success' => FALSE,
          'message' => $this->t('Datos invalidos: se requiere al menos el campo message.'),
        ], 400);
      }

      // Validar error_type.
      $allowed_types = ['js', 'php', 'api'];
      $error_type = $content['error_type'] ?? 'js';
      if (!in_array($error_type, $allowed_types, TRUE)) {
        $error_type = 'js';
      }

      // Validar severity.
      $allowed_severities = ['error', 'warning', 'info'];
      $severity = $content['severity'] ?? 'error';
      if (!in_array($severity, $allowed_severities, TRUE)) {
        $severity = 'error';
      }

      // Determinar tenant_id.
      $tenant_id = (int) ($content['tenant_id'] ?? 0);
      if ($tenant_id === 0) {
        $tenant = $this->tenantContext->getCurrentTenant();
        $tenant_id = $tenant ? (int) $tenant->id() : 0;
      }

      // Preparar datos del error.
      $error_data = [
        'tenant_id' => $tenant_id,
        'error_type' => $error_type,
        'severity' => $severity,
        'message' => mb_substr($content['message'], 0, 5000),
        'stack_trace' => mb_substr($content['stack_trace'] ?? '', 0, 10000),
        'file_path' => mb_substr($content['file_path'] ?? '', 0, 500),
        'line_number' => (int) ($content['line_number'] ?? 0),
        'url' => mb_substr($content['url'] ?? '', 0, 500),
      ];

      // Registrar el error (con deduplicacion).
      $result = $this->errorTracking->recordError($error_data);

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'id' => $result['id'],
          'is_new' => $result['is_new'],
          'occurrences' => $result['occurrences'],
        ],
      ]);
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_insights_hub')->error('Error al registrar error tracking: @message', [
        '@message' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'success' => FALSE,
        'message' => $this->t('Error al registrar el error.'),
      ], 500);
    }
  }

  /**
   * Lista los errores registrados con filtros opcionales.
   *
   * GET /api/v1/insights/errors/list
   *
   * Query params:
   * - status: open|acknowledged|resolved|ignored (default: open)
   * - error_type: js|php|api (opcional)
   * - severity: error|warning|info (opcional)
   * - date_range: 7d|30d|90d (default: 30d)
   * - page: pagina actual (default: 0)
   * - limit: resultados por pagina (default: 20, max: 100)
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La peticion HTTP.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con estructura {success: bool, data: {errors: [...], total: int}}.
   */
  public function listErrors(Request $request): JsonResponse {
    try {
      $tenant = $this->tenantContext->getCurrentTenant();
      $tenant_id = $tenant ? (int) $tenant->id() : 0;

      // Parsear filtros.
      $status = $request->query->get('status', 'open');
      $allowed_statuses = ['open', 'acknowledged', 'resolved', 'ignored'];
      if (!in_array($status, $allowed_statuses, TRUE)) {
        $status = 'open';
      }

      $error_type = $request->query->get('error_type');
      $allowed_types = ['js', 'php', 'api'];
      if ($error_type !== NULL && !in_array($error_type, $allowed_types, TRUE)) {
        $error_type = NULL;
      }

      $severity = $request->query->get('severity');
      $allowed_severities = ['error', 'warning', 'info'];
      if ($severity !== NULL && !in_array($severity, $allowed_severities, TRUE)) {
        $severity = NULL;
      }

      $date_range = $request->query->get('date_range', '30d');
      $allowed_ranges = ['7d', '30d', '90d'];
      if (!in_array($date_range, $allowed_ranges, TRUE)) {
        $date_range = '30d';
      }

      $page = max(0, (int) $request->query->get('page', '0'));
      $limit = max(1, min((int) $request->query->get('limit', '20'), 100));

      // Construir filtros.
      $filters = [
        'tenant_id' => $tenant_id,
        'status' => $status,
        'error_type' => $error_type,
        'severity' => $severity,
        'date_range' => $date_range,
        'offset' => $page * $limit,
        'limit' => $limit,
      ];

      $result = $this->errorTracking->getErrors($filters);

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'errors' => $result['errors'],
          'total' => $result['total'],
          'page' => $page,
          'limit' => $limit,
        ],
      ]);
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_insights_hub')->error('Error al listar errores: @message', [
        '@message' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'success' => FALSE,
        'message' => $this->t('Error al obtener la lista de errores.'),
      ], 500);
    }
  }

  /**
   * Marca un error como resuelto.
   *
   * POST /api/v1/insights/errors/{id}/resolve
   *
   * Body JSON opcional:
   * {
   *   "resolution_note": "Descripcion de la correccion aplicada"
   * }
   *
   * @param int $id
   *   ID de la entidad InsightsErrorLog.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La peticion HTTP.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con estructura {success: bool, data: {id: int, status: string}}.
   */
  public function resolve(int $id, Request $request): JsonResponse {
    try {
      $tenant = $this->tenantContext->getCurrentTenant();
      $tenant_id = $tenant ? (int) $tenant->id() : 0;

      // Parsear nota de resolucion si existe.
      $content = json_decode($request->getContent(), TRUE);
      $resolution_note = mb_substr($content['resolution_note'] ?? '', 0, 5000);

      $result = $this->errorTracking->resolveError($id, $tenant_id, $resolution_note);

      if (!$result['success']) {
        $status_code = $result['code'] ?? 404;
        return new JsonResponse([
          'success' => FALSE,
          'message' => $result['message'],
        ], $status_code);
      }

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'id' => $id,
          'status' => 'resolved',
        ],
      ]);
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_insights_hub')->error('Error al resolver error @id: @message', [
        '@id' => $id,
        '@message' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'success' => FALSE,
        'message' => $this->t('Error al resolver el error.'),
      ], 500);
    }
  }

}
