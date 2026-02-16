<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_cases\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_legal_cases\Service\ActivityLoggerService;
use Drupal\jaraba_legal_cases\Service\CaseManagerService;
use Drupal\jaraba_legal_cases\Service\CaseTriageService;
use Drupal\jaraba_legal_cases\Service\InquiryManagerService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller de la API REST de Legal Cases.
 *
 * Estructura: 11 endpoints JSON para expedientes, consultas,
 *   actividades, triaje y conversiones.
 *
 * Logica: Cada endpoint retorna JsonResponse con la estructura
 *   estandar del ecosistema: { data } o { data, meta } para listas,
 *   { error } para errores (API-NAMING-001: store() no create()).
 */
class CasesApiController extends ControllerBase {

  /**
   * Constructor.
   */
  public function __construct(
    protected CaseManagerService $caseManager,
    protected ActivityLoggerService $activityLogger,
    protected CaseTriageService $triageService,
    protected InquiryManagerService $inquiryManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_legal_cases.case_manager'),
      $container->get('jaraba_legal_cases.activity_logger'),
      $container->get('jaraba_legal_cases.triage'),
      $container->get('jaraba_legal_cases.inquiry_manager'),
    );
  }

  /**
   * GET /api/v1/legal/cases — Listado de expedientes.
   */
  public function list(Request $request): JsonResponse {
    $filters = [
      'status' => $request->query->get('status'),
      'priority' => $request->query->get('priority'),
      'case_type' => $request->query->get('case_type'),
    ];
    $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
    $offset = max(0, (int) $request->query->get('offset', 0));

    $result = $this->caseManager->getCasesFiltered(array_filter($filters), $limit, $offset);

    $data = [];
    foreach ($result['cases'] as $case) {
      $data[] = $this->serializeCase($case);
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
   * POST /api/v1/legal/cases — Crear expediente (API-NAMING-001: store).
   */
  public function store(Request $request): JsonResponse {
    $content = json_decode($request->getContent(), TRUE);
    if (empty($content['title']) || empty($content['client_name'])) {
      return new JsonResponse(['error' => 'Los campos title y client_name son obligatorios.'], 400);
    }

    try {
      $storage = $this->entityTypeManager()->getStorage('client_case');
      $case = $storage->create([
        'title' => $content['title'],
        'client_name' => $content['client_name'],
        'client_email' => $content['client_email'] ?? '',
        'client_phone' => $content['client_phone'] ?? '',
        'client_nif' => $content['client_nif'] ?? '',
        'case_type' => $content['case_type'] ?? 'civil',
        'priority' => $content['priority'] ?? 'medium',
        'description' => $content['description'] ?? '',
        'status' => 'active',
        'uid' => $this->currentUser()->id(),
      ]);
      $case->save();

      return new JsonResponse(['data' => $this->serializeCase($case)], 201);
    }
    catch (\Exception $e) {
      return new JsonResponse(['error' => 'Error al crear el expediente.'], 500);
    }
  }

  /**
   * GET /api/v1/legal/cases/{uuid} — Detalle de expediente.
   */
  public function detail(string $uuid): JsonResponse {
    $case = $this->caseManager->getCaseByUuid($uuid);
    if (!$case) {
      return new JsonResponse(['error' => 'Expediente no encontrado.'], 404);
    }
    return new JsonResponse(['data' => $this->serializeCase($case)]);
  }

  /**
   * PATCH /api/v1/legal/cases/{uuid} — Actualizar expediente.
   */
  public function update(string $uuid, Request $request): JsonResponse {
    $case = $this->caseManager->getCaseByUuid($uuid);
    if (!$case) {
      return new JsonResponse(['error' => 'Expediente no encontrado.'], 404);
    }

    $content = json_decode($request->getContent(), TRUE);
    $allowed_fields = [
      'title', 'status', 'priority', 'case_type', 'client_name',
      'client_email', 'client_phone', 'client_nif', 'description',
      'court_name', 'court_number', 'opposing_party', 'notes',
    ];

    foreach ($allowed_fields as $field) {
      if (isset($content[$field])) {
        $case->set($field, $content[$field]);
      }
    }

    try {
      $case->save();
      return new JsonResponse(['data' => $this->serializeCase($case)]);
    }
    catch (\Exception $e) {
      return new JsonResponse(['error' => 'Error al actualizar el expediente.'], 500);
    }
  }

  /**
   * DELETE /api/v1/legal/cases/{uuid} — Eliminar expediente.
   */
  public function delete(string $uuid): JsonResponse {
    $case = $this->caseManager->getCaseByUuid($uuid);
    if (!$case) {
      return new JsonResponse(['error' => 'Expediente no encontrado.'], 404);
    }

    try {
      $case->delete();
      return new JsonResponse(['data' => ['deleted' => TRUE]]);
    }
    catch (\Exception $e) {
      return new JsonResponse(['error' => 'Error al eliminar el expediente.'], 500);
    }
  }

  /**
   * GET /api/v1/legal/cases/{uuid}/activity — Actividades del expediente.
   */
  public function activity(string $uuid): JsonResponse {
    $case = $this->caseManager->getCaseByUuid($uuid);
    if (!$case) {
      return new JsonResponse(['error' => 'Expediente no encontrado.'], 404);
    }

    $activities = $this->activityLogger->getActivities((int) $case->id());
    $data = [];
    foreach ($activities as $activity) {
      $actor = $activity->get('actor_uid')->entity;
      $data[] = [
        'id' => (int) $activity->id(),
        'activity_type' => $activity->get('activity_type')->value,
        'description' => $activity->get('description')->value ?? '',
        'actor' => $actor ? $actor->getDisplayName() : '',
        'is_client_visible' => (bool) $activity->get('is_client_visible')->value,
        'created' => $activity->get('created')->value,
      ];
    }

    return new JsonResponse(['data' => $data]);
  }

  /**
   * POST /api/v1/legal/inquiries — Crear consulta (store).
   */
  public function storeInquiry(Request $request): JsonResponse {
    $content = json_decode($request->getContent(), TRUE);
    if (empty($content['subject']) || empty($content['client_name'])) {
      return new JsonResponse(['error' => 'Los campos subject y client_name son obligatorios.'], 400);
    }

    try {
      $storage = $this->entityTypeManager()->getStorage('client_inquiry');
      $inquiry = $storage->create([
        'subject' => $content['subject'],
        'client_name' => $content['client_name'],
        'client_email' => $content['client_email'] ?? '',
        'client_phone' => $content['client_phone'] ?? '',
        'description' => $content['description'] ?? '',
        'source' => $content['source'] ?? 'web_form',
        'case_type_requested' => $content['case_type_requested'] ?? '',
        'status' => 'pending',
        'uid' => $this->currentUser()->id(),
      ]);
      $inquiry->save();

      return new JsonResponse(['data' => $this->serializeInquiry($inquiry)], 201);
    }
    catch (\Exception $e) {
      return new JsonResponse(['error' => 'Error al crear la consulta.'], 500);
    }
  }

  /**
   * GET /api/v1/legal/inquiries — Listado de consultas.
   */
  public function listInquiries(Request $request): JsonResponse {
    $filters = [
      'status' => $request->query->get('status'),
      'source' => $request->query->get('source'),
    ];
    $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
    $offset = max(0, (int) $request->query->get('offset', 0));

    $result = $this->inquiryManager->getInquiriesFiltered(array_filter($filters), $limit, $offset);

    $data = [];
    foreach ($result['inquiries'] as $inquiry) {
      $data[] = $this->serializeInquiry($inquiry);
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
   * POST /api/v1/legal/inquiries/{uuid}/triage — Triaje IA.
   */
  public function triage(string $uuid): JsonResponse {
    $storage = $this->entityTypeManager()->getStorage('client_inquiry');
    $entities = $storage->loadByProperties(['uuid' => $uuid]);
    $inquiry = !empty($entities) ? reset($entities) : NULL;

    if (!$inquiry) {
      return new JsonResponse(['error' => 'Consulta no encontrada.'], 404);
    }

    $result = $this->triageService->triageInquiry((int) $inquiry->id());
    if (!$result['success']) {
      return new JsonResponse(['error' => $result['error'] ?? 'Error en triaje.'], 500);
    }

    return new JsonResponse(['data' => $result]);
  }

  /**
   * POST /api/v1/legal/inquiries/{uuid}/convert — Convertir a expediente.
   */
  public function convertToCase(string $uuid): JsonResponse {
    $storage = $this->entityTypeManager()->getStorage('client_inquiry');
    $entities = $storage->loadByProperties(['uuid' => $uuid]);
    $inquiry = !empty($entities) ? reset($entities) : NULL;

    if (!$inquiry) {
      return new JsonResponse(['error' => 'Consulta no encontrada.'], 404);
    }

    $result = $this->inquiryManager->convertToCase((int) $inquiry->id());
    if (!$result['success']) {
      return new JsonResponse(['error' => $result['error'] ?? 'Error en conversion.'], 400);
    }

    return new JsonResponse(['data' => $result], 201);
  }

  /**
   * PATCH /api/v1/legal/inquiries/{uuid}/assign — Asignar consulta.
   */
  public function assign(string $uuid, Request $request): JsonResponse {
    $content = json_decode($request->getContent(), TRUE);
    if (empty($content['user_id'])) {
      return new JsonResponse(['error' => 'El campo user_id es obligatorio.'], 400);
    }

    $storage = $this->entityTypeManager()->getStorage('client_inquiry');
    $entities = $storage->loadByProperties(['uuid' => $uuid]);
    $inquiry = !empty($entities) ? reset($entities) : NULL;

    if (!$inquiry) {
      return new JsonResponse(['error' => 'Consulta no encontrada.'], 404);
    }

    $result = $this->inquiryManager->assignInquiry((int) $inquiry->id(), (int) $content['user_id']);
    if (!$result['success']) {
      return new JsonResponse(['error' => $result['error'] ?? 'Error en asignacion.'], 400);
    }

    return new JsonResponse(['data' => $result]);
  }

  /**
   * Serializa un expediente para la API.
   */
  protected function serializeCase(object $case): array {
    $assigned = $case->get('assigned_to')->entity;
    return [
      'id' => (int) $case->id(),
      'uuid' => $case->uuid(),
      'case_number' => $case->get('case_number')->value ?? '',
      'title' => $case->get('title')->value ?? '',
      'status' => $case->get('status')->value ?? '',
      'priority' => $case->get('priority')->value ?? '',
      'case_type' => $case->get('case_type')->value ?? '',
      'client_name' => $case->get('client_name')->value ?? '',
      'client_email' => $case->get('client_email')->value ?? '',
      'assigned_to' => $assigned ? ['id' => (int) $assigned->id(), 'name' => $assigned->getDisplayName()] : NULL,
      'court_name' => $case->get('court_name')->value ?? '',
      'estimated_value' => $case->get('estimated_value')->value ?? NULL,
      'created' => $case->get('created')->value,
      'changed' => $case->get('changed')->value,
    ];
  }

  /**
   * Serializa una consulta para la API.
   */
  protected function serializeInquiry(object $inquiry): array {
    return [
      'id' => (int) $inquiry->id(),
      'uuid' => $inquiry->uuid(),
      'inquiry_number' => $inquiry->get('inquiry_number')->value ?? '',
      'subject' => $inquiry->get('subject')->value ?? '',
      'status' => $inquiry->get('status')->value ?? '',
      'source' => $inquiry->get('source')->value ?? '',
      'priority' => $inquiry->get('priority')->value ?? '',
      'client_name' => $inquiry->get('client_name')->value ?? '',
      'client_email' => $inquiry->get('client_email')->value ?? '',
      'converted_to_case_id' => $inquiry->get('converted_to_case_id')->value ?? NULL,
      'created' => $inquiry->get('created')->value,
      'changed' => $inquiry->get('changed')->value,
    ];
  }

}
