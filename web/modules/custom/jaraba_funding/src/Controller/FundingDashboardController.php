<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_funding\Service\Intelligence\FundingCacheService;
use Drupal\jaraba_funding\Service\Intelligence\FundingMatchingEngine;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador del dashboard frontend de Funding Intelligence.
 *
 * PROPOSITO:
 * Renderiza el dashboard principal de subvenciones para el usuario final
 * (no admin) y expone los endpoints API de busqueda, matches, detalle
 * de convocatoria y estadisticas del dashboard.
 *
 * FUNCIONALIDADES:
 * - Dashboard frontend con busqueda, matches, calendario y copilot widget
 * - API de busqueda de convocatorias con filtros avanzados
 * - API de matches personalizados por suscripcion del usuario
 * - API de detalle de convocatoria con comprobacion de elegibilidad
 * - API de estadisticas del dashboard
 * - Multi-tenant: datos filtrados por tenant del usuario actual
 *
 * RUTAS:
 * - GET /funding -> dashboard()
 * - GET /api/v1/funding/calls -> apiSearch()
 * - GET /api/v1/funding/matches -> apiMatches()
 * - GET /api/v1/funding/calls/{call_id} -> apiCallDetail()
 * - GET /api/v1/funding/stats -> apiStats()
 *
 * @package Drupal\jaraba_funding\Controller
 */
class FundingDashboardController extends ControllerBase {

  /**
   * El gestor de tipos de entidad.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * El usuario actual.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * El servicio de cache de funding.
   *
   * @var \Drupal\jaraba_funding\Service\Intelligence\FundingCacheService
   */
  protected FundingCacheService $fundingCache;

  /**
   * El motor de matching de funding.
   *
   * @var \Drupal\jaraba_funding\Service\Intelligence\FundingMatchingEngine
   */
  protected FundingMatchingEngine $matchingEngine;

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
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->currentUser = $container->get('current_user');
    $instance->fundingCache = $container->get('jaraba_funding.cache');
    $instance->matchingEngine = $container->get('jaraba_funding.matching_engine');
    $instance->tenantContext = $container->get('ecosistema_jaraba_core.tenant_context');
    return $instance;
  }

  /**
   * Renderiza el dashboard principal de Funding Intelligence.
   *
   * Pagina frontend (no admin) que muestra el buscador de convocatorias,
   * matches personalizados, calendario de plazos y widget del copilot.
   *
   * @return array
   *   Render array con #theme => 'page__funding'.
   */
  public function dashboard(): array {
    $tenant = $this->tenantContext->getCurrentTenant();
    $tenant_id = $tenant ? (int) $tenant->id() : 0;

    return [
      '#theme' => 'page__funding',
      '#tenant_id' => $tenant_id,
      '#labels' => [
        'title' => $this->t('Subvenciones'),
        'subtitle' => $this->t('Inteligencia de subvenciones con matching IA y alertas personalizadas'),
        'search_placeholder' => $this->t('Buscar convocatorias...'),
        'matches_panel' => $this->t('Matches personalizados'),
        'calendar_panel' => $this->t('Calendario de plazos'),
        'copilot_panel' => $this->t('Copilot de subvenciones'),
        'no_results' => $this->t('No se encontraron convocatorias para los filtros seleccionados.'),
        'loading' => $this->t('Cargando convocatorias...'),
        'view_detail' => $this->t('Ver detalle'),
        'subscribe' => $this->t('Suscribirse a alertas'),
      ],
      '#urls' => [
        'api_search' => Url::fromRoute('jaraba_funding.api.search')->toString(),
        'api_matches' => Url::fromRoute('jaraba_funding.api.matches')->toString(),
        'api_stats' => Url::fromRoute('jaraba_funding.api.stats')->toString(),
        'copilot' => Url::fromRoute('jaraba_funding.copilot')->toString(),
        'settings' => Url::fromRoute('jaraba_funding.settings')->toString(),
      ],
      '#attached' => [
        'library' => [
          'jaraba_funding/funding-dashboard',
        ],
        'drupalSettings' => [
          'jarabaFunding' => [
            'tenantId' => $tenant_id,
            'apiSearchUrl' => Url::fromRoute('jaraba_funding.api.search')->toString(),
            'apiMatchesUrl' => Url::fromRoute('jaraba_funding.api.matches')->toString(),
            'apiStatsUrl' => Url::fromRoute('jaraba_funding.api.stats')->toString(),
          ],
        ],
      ],
    ];
  }

  /**
   * Endpoint API: Busqueda de convocatorias con filtros.
   *
   * GET /api/v1/funding/calls
   *
   * Busca convocatorias de subvenciones aplicando filtros por region,
   * sector, tipo, plazo, estado y texto libre. Soporta paginacion
   * y ordenamiento.
   *
   * Query params:
   * - region: string (filtro por region)
   * - sector: string (filtro por sector)
   * - call_type: string (subvencion, ayuda, prestamo, incentivo, premio)
   * - deadline_from: string (ISO date, plazo desde)
   * - deadline_to: string (ISO date, plazo hasta)
   * - status: string (open, closed, pending_resolution, resolved, draft)
   * - query: string (busqueda de texto libre)
   * - sort: string (deadline_asc, deadline_desc, relevance, recent)
   * - page: int (pagina, default 1)
   * - limit: int (resultados por pagina, default 20, max 100)
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La peticion HTTP con query params.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con estructura {success: bool, data: {calls: [...], total: int, page: int}}.
   */
  public function apiSearch(Request $request): JsonResponse {
    try {
      $tenant = $this->tenantContext->getCurrentTenant();
      $tenant_id = $tenant ? (int) $tenant->id() : 0;

      // Parsear filtros de query params.
      $filters = [];

      $region = $request->query->get('region');
      if (!empty($region)) {
        $filters['region'] = mb_substr(trim($region), 0, 128);
      }

      $sector = $request->query->get('sector');
      if (!empty($sector)) {
        $filters['sector'] = mb_substr(trim($sector), 0, 128);
      }

      $call_type = $request->query->get('call_type');
      $allowed_call_types = ['subvencion', 'ayuda', 'prestamo', 'incentivo', 'premio'];
      if (!empty($call_type) && in_array($call_type, $allowed_call_types, TRUE)) {
        $filters['call_type'] = $call_type;
      }

      $deadline_from = $request->query->get('deadline_from');
      if (!empty($deadline_from) && strtotime($deadline_from) !== FALSE) {
        $filters['deadline_from'] = strtotime($deadline_from);
      }

      $deadline_to = $request->query->get('deadline_to');
      if (!empty($deadline_to) && strtotime($deadline_to) !== FALSE) {
        $filters['deadline_to'] = strtotime($deadline_to);
      }

      $status = $request->query->get('status');
      $allowed_statuses = ['open', 'closed', 'pending_resolution', 'resolved', 'draft'];
      if (!empty($status) && in_array($status, $allowed_statuses, TRUE)) {
        $filters['status'] = $status;
      }

      $query_text = $request->query->get('query');
      if (!empty($query_text)) {
        $filters['query'] = mb_substr(trim($query_text), 0, 500);
      }

      // Ordenamiento.
      $sort = $request->query->get('sort', 'deadline_asc');
      $allowed_sorts = ['deadline_asc', 'deadline_desc', 'relevance', 'recent'];
      if (!in_array($sort, $allowed_sorts, TRUE)) {
        $sort = 'deadline_asc';
      }

      // Paginacion.
      $page = max(1, (int) $request->query->get('page', 1));
      $limit = min(100, max(1, (int) $request->query->get('limit', 20)));

      // Buscar convocatorias via cache service.
      $result = $this->fundingCache->searchCalls($tenant_id, $filters, $sort, $page, $limit);

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'calls' => $result['calls'] ?? [],
          'total' => $result['total'] ?? 0,
          'page' => $page,
        ],
      ]);
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_funding')->error('Error al buscar convocatorias: @message', [
        '@message' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'success' => FALSE,
        'message' => $this->t('Error al buscar convocatorias.'),
      ], 500);
    }
  }

  /**
   * Endpoint API: Matches personalizados del usuario actual.
   *
   * GET /api/v1/funding/matches
   *
   * Devuelve los matches entre las suscripciones del usuario actual
   * y las convocatorias abiertas, ordenados por puntuacion de matching.
   *
   * Query params:
   * - min_score: float (puntuacion minima, default: threshold de config)
   * - user_interest: string (filtro por interes del usuario)
   * - sort: string (score_desc, deadline_asc)
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La peticion HTTP con query params.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con estructura {success: bool, data: {matches: [...], total: int}}.
   */
  public function apiMatches(Request $request): JsonResponse {
    try {
      $tenant = $this->tenantContext->getCurrentTenant();
      $tenant_id = $tenant ? (int) $tenant->id() : 0;
      $uid = (int) $this->currentUser->id();

      // Parsear filtros.
      $min_score = $request->query->get('min_score');
      $min_score = $min_score !== NULL ? max(0.0, min(100.0, (float) $min_score)) : NULL;

      $user_interest = $request->query->get('user_interest');
      if (!empty($user_interest)) {
        $user_interest = mb_substr(trim($user_interest), 0, 128);
      }

      $sort = $request->query->get('sort', 'score_desc');
      $allowed_sorts = ['score_desc', 'deadline_asc'];
      if (!in_array($sort, $allowed_sorts, TRUE)) {
        $sort = 'score_desc';
      }

      // Obtener matches via matching engine.
      $result = $this->matchingEngine->getMatchesForUser($tenant_id, $uid, [
        'min_score' => $min_score,
        'user_interest' => $user_interest,
        'sort' => $sort,
      ]);

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'matches' => $result['matches'] ?? [],
          'total' => $result['total'] ?? 0,
        ],
      ]);
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_funding')->error('Error al obtener matches: @message', [
        '@message' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'success' => FALSE,
        'message' => $this->t('Error al obtener los matches de subvenciones.'),
      ], 500);
    }
  }

  /**
   * Endpoint API: Detalle de convocatoria con elegibilidad.
   *
   * GET /api/v1/funding/calls/{call_id}
   *
   * Devuelve el detalle completo de una convocatoria, incluyendo
   * la comprobacion de elegibilidad del usuario actual si tiene
   * suscripciones activas.
   *
   * @param int $call_id
   *   ID de la convocatoria.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con estructura {success: bool, data: {call: {...}, eligibility: {...}}}.
   */
  public function apiCallDetail(int $call_id): JsonResponse {
    try {
      $tenant = $this->tenantContext->getCurrentTenant();
      $tenant_id = $tenant ? (int) $tenant->id() : 0;
      $uid = (int) $this->currentUser->id();

      // Cargar la convocatoria.
      $storage = $this->entityTypeManager->getStorage('funding_call');
      $call = $storage->load($call_id);

      if (!$call) {
        return new JsonResponse([
          'success' => FALSE,
          'message' => $this->t('Convocatoria no encontrada.'),
        ], 404);
      }

      // Verificar que la convocatoria pertenece al tenant actual.
      $call_tenant_id = (int) $call->get('tenant_id')->target_id;
      if ($call_tenant_id !== $tenant_id && $tenant_id !== 0) {
        return new JsonResponse([
          'success' => FALSE,
          'message' => $this->t('Convocatoria no encontrada.'),
        ], 404);
      }

      // Obtener detalle y elegibilidad via matching engine.
      $call_data = $this->fundingCache->getCallDetail($call_id, $tenant_id);
      $eligibility = $this->matchingEngine->checkEligibility($call_id, $uid, $tenant_id);

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'call' => $call_data,
          'eligibility' => $eligibility,
        ],
      ]);
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_funding')->error('Error al obtener detalle de convocatoria @id: @message', [
        '@id' => $call_id,
        '@message' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'success' => FALSE,
        'message' => $this->t('Error al obtener el detalle de la convocatoria.'),
      ], 500);
    }
  }

  /**
   * Endpoint API: Estadisticas del dashboard de funding.
   *
   * GET /api/v1/funding/stats
   *
   * Devuelve estadisticas agregadas para el dashboard: total de
   * convocatorias abiertas, nuevas esta semana, matches del usuario,
   * puntuacion media y proximos plazos.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La peticion HTTP.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con estructura {success: bool, data: {total_calls_open, new_this_week, matches_count, avg_score, upcoming_deadlines}}.
   */
  public function apiStats(Request $request): JsonResponse {
    try {
      $tenant = $this->tenantContext->getCurrentTenant();
      $tenant_id = $tenant ? (int) $tenant->id() : 0;
      $uid = (int) $this->currentUser->id();

      // Obtener estadisticas via cache service.
      $stats = $this->fundingCache->getDashboardStats($tenant_id, $uid);

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'total_calls_open' => $stats['total_calls_open'] ?? 0,
          'new_this_week' => $stats['new_this_week'] ?? 0,
          'matches_count' => $stats['matches_count'] ?? 0,
          'avg_score' => $stats['avg_score'] ?? 0.0,
          'upcoming_deadlines' => $stats['upcoming_deadlines'] ?? [],
        ],
      ]);
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_funding')->error('Error al obtener estadisticas de funding: @message', [
        '@message' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'success' => FALSE,
        'message' => $this->t('Error al obtener las estadisticas.'),
      ], 500);
    }
  }

}
