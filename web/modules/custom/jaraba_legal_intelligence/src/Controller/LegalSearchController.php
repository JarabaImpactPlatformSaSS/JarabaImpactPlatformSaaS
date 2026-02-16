<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_legal_intelligence\Service\LegalAlertService;
use Drupal\jaraba_legal_intelligence\Service\LegalCitationService;
use Drupal\jaraba_legal_intelligence\Service\LegalCopilotBridgeService;
use Drupal\jaraba_legal_intelligence\Service\LegalDigestService;
use Drupal\jaraba_legal_intelligence\Service\LegalMergeRankService;
use Drupal\jaraba_legal_intelligence\Service\LegalSearchService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador del Legal Intelligence Hub: busqueda y API REST.
 *
 * ESTRUCTURA:
 * Controlador que expone la pagina de busqueda semantica del frontend
 * y los endpoints REST /api/v1/legal/* para busqueda, alertas y digest.
 * El frontend carga como pagina Zero-Region con render array y pasa los
 * datos al JS via drupalSettings. Los endpoints API devuelven JsonResponse.
 *
 * LOGICA:
 * La pagina de busqueda (search()) renderiza el formulario inicial con las
 * facetas disponibles y pasa la URL de la API de busqueda al JS. El JS
 * ejecuta busquedas via POST a apiSearch(), que delega en LegalSearchService
 * para la busqueda semantica en Qdrant y opcionalmente aplica merge & rank
 * cuando el scope es 'all' (nacional + UE). Los endpoints de alertas (CRUD)
 * delegan en LegalAlertService y el preview del digest en LegalDigestService.
 *
 * RELACIONES:
 * - LegalSearchController -> LegalSearchService: ejecuta busquedas semanticas
 *   en Qdrant con filtros facetados y obtiene facetas disponibles.
 * - LegalSearchController -> LegalMergeRankService: aplica boost UE y frescura
 *   cuando el scope de busqueda es 'all' (nacional + europeo).
 * - LegalSearchController -> ConfigFactoryInterface: lee configuracion del
 *   modulo para parametros de busqueda y limites.
 * - LegalSearchController <- jaraba_legal.search (ruta): pagina de busqueda
 *   frontend con permiso 'search legal resolutions'.
 * - LegalSearchController <- jaraba_legal.api.search (ruta POST): endpoint API
 *   de busqueda con permiso 'access legal api'.
 * - LegalSearchController <- jaraba_legal.api.alerts (ruta CRUD): endpoint API
 *   de alertas con permiso 'manage legal alerts'.
 * - LegalSearchController <- jaraba_legal.api.digest_preview (ruta GET): preview
 *   del digest semanal con permiso 'manage legal alerts'.
 * - LegalSearchController -> LegalCopilotBridgeService: busqueda y vinculacion
 *   desde el copilot conversacional (FASE 6).
 * - LegalSearchController -> LegalCitationService: referencias de expediente.
 * - LegalSearchController <- jaraba_legal.api.copilot_search (ruta POST):
 *   busqueda legal desde copilot.
 * - LegalSearchController <- jaraba_legal.api.copilot_attach (ruta POST):
 *   vincular resolucion a expediente desde copilot.
 * - LegalSearchController <- jaraba_legal.api.expediente_references (ruta GET):
 *   listar referencias vinculadas a un expediente.
 */
class LegalSearchController extends ControllerBase {

  /**
   * Construye una nueva instancia del controlador de busqueda legal.
   *
   * @param \Drupal\jaraba_legal_intelligence\Service\LegalSearchService $searchService
   *   Servicio de busqueda semantica con facetas en Qdrant.
   * @param \Drupal\jaraba_legal_intelligence\Service\LegalMergeRankService $mergeRankService
   *   Servicio de fusion y re-ranking de resultados nacionales + UE.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Factoria de configuracion para leer parametros del modulo.
   * @param \Drupal\jaraba_legal_intelligence\Service\LegalAlertService $alertService
   *   Servicio de gestion de alertas inteligentes.
   * @param \Drupal\jaraba_legal_intelligence\Service\LegalDigestService $digestService
   *   Servicio de generacion de digest semanal.
   * @param \Drupal\jaraba_legal_intelligence\Service\LegalCopilotBridgeService $copilotBridge
   *   Servicio puente entre Legal Intelligence Hub y Copilot.
   * @param \Drupal\jaraba_legal_intelligence\Service\LegalCitationService $citationService
   *   Servicio de citas y referencias legales.
   */
  public function __construct(
    protected LegalSearchService $searchService,
    protected LegalMergeRankService $mergeRankService,
    protected ConfigFactoryInterface $configFactory,
    protected LegalAlertService $alertService,
    protected LegalDigestService $digestService,
    protected LegalCopilotBridgeService $copilotBridge,
    protected LegalCitationService $citationService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_legal_intelligence.search'),
      $container->get('jaraba_legal_intelligence.merge_rank'),
      $container->get('config.factory'),
      $container->get('jaraba_legal_intelligence.alerts'),
      $container->get('jaraba_legal_intelligence.digest'),
      $container->get('jaraba_legal_intelligence.copilot_bridge'),
      $container->get('jaraba_legal_intelligence.citations'),
    );
  }

  // =========================================================================
  // FRONTEND: Pagina de busqueda semantica.
  // =========================================================================

  /**
   * Renderiza la pagina principal de busqueda del Legal Intelligence Hub.
   *
   * Obtiene las facetas disponibles del servicio de busqueda y prepara
   * un render array con el theme 'legal_search_page'. Pasa la URL de la
   * API de busqueda al JS via drupalSettings para que el frontend ejecute
   * busquedas asincronas contra apiSearch().
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Objeto request HTTP de Symfony.
   *
   * @return array
   *   Render array de Drupal con el theme 'legal_search_page'.
   */
  public function search(Request $request): array {
    $facets = $this->searchService->getAvailableFacets();

    return [
      '#theme' => 'legal_search_page',
      '#tenant' => NULL,
      '#labels' => [
        'page_title' => $this->t('Legal Intelligence Hub'),
        'search_placeholder' => $this->t('Search resolutions, case law, regulations...'),
        'search_button' => $this->t('Search'),
        'filters_label' => $this->t('Filters'),
        'no_results' => $this->t('No results found. Try adjusting your filters or search terms.'),
        'loading' => $this->t('Searching...'),
        'scope_all' => $this->t('All sources'),
        'scope_national' => $this->t('National (ES)'),
        'scope_eu' => $this->t('European (EU)'),
      ],
      '#urls' => [
        'search' => '/api/v1/legal/search',
        'alerts' => '/api/v1/legal/alerts',
        'digest_preview' => '/api/v1/legal/digest/preview',
      ],
      '#facets' => $facets,
      '#attached' => [
        'library' => [
          'jaraba_legal_intelligence/legal.search',
          'jaraba_legal_intelligence/legal.results',
          'jaraba_legal_intelligence/legal.facets',
        ],
        'drupalSettings' => [
          'legalIntelligence' => [
            'searchUrl' => '/api/v1/legal/search',
            'alertsUrl' => '/api/v1/legal/alerts',
            'digestPreviewUrl' => '/api/v1/legal/digest/preview',
            'facets' => $facets,
          ],
        ],
      ],
    ];
  }

  // =========================================================================
  // API REST: Busqueda semantica.
  // =========================================================================

  /**
   * Endpoint API POST /api/v1/legal/search — Busqueda semantica.
   *
   * Lee los parametros de busqueda del cuerpo JSON de la peticion,
   * ejecuta la busqueda semantica via LegalSearchService y opcionalmente
   * aplica merge & rank si el scope es 'all'. Devuelve resultados con
   * facetas actualizadas para refinamiento interactivo.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Objeto request con cuerpo JSON:
   *   - query: string — Texto de busqueda.
   *   - filters: object — Filtros facetados opcionales.
   *   - scope: string — 'all', 'national' o 'eu'.
   *   - limit: int — Maximo de resultados (default 20).
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con resultados, total y facetas.
   */
  public function apiSearch(Request $request): JsonResponse {
    try {
      $body = json_decode($request->getContent(), TRUE);

      if (!is_array($body)) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'Invalid JSON body.',
        ], 400);
      }

      $query = trim((string) ($body['query'] ?? ''));
      $filters = (array) ($body['filters'] ?? []);
      $scope = (string) ($body['scope'] ?? 'all');
      $limit = (int) ($body['limit'] ?? 20);

      // Validar que la query no este vacia.
      if ($query === '') {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'The search query cannot be empty.',
        ], 400);
      }

      // Validar scope.
      if (!in_array($scope, ['all', 'national', 'eu'], TRUE)) {
        $scope = 'all';
      }

      // Limitar el rango de resultados.
      $limit = max(1, min($limit, 100));

      // Ejecutar busqueda semantica.
      $searchResult = $this->searchService->search($query, $filters, $scope, $limit);

      if (!$searchResult['success']) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => $searchResult['error'] ?? 'Search failed.',
        ], 500);
      }

      $results = $searchResult['results'] ?? [];

      // Si el scope es 'all', aplicar merge & rank con boost UE y frescura.
      if ($scope === 'all' && !empty($results)) {
        $results = $this->mergeRankService->applyBoosts($results);
      }

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'results' => $results,
          'total' => count($results),
          'facets' => $searchResult['facets'] ?? [],
        ],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => 'An unexpected error occurred during search.',
      ], 500);
    }
  }

  // =========================================================================
  // API REST: Alertas inteligentes (FASE 5 stub).
  // =========================================================================

  /**
   * Endpoint API /api/v1/legal/alerts — CRUD de alertas inteligentes.
   *
   * Gestiona las alertas del usuario actual mediante un switch sobre el
   * metodo HTTP:
   * - GET: Lista todas las alertas del usuario con metadatos.
   * - POST: Crea una nueva alerta (verifica limite del plan).
   * - PATCH: Alterna estado activo/inactivo de una alerta.
   * - DELETE: Elimina una alerta del usuario.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Objeto request HTTP con metodo GET, POST, PATCH o DELETE.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con datos de alertas o confirmacion de operacion.
   */
  public function apiAlerts(Request $request): JsonResponse {
    $method = $request->getMethod();
    $currentUser = $this->currentUser();
    $providerId = (int) $currentUser->id();

    if ($providerId === 0) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Authentication required.',
      ], 401);
    }

    switch ($method) {
      case 'GET':
        return $this->handleGetAlerts($providerId);

      case 'POST':
        return $this->handleCreateAlert($request, $providerId);

      case 'PATCH':
        return $this->handleUpdateAlert($request, $providerId);

      case 'DELETE':
        return $this->handleDeleteAlert($request, $providerId);

      default:
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'Method not allowed.',
        ], 405);
    }
  }

  /**
   * Maneja GET /api/v1/legal/alerts — Listar alertas del usuario.
   */
  private function handleGetAlerts(int $providerId): JsonResponse {
    $alerts = $this->alertService->listAlerts($providerId);

    $data = [];
    foreach ($alerts as $alert) {
      $data[] = [
        'id' => (int) $alert->id(),
        'label' => $alert->get('label')->value ?? '',
        'alert_type' => $alert->get('alert_type')->value ?? '',
        'severity' => $alert->get('severity')->value ?? 'medium',
        'is_active' => (bool) $alert->get('is_active')->value,
        'filter_sources' => $alert->getFilterSources(),
        'filter_topics' => $alert->getFilterTopics(),
        'filter_jurisdictions' => $alert->getFilterJurisdictions(),
        'channels' => $alert->getChannels(),
        'trigger_count' => (int) ($alert->get('trigger_count')->value ?? 0),
        'last_triggered' => $alert->get('last_triggered')->value ? (int) $alert->get('last_triggered')->value : NULL,
        'created' => (int) $alert->get('created')->value,
      ];
    }

    return new JsonResponse([
      'success' => TRUE,
      'data' => $data,
      'meta' => [
        'total' => count($data),
      ],
    ]);
  }

  /**
   * Maneja POST /api/v1/legal/alerts — Crear nueva alerta.
   */
  private function handleCreateAlert(Request $request, int $providerId): JsonResponse {
    $body = json_decode($request->getContent(), TRUE);

    if (!is_array($body)) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Invalid JSON body.',
      ], 400);
    }

    // Validar campos requeridos.
    if (empty($body['label']) || empty($body['alert_type'])) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Fields "label" and "alert_type" are required.',
      ], 400);
    }

    // Validar alert_type contra valores permitidos.
    $allowedTypes = [
      'resolution_annulled', 'criteria_change', 'new_relevant_doctrine',
      'legislation_modified', 'procedural_deadline', 'tjue_spain_impact',
      'tedh_spain', 'edpb_guideline', 'transposition_deadline', 'ag_conclusions',
    ];
    if (!in_array($body['alert_type'], $allowedTypes, TRUE)) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Invalid alert_type. Allowed: ' . implode(', ', $allowedTypes),
      ], 400);
    }

    $result = $this->alertService->createAlert($body, $providerId);

    if (!$result['success']) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => $result['error'],
      ], 403);
    }

    $alert = $result['alert'];

    return new JsonResponse([
      'success' => TRUE,
      'data' => [
        'id' => (int) $alert->id(),
        'label' => $alert->get('label')->value ?? '',
        'alert_type' => $alert->get('alert_type')->value ?? '',
        'severity' => $alert->get('severity')->value ?? 'medium',
        'is_active' => TRUE,
      ],
    ], 201);
  }

  /**
   * Maneja PATCH /api/v1/legal/alerts — Toggle activo/inactivo.
   */
  private function handleUpdateAlert(Request $request, int $providerId): JsonResponse {
    $body = json_decode($request->getContent(), TRUE);

    if (!is_array($body) || empty($body['id'])) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Field "id" is required.',
      ], 400);
    }

    $alertId = (int) $body['id'];
    $isActive = (bool) ($body['is_active'] ?? TRUE);

    $result = $this->alertService->toggleAlert($alertId, $isActive, $providerId);

    if (!$result) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Alert not found or not owned by current user.',
      ], 404);
    }

    return new JsonResponse([
      'success' => TRUE,
      'data' => [
        'id' => $alertId,
        'is_active' => $isActive,
      ],
    ]);
  }

  /**
   * Maneja DELETE /api/v1/legal/alerts — Eliminar alerta.
   */
  private function handleDeleteAlert(Request $request, int $providerId): JsonResponse {
    $body = json_decode($request->getContent(), TRUE);

    if (!is_array($body) || empty($body['id'])) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Field "id" is required.',
      ], 400);
    }

    $alertId = (int) $body['id'];
    $result = $this->alertService->deleteAlert($alertId, $providerId);

    if (!$result) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Alert not found or not owned by current user.',
      ], 404);
    }

    return new JsonResponse([
      'success' => TRUE,
      'data' => [
        'id' => $alertId,
        'deleted' => TRUE,
      ],
    ]);
  }

  // =========================================================================
  // API REST: Preview del digest semanal (FASE 5 stub).
  // =========================================================================

  /**
   * Endpoint API GET /api/v1/legal/digest/preview — Preview del digest.
   *
   * Genera una previsualizacion del digest semanal personalizado del
   * usuario actual. Muestra las resoluciones que se incluirian en el
   * proximo envio del digest basandose en el perfil de intereses del
   * usuario (alertas activas, fuentes, temas, jurisdicciones).
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Objeto request HTTP GET.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con preview del digest.
   */
  public function apiDigestPreview(Request $request): JsonResponse {
    $currentUser = $this->currentUser();
    $providerId = (int) $currentUser->id();

    if ($providerId === 0) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Authentication required.',
      ], 401);
    }

    try {
      $digest = $this->digestService->generateWeeklyDigest($providerId);

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'provider_name' => $digest['provider_name'],
          'period_start' => $digest['period_start'],
          'period_end' => $digest['period_end'],
          'resolutions' => $digest['resolutions'],
          'total' => count($digest['resolutions']),
        ],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Error generating digest preview.',
      ], 500);
    }
  }

  // =========================================================================
  // API REST: Copilot integration (FASE 6).
  // =========================================================================

  /**
   * Endpoint API POST /api/v1/legal/copilot/search — Busqueda desde copilot.
   *
   * Recibe una query en lenguaje natural desde el copilot conversacional
   * y devuelve hasta 5 resoluciones relevantes con botones de accion
   * (insertar en expediente, ver completo, buscar similares).
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Objeto request con body JSON:
   *   - query: string — Texto de busqueda.
   *   - context: object — Contexto opcional (expediente_id, source_ids, etc.).
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con resultados formateados para el copilot.
   */
  public function apiCopilotSearch(Request $request): JsonResponse {
    $body = json_decode($request->getContent(), TRUE);

    if (!is_array($body) || empty($body['query'])) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Field "query" is required.',
      ], 400);
    }

    $query = trim((string) $body['query']);
    $context = (array) ($body['context'] ?? []);

    $result = $this->copilotBridge->searchForCopilot($query, $context);

    return new JsonResponse($result);
  }

  /**
   * Endpoint API POST /api/v1/legal/copilot/attach — Vincular a expediente.
   *
   * Permite al copilot vincular una resolucion a un expediente del Buzon
   * de Confianza con un solo clic. Crea una entidad LegalCitation con la
   * cita formateada en el formato solicitado.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Objeto request con body JSON:
   *   - resolution_id: int — ID de la resolucion.
   *   - expediente_id: int — ID del expediente.
   *   - format: string — Formato de cita (default: 'formal').
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con datos de la cita creada.
   */
  public function apiCopilotAttach(Request $request): JsonResponse {
    $body = json_decode($request->getContent(), TRUE);

    if (!is_array($body) || empty($body['resolution_id']) || empty($body['expediente_id'])) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Fields "resolution_id" and "expediente_id" are required.',
      ], 400);
    }

    $resolutionId = (int) $body['resolution_id'];
    $expedienteId = (int) $body['expediente_id'];
    $format = (string) ($body['format'] ?? 'formal');

    $result = $this->copilotBridge->attachResultToExpediente(
      $resolutionId,
      $expedienteId,
      $format
    );

    $status = $result['success'] ? 200 : 400;

    return new JsonResponse($result, $status);
  }

  /**
   * Endpoint API GET /api/v1/legal/expediente/{id}/references.
   *
   * Lista todas las resoluciones vinculadas a un expediente del Buzon
   * de Confianza con datos de la cita y la resolucion.
   *
   * @param string $expediente_id
   *   ID del expediente.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con la lista de referencias.
   */
  public function apiExpedienteReferences(string $expediente_id): JsonResponse {
    $references = $this->citationService->getExpedienteReferences((int) $expediente_id);

    return new JsonResponse([
      'success' => TRUE,
      'data' => $references,
      'meta' => [
        'total' => count($references),
        'expediente_id' => (int) $expediente_id,
      ],
    ]);
  }

}
