<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Service\CoordinadorHubService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller API para el Hub Coordinador Andalucia +ei.
 *
 * Endpoints JSON para operaciones CRUD de solicitudes, participantes,
 * sesiones y KPIs del hub operativo.
 *
 * Seguridad:
 * - CSRF-API-001: POST routes con _csrf_request_header_token: 'TRUE'
 * - TENANT-001: Todas las queries filtran por tenant
 * - Permiso: 'administer andalucia ei' en todas las rutas
 * - API-WHITELIST-001: Validacion de input contra listas permitidas
 */
class CoordinadorHubApiController extends ControllerBase {

  /**
   * Fases validas para transiciones (API-WHITELIST-001).
   */
  private const ALLOWED_PHASES = ['acogida', 'diagnostico', 'atencion', 'insercion', 'seguimiento', 'baja'];

  /**
   * Estados validos para filtro de solicitudes (API-WHITELIST-001).
   */
  private const ALLOWED_ESTADOS = ['pendiente', 'contactado', 'admitido', 'rechazado', 'lista_espera'];

  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    protected readonly CoordinadorHubService $hubService,
    protected readonly LoggerInterface $logger,
    protected readonly ?object $tenantContext = NULL,
  ) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('jaraba_andalucia_ei.coordinador_hub'),
      $container->get('logger.channel.jaraba_andalucia_ei'),
      $container->get('ecosistema_jaraba_core.tenant_context', ContainerInterface::NULL_ON_INVALID_REFERENCE),
    );
  }

  /**
   * GET /api/v1/andalucia-ei/hub/solicitudes
   */
  public function listSolicitudes(Request $request): JsonResponse {
    $estado = $request->query->get('estado', '');
    $limit = min((int) $request->query->get('limit', '20'), 100);
    $offset = max((int) $request->query->get('offset', '0'), 0);

    // API-WHITELIST-001: validar estado.
    if ($estado !== '' && !in_array($estado, self::ALLOWED_ESTADOS, TRUE)) {
      return new JsonResponse([
        'success' => FALSE,
        'message' => 'Estado no valido.',
        'data' => NULL,
      ], 400);
    }

    $tenantId = $this->resolveTenantId();
    $result = $this->hubService->getSolicitudes($tenantId, $estado, $limit, $offset);

    return new JsonResponse([
      'success' => TRUE,
      'data' => $result,
      'message' => '',
    ]);
  }

  /**
   * POST /api/v1/andalucia-ei/hub/solicitud/{id}/approve
   */
  public function approveSolicitud(int $id): JsonResponse {
    $tenantId = $this->resolveTenantId();
    $result = $this->hubService->approveSolicitud($id, $tenantId);

    return new JsonResponse([
      'success' => $result['success'],
      'data' => ['participante_id' => $result['participante_id'] ?? NULL],
      'message' => $result['message'],
    ], $result['success'] ? 200 : 400);
  }

  /**
   * POST /api/v1/andalucia-ei/hub/solicitud/{id}/reject
   */
  public function rejectSolicitud(int $id, Request $request): JsonResponse {
    $content = $request->getContent();
    $data = json_decode($content, TRUE);
    $reason = '';

    if (is_array($data) && isset($data['reason'])) {
      $reason = mb_substr(strip_tags((string) $data['reason']), 0, 1000);
    }

    $result = $this->hubService->rejectSolicitud($id, $reason);

    return new JsonResponse([
      'success' => $result['success'],
      'data' => NULL,
      'message' => $result['message'],
    ], $result['success'] ? 200 : 400);
  }

  /**
   * POST /api/v1/andalucia-ei/hub/participant/{id}/phase
   */
  public function changePhase(int $id, Request $request): JsonResponse {
    $content = $request->getContent();
    $data = json_decode($content, TRUE);

    if (!is_array($data) || !isset($data['phase'])) {
      return new JsonResponse([
        'success' => FALSE,
        'message' => 'Se requiere el campo "phase".',
        'data' => NULL,
      ], 400);
    }

    $phase = (string) $data['phase'];

    // API-WHITELIST-001: validar fase.
    if (!in_array($phase, self::ALLOWED_PHASES, TRUE)) {
      return new JsonResponse([
        'success' => FALSE,
        'message' => 'Fase no valida. Valores permitidos: ' . implode(', ', self::ALLOWED_PHASES),
        'data' => NULL,
      ], 400);
    }

    $result = $this->hubService->changeParticipantPhase($id, $phase);

    return new JsonResponse([
      'success' => $result['success'],
      'data' => NULL,
      'message' => $result['message'],
    ], $result['success'] ? 200 : 400);
  }

  /**
   * GET /api/v1/andalucia-ei/hub/participants
   */
  public function listParticipants(Request $request): JsonResponse {
    $fase = $request->query->get('fase', '');
    $search = $request->query->get('search', '');
    $limit = min((int) $request->query->get('limit', '20'), 100);
    $offset = max((int) $request->query->get('offset', '0'), 0);

    // API-WHITELIST-001: validar fase.
    if ($fase !== '' && !in_array($fase, self::ALLOWED_PHASES, TRUE)) {
      return new JsonResponse([
        'success' => FALSE,
        'message' => 'Fase no valida.',
        'data' => NULL,
      ], 400);
    }

    $tenantId = $this->resolveTenantId();
    $result = $this->hubService->getParticipants($tenantId, $fase, $search, $limit, $offset);

    return new JsonResponse([
      'success' => TRUE,
      'data' => $result,
      'message' => '',
    ]);
  }

  /**
   * GET /api/v1/andalucia-ei/hub/sessions
   */
  public function listSessions(Request $request): JsonResponse {
    $days = min(max((int) $request->query->get('days', '7'), 1), 90);
    $tenantId = $this->resolveTenantId();
    $sessions = $this->hubService->getUpcomingSessions($tenantId, $days);

    return new JsonResponse([
      'success' => TRUE,
      'data' => ['sessions' => $sessions],
      'message' => '',
    ]);
  }

  /**
   * GET /api/v1/andalucia-ei/hub/kpis
   */
  public function getKpis(): JsonResponse {
    $tenantId = $this->resolveTenantId();
    $kpis = $this->hubService->getHubKpis($tenantId);

    return new JsonResponse([
      'success' => TRUE,
      'data' => $kpis,
      'message' => '',
    ]);
  }

  /**
   * Resuelve tenant Group ID del contexto actual.
   */
  private function resolveTenantId(): ?int {
    if (!$this->tenantContext) {
      return NULL;
    }
    try {
      $tenant = $this->tenantContext->getCurrentTenant();
      return $tenant ? (int) $tenant->id() : NULL;
    }
    catch (\Throwable) {
      return NULL;
    }
  }

}
