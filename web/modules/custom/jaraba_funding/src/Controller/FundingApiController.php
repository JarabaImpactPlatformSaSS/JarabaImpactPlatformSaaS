<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\jaraba_funding\Service\ApplicationManagerService;
use Drupal\jaraba_funding\Service\BudgetAnalyzerService;
use Drupal\jaraba_funding\Service\ImpactCalculatorService;
use Drupal\jaraba_funding\Service\OpportunityTrackerService;
use Drupal\jaraba_funding\Service\ReportGeneratorService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller de la API REST de Fondos y Subvenciones.
 *
 * Estructura: 13 endpoints JSON para convocatorias, solicitudes,
 *   memorias tecnicas y estadisticas. Sigue el patron del ecosistema
 *   con envelope estandar {data}/{data,meta}/{error}.
 *
 * Logica: Cada endpoint retorna JsonResponse. Los metodos de escritura
 *   usan store() en lugar de create() (API-NAMING-001). Los metodos
 *   de lectura soportan paginacion via query params limit/offset.
 *
 * @see \Drupal\jaraba_funding\Service\OpportunityTrackerService
 * @see \Drupal\jaraba_funding\Service\ApplicationManagerService
 */
class FundingApiController extends ControllerBase {

  /**
   * Constructor con inyeccion de dependencias.
   */
  public function __construct(
    protected OpportunityTrackerService $opportunityTracker,
    protected ApplicationManagerService $applicationManager,
    protected ReportGeneratorService $reportGenerator,
    protected BudgetAnalyzerService $budgetAnalyzer,
    protected ImpactCalculatorService $impactCalculator,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_funding.opportunity_tracker'),
      $container->get('jaraba_funding.application_manager'),
      $container->get('jaraba_funding.report_generator'),
      $container->get('jaraba_funding.budget_analyzer'),
      $container->get('jaraba_funding.impact_calculator'),
    );
  }

  // ============================================
  // CONVOCATORIAS
  // ============================================

  /**
   * GET /api/v1/funding/opportunities — Listado de convocatorias.
   */
  public function listOpportunities(Request $request): JsonResponse {
    $filters = array_filter([
      'status' => $request->query->get('status'),
      'program' => $request->query->get('program'),
      'funding_body' => $request->query->get('funding_body'),
    ]);
    $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
    $offset = max(0, (int) $request->query->get('offset', 0));

    $result = $this->opportunityTracker->getOpportunitiesFiltered($filters, $limit, $offset);

    $data = [];
    foreach ($result['opportunities'] as $opp) {
      $data[] = $this->serializeOpportunity($opp);
    }

    return new JsonResponse([
      'data' => $data,
      'meta' => [
        'total' => $result['total'],
        'limit' => $limit,
        'offset' => $offset,
      ],
    ]);
  }

  /**
   * POST /api/v1/funding/opportunities — Crear convocatoria (API-NAMING-001: store).
   */
  public function storeOpportunity(Request $request): JsonResponse {
    try {
      $content = json_decode($request->getContent(), TRUE);
      if (empty($content['name']) || empty($content['funding_body'])) {
        return new JsonResponse([
          'error' => (string) new TranslatableMarkup('Nombre y organismo convocante son obligatorios.'),
        ], 422);
      }

      $storage = $this->entityTypeManager()->getStorage('funding_opportunity');
      $opportunity = $storage->create([
        'name' => $content['name'],
        'funding_body' => $content['funding_body'],
        'program' => $content['program'] ?? '',
        'max_amount' => $content['max_amount'] ?? NULL,
        'deadline' => $content['deadline'] ?? NULL,
        'requirements' => $content['requirements'] ?? '',
        'documentation_required' => $content['documentation_required'] ?? '',
        'status' => $content['status'] ?? 'upcoming',
        'url' => isset($content['url']) ? ['uri' => $content['url']] : NULL,
        'alert_days_before' => $content['alert_days_before'] ?? 15,
        'notes' => $content['notes'] ?? '',
        'tenant_id' => $content['tenant_id'] ?? NULL,
      ]);
      $opportunity->save();

      return new JsonResponse([
        'data' => $this->serializeOpportunity($opportunity),
      ], 201);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => (string) new TranslatableMarkup('Error al crear convocatoria.'),
      ], 500);
    }
  }

  /**
   * GET /api/v1/funding/opportunities/{id} — Detalle de convocatoria.
   */
  public function showOpportunity(int $funding_opportunity): JsonResponse {
    $storage = $this->entityTypeManager()->getStorage('funding_opportunity');
    $opp = $storage->load($funding_opportunity);

    if (!$opp) {
      return new JsonResponse([
        'error' => (string) new TranslatableMarkup('Convocatoria no encontrada.'),
      ], 404);
    }

    return new JsonResponse(['data' => $this->serializeOpportunity($opp)]);
  }

  /**
   * PATCH /api/v1/funding/opportunities/{id} — Actualizar convocatoria.
   */
  public function updateOpportunity(Request $request, int $funding_opportunity): JsonResponse {
    try {
      $storage = $this->entityTypeManager()->getStorage('funding_opportunity');
      $opp = $storage->load($funding_opportunity);

      if (!$opp) {
        return new JsonResponse([
          'error' => (string) new TranslatableMarkup('Convocatoria no encontrada.'),
        ], 404);
      }

      $content = json_decode($request->getContent(), TRUE);
      $updatable = ['name', 'funding_body', 'program', 'max_amount', 'deadline', 'requirements', 'documentation_required', 'status', 'alert_days_before', 'notes'];

      foreach ($updatable as $field) {
        if (isset($content[$field])) {
          $opp->set($field, $content[$field]);
        }
      }

      if (isset($content['url'])) {
        $opp->set('url', ['uri' => $content['url']]);
      }

      $opp->save();

      return new JsonResponse(['data' => $this->serializeOpportunity($opp)]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => (string) new TranslatableMarkup('Error al actualizar convocatoria.'),
      ], 500);
    }
  }

  // ============================================
  // SOLICITUDES
  // ============================================

  /**
   * GET /api/v1/funding/applications — Listado de solicitudes.
   */
  public function listApplications(Request $request): JsonResponse {
    $filters = array_filter([
      'status' => $request->query->get('status'),
      'opportunity_id' => $request->query->get('opportunity_id'),
    ]);
    $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
    $offset = max(0, (int) $request->query->get('offset', 0));

    $result = $this->applicationManager->getApplicationsFiltered($filters, $limit, $offset);

    $data = [];
    foreach ($result['applications'] as $app) {
      $data[] = $this->serializeApplication($app);
    }

    return new JsonResponse([
      'data' => $data,
      'meta' => [
        'total' => $result['total'],
        'limit' => $limit,
        'offset' => $offset,
      ],
    ]);
  }

  /**
   * POST /api/v1/funding/applications — Crear solicitud (API-NAMING-001: store).
   */
  public function storeApplication(Request $request): JsonResponse {
    try {
      $content = json_decode($request->getContent(), TRUE);
      if (empty($content['opportunity_id'])) {
        return new JsonResponse([
          'error' => (string) new TranslatableMarkup('La convocatoria asociada es obligatoria.'),
        ], 422);
      }

      $storage = $this->entityTypeManager()->getStorage('funding_application');
      $application = $storage->create([
        'opportunity_id' => $content['opportunity_id'],
        'tenant_id' => $content['tenant_id'] ?? NULL,
        'status' => 'draft',
        'amount_requested' => $content['amount_requested'] ?? NULL,
        'budget_breakdown' => $content['budget_breakdown'] ?? '',
        'impact_indicators' => $content['impact_indicators'] ?? '',
      ]);
      $application->save();

      return new JsonResponse([
        'data' => $this->serializeApplication($application),
      ], 201);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => (string) new TranslatableMarkup('Error al crear solicitud.'),
      ], 500);
    }
  }

  /**
   * GET /api/v1/funding/applications/{id} — Detalle de solicitud.
   */
  public function showApplication(int $funding_application): JsonResponse {
    $storage = $this->entityTypeManager()->getStorage('funding_application');
    $app = $storage->load($funding_application);

    if (!$app) {
      return new JsonResponse([
        'error' => (string) new TranslatableMarkup('Solicitud no encontrada.'),
      ], 404);
    }

    return new JsonResponse(['data' => $this->serializeApplication($app)]);
  }

  /**
   * PATCH /api/v1/funding/applications/{id} — Actualizar solicitud.
   */
  public function updateApplication(Request $request, int $funding_application): JsonResponse {
    try {
      $storage = $this->entityTypeManager()->getStorage('funding_application');
      $app = $storage->load($funding_application);

      if (!$app) {
        return new JsonResponse([
          'error' => (string) new TranslatableMarkup('Solicitud no encontrada.'),
        ], 404);
      }

      $content = json_decode($request->getContent(), TRUE);
      $updatable = ['amount_requested', 'amount_approved', 'submission_date', 'resolution_date', 'next_deadline', 'budget_breakdown', 'impact_indicators', 'justification_notes'];

      foreach ($updatable as $field) {
        if (isset($content[$field])) {
          $app->set($field, $content[$field]);
        }
      }

      if (isset($content['status'])) {
        $result = $this->applicationManager->updateStatus((int) $app->id(), $content['status']);
        if (!$result['success']) {
          return new JsonResponse(['error' => $result['error']], 422);
        }
        $app = $storage->load($funding_application);
      }
      else {
        $app->save();
      }

      return new JsonResponse(['data' => $this->serializeApplication($app)]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => (string) new TranslatableMarkup('Error al actualizar solicitud.'),
      ], 500);
    }
  }

  /**
   * POST /api/v1/funding/applications/{id}/submit — Presentar solicitud.
   */
  public function submitApplication(int $funding_application): JsonResponse {
    $result = $this->applicationManager->updateStatus($funding_application, 'submitted');

    if (!$result['success']) {
      return new JsonResponse(['error' => $result['error']], 422);
    }

    $storage = $this->entityTypeManager()->getStorage('funding_application');
    $app = $storage->load($funding_application);

    return new JsonResponse(['data' => $this->serializeApplication($app)]);
  }

  // ============================================
  // MEMORIAS TECNICAS
  // ============================================

  /**
   * POST /api/v1/funding/applications/{id}/report — Generar memoria tecnica.
   */
  public function generateReport(Request $request, int $funding_application): JsonResponse {
    $content = json_decode($request->getContent(), TRUE);
    $type = $content['report_type'] ?? 'initial';

    $result = $this->reportGenerator->generateReport($funding_application, $type);

    if (!$result['success']) {
      return new JsonResponse(['error' => $result['error']], 422);
    }

    return new JsonResponse(['data' => ['report_id' => $result['report_id']]], 201);
  }

  /**
   * POST /api/v1/funding/applications/{id}/report/ai — Generar memoria con IA.
   */
  public function generateReportWithAi(Request $request, int $funding_application): JsonResponse {
    $content = json_decode($request->getContent(), TRUE);
    $type = $content['report_type'] ?? 'initial';

    $result = $this->reportGenerator->generateWithAi($funding_application, $type);

    if (!$result['success']) {
      return new JsonResponse(['error' => $result['error']], 422);
    }

    return new JsonResponse(['data' => ['report_id' => $result['report_id']]], 201);
  }

  // ============================================
  // ESTADISTICAS
  // ============================================

  /**
   * GET /api/v1/funding/stats — Estadisticas del dashboard.
   */
  public function stats(): JsonResponse {
    $stats = $this->applicationManager->getDashboardStats();
    return new JsonResponse(['data' => $stats]);
  }

  // ============================================
  // SERIALIZACION
  // ============================================

  /**
   * Serializa una convocatoria para respuesta JSON.
   */
  protected function serializeOpportunity(object $opportunity): array {
    return [
      'id' => (int) $opportunity->id(),
      'name' => $opportunity->get('name')->value ?? '',
      'funding_body' => $opportunity->get('funding_body')->value ?? '',
      'program' => $opportunity->get('program')->value ?? '',
      'max_amount' => $opportunity->get('max_amount')->value,
      'deadline' => $opportunity->get('deadline')->value,
      'status' => $opportunity->get('status')->value ?? '',
      'url' => $opportunity->get('url')->uri ?? '',
      'alert_days_before' => (int) ($opportunity->get('alert_days_before')->value ?? 15),
      'created' => $opportunity->get('created')->value,
      'changed' => $opportunity->get('changed')->value,
    ];
  }

  /**
   * Serializa una solicitud para respuesta JSON.
   */
  protected function serializeApplication(object $application): array {
    return [
      'id' => (int) $application->id(),
      'application_number' => $application->get('application_number')->value ?? '',
      'opportunity_id' => $application->get('opportunity_id')->target_id,
      'status' => $application->get('status')->value ?? '',
      'amount_requested' => $application->get('amount_requested')->value,
      'amount_approved' => $application->get('amount_approved')->value,
      'submission_date' => $application->get('submission_date')->value,
      'resolution_date' => $application->get('resolution_date')->value,
      'next_deadline' => $application->get('next_deadline')->value,
      'created' => $application->get('created')->value,
      'changed' => $application->get('changed')->value,
    ];
  }

}
