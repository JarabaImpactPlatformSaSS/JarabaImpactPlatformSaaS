<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_calendar\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_legal_calendar\Service\DeadlineCalculatorService;
use Drupal\jaraba_legal_calendar\Service\LegalAgendaService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API REST para agenda juridica.
 *
 * Endpoints para plazos, senalados, agenda unificada y conexiones.
 * Respuestas con API envelope: {data} / {data, meta} / {error}.
 */
class CalendarApiController extends ControllerBase {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected DeadlineCalculatorService $deadlineCalculator,
    protected LegalAgendaService $agenda,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('jaraba_legal_calendar.deadline_calculator'),
      $container->get('jaraba_legal_calendar.agenda'),
    );
  }

  /**
   * GET /api/v1/legal/calendar/deadlines — Listar plazos.
   */
  public function listDeadlines(Request $request): JsonResponse {
    $storage = $this->entityTypeManager->getStorage('legal_deadline');
    $query = $storage->getQuery()->accessCheck(TRUE)->sort('due_date', 'ASC');

    $status = $request->query->get('status');
    if ($status) {
      $query->condition('status', $status);
    }

    $case_id = $request->query->get('case_id');
    if ($case_id) {
      $query->condition('case_id', $case_id);
    }

    $limit = min((int) ($request->query->get('limit') ?: 50), 100);
    $offset = (int) ($request->query->get('offset') ?: 0);
    $count = (clone $query)->count()->execute();
    $ids = $query->range($offset, $limit)->execute();
    $entities = $ids ? $storage->loadMultiple($ids) : [];

    $data = [];
    foreach ($entities as $entity) {
      $data[] = $this->serializeDeadline($entity);
    }

    return new JsonResponse(['data' => $data, 'meta' => ['total' => $count, 'limit' => $limit, 'offset' => $offset]]);
  }

  /**
   * POST /api/v1/legal/calendar/deadlines — Crear plazo.
   */
  public function storeDeadline(Request $request): JsonResponse {
    $content = json_decode($request->getContent(), TRUE);
    if (empty($content['title']) || empty($content['case_id']) || empty($content['due_date'])) {
      return new JsonResponse(['error' => 'Campos obligatorios: title, case_id, due_date.'], 400);
    }

    $storage = $this->entityTypeManager->getStorage('legal_deadline');
    $entity = $storage->create($content);
    $entity->save();

    return new JsonResponse(['data' => $this->serializeDeadline($entity)], 201);
  }

  /**
   * POST /api/v1/legal/calendar/deadlines/compute — Calcular plazo.
   */
  public function computeDeadline(Request $request): JsonResponse {
    $content = json_decode($request->getContent(), TRUE);
    if (empty($content['base_date']) || empty($content['rule'])) {
      return new JsonResponse(['error' => 'Campos obligatorios: base_date, rule.'], 400);
    }

    $baseDate = new \DateTimeImmutable($content['base_date']);
    $jurisdiction = $content['jurisdiction'] ?? 'ES';
    $computed = $this->deadlineCalculator->computeDeadline($baseDate, $content['rule'], $jurisdiction);

    return new JsonResponse([
      'data' => [
        'base_date' => $baseDate->format('Y-m-d\TH:i:s'),
        'rule' => $content['rule'],
        'jurisdiction' => $jurisdiction,
        'computed_date' => $computed->format('Y-m-d\TH:i:s'),
      ],
    ]);
  }

  /**
   * PATCH /api/v1/legal/calendar/deadlines/{uuid} — Actualizar plazo.
   */
  public function updateDeadline(string $uuid, Request $request): JsonResponse {
    $entity = $this->loadByUuid('legal_deadline', $uuid);
    if (!$entity) {
      return new JsonResponse(['error' => 'Plazo no encontrado.'], 404);
    }

    $content = json_decode($request->getContent(), TRUE);
    foreach ($content as $field => $value) {
      if ($entity->hasField($field)) {
        $entity->set($field, $value);
      }
    }
    $entity->save();

    return new JsonResponse(['data' => $this->serializeDeadline($entity)]);
  }

  /**
   * POST /api/v1/legal/calendar/deadlines/{uuid}/complete — Completar.
   */
  public function completeDeadline(string $uuid): JsonResponse {
    $entity = $this->loadByUuid('legal_deadline', $uuid);
    if (!$entity) {
      return new JsonResponse(['error' => 'Plazo no encontrado.'], 404);
    }

    $entity->set('status', 'completed');
    $entity->set('completed_at', date('Y-m-d\TH:i:s'));
    $entity->save();

    return new JsonResponse(['data' => $this->serializeDeadline($entity)]);
  }

  /**
   * GET /api/v1/legal/calendar/hearings — Listar senalados.
   */
  public function listHearings(Request $request): JsonResponse {
    $storage = $this->entityTypeManager->getStorage('court_hearing');
    $query = $storage->getQuery()->accessCheck(TRUE)->sort('scheduled_at', 'ASC');

    $case_id = $request->query->get('case_id');
    if ($case_id) {
      $query->condition('case_id', $case_id);
    }

    $limit = min((int) ($request->query->get('limit') ?: 50), 100);
    $offset = (int) ($request->query->get('offset') ?: 0);
    $count = (clone $query)->count()->execute();
    $ids = $query->range($offset, $limit)->execute();
    $entities = $ids ? $storage->loadMultiple($ids) : [];

    $data = [];
    foreach ($entities as $entity) {
      $data[] = $this->serializeHearing($entity);
    }

    return new JsonResponse(['data' => $data, 'meta' => ['total' => $count, 'limit' => $limit, 'offset' => $offset]]);
  }

  /**
   * POST /api/v1/legal/calendar/hearings — Crear senalado.
   */
  public function storeHearing(Request $request): JsonResponse {
    $content = json_decode($request->getContent(), TRUE);
    if (empty($content['title']) || empty($content['case_id']) || empty($content['scheduled_at']) || empty($content['court'])) {
      return new JsonResponse(['error' => 'Campos obligatorios: title, case_id, scheduled_at, court.'], 400);
    }

    $storage = $this->entityTypeManager->getStorage('court_hearing');
    $entity = $storage->create($content);
    $entity->save();

    return new JsonResponse(['data' => $this->serializeHearing($entity)], 201);
  }

  /**
   * PATCH /api/v1/legal/calendar/hearings/{uuid} — Actualizar senalado.
   */
  public function updateHearing(string $uuid, Request $request): JsonResponse {
    $entity = $this->loadByUuid('court_hearing', $uuid);
    if (!$entity) {
      return new JsonResponse(['error' => 'Senalado no encontrado.'], 404);
    }

    $content = json_decode($request->getContent(), TRUE);
    foreach ($content as $field => $value) {
      if ($entity->hasField($field)) {
        $entity->set($field, $value);
      }
    }
    $entity->save();

    return new JsonResponse(['data' => $this->serializeHearing($entity)]);
  }

  /**
   * GET /api/v1/legal/calendar/agenda — Vista unificada.
   */
  public function agendaView(Request $request): JsonResponse {
    $days = (int) ($request->query->get('days') ?: 30);
    $deadlines = $this->agenda->getUpcomingDeadlines($days);
    $hearings = $this->agenda->getHearings($days);

    return new JsonResponse([
      'data' => [
        'deadlines' => array_map([$this, 'serializeDeadline'], $deadlines),
        'hearings' => array_map([$this, 'serializeHearing'], $hearings),
      ],
    ]);
  }

  /**
   * GET /api/v1/legal/calendar/agenda/{year}/{month} — Vista mensual.
   */
  public function monthView(int $year, int $month): JsonResponse {
    $startDate = sprintf('%04d-%02d-01', $year, $month);
    $endDate = (new \DateTime($startDate))->modify('last day of this month')->format('Y-m-d');

    $dayView = $this->agenda->getDayView($startDate);

    return new JsonResponse([
      'data' => [
        'year' => $year,
        'month' => $month,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'deadlines' => array_map([$this, 'serializeDeadline'], $dayView['deadlines']),
        'hearings' => array_map([$this, 'serializeHearing'], $dayView['hearings']),
      ],
    ]);
  }

  /**
   * GET /api/v1/legal/calendar/connections — Listar conexiones.
   */
  public function listConnections(): JsonResponse {
    $storage = $this->entityTypeManager->getStorage('calendar_connection');
    $ids = $storage->getQuery()->accessCheck(TRUE)->execute();
    $entities = $ids ? $storage->loadMultiple($ids) : [];

    $data = [];
    foreach ($entities as $entity) {
      $data[] = [
        'id' => (int) $entity->id(),
        'uuid' => $entity->uuid(),
        'platform' => $entity->get('platform')->value,
        'account_email' => $entity->get('account_email')->value,
        'status' => $entity->get('status')->value,
        'last_sync_at' => $entity->get('last_sync_at')->value,
      ];
    }

    return new JsonResponse(['data' => $data]);
  }

  /**
   * GET /api/v1/legal/calendar/google/auth — OAuth Google redirect.
   */
  public function googleAuth(): JsonResponse {
    // TODO: Implementar OAuth 2.0 redirect a Google.
    return new JsonResponse(['data' => ['redirect_url' => 'https://accounts.google.com/o/oauth2/v2/auth']]);
  }

  /**
   * GET /api/v1/legal/calendar/google/callback — OAuth Google callback.
   */
  public function googleCallback(Request $request): JsonResponse {
    // TODO: Implementar intercambio de authorization code por tokens.
    return new JsonResponse(['data' => ['status' => 'connected']]);
  }

  /**
   * GET /api/v1/legal/calendar/microsoft/auth — OAuth Microsoft redirect.
   */
  public function microsoftAuth(): JsonResponse {
    // TODO: Implementar OAuth 2.0 redirect a Microsoft.
    return new JsonResponse(['data' => ['redirect_url' => 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize']]);
  }

  /**
   * GET /api/v1/legal/calendar/microsoft/callback — OAuth Microsoft callback.
   */
  public function microsoftCallback(Request $request): JsonResponse {
    // TODO: Implementar intercambio de authorization code por tokens.
    return new JsonResponse(['data' => ['status' => 'connected']]);
  }

  /**
   * DELETE /api/v1/legal/calendar/connections/{id} — Desconectar.
   */
  public function deleteConnection(int $id): JsonResponse {
    $storage = $this->entityTypeManager->getStorage('calendar_connection');
    $entity = $storage->load($id);
    if (!$entity) {
      return new JsonResponse(['error' => 'Conexion no encontrada.'], 404);
    }
    $entity->delete();
    return new JsonResponse(['data' => ['deleted' => TRUE]]);
  }

  /**
   * POST /api/v1/legal/calendar/sync/{calendarId}/refresh — Forzar sync.
   */
  public function forceSync(int $calendarId): JsonResponse {
    // TODO: Invocar CalendarSyncService::syncFromExternal().
    return new JsonResponse(['data' => ['synced' => TRUE, 'calendar_id' => $calendarId]]);
  }

  /**
   * Carga entidad por UUID.
   */
  protected function loadByUuid(string $entityType, string $uuid): mixed {
    $storage = $this->entityTypeManager->getStorage($entityType);
    $ids = $storage->getQuery()
      ->condition('uuid', $uuid)
      ->accessCheck(TRUE)
      ->range(0, 1)
      ->execute();
    return $ids ? $storage->load(reset($ids)) : NULL;
  }

  /**
   * Serializa LegalDeadline para API response.
   */
  protected function serializeDeadline($entity): array {
    return [
      'id' => (int) $entity->id(),
      'uuid' => $entity->uuid(),
      'title' => $entity->label(),
      'deadline_type' => $entity->get('deadline_type')->value,
      'legal_basis' => $entity->get('legal_basis')->value,
      'due_date' => $entity->get('due_date')->value,
      'status' => $entity->get('status')->value,
      'is_computed' => (bool) $entity->get('is_computed')->value,
      'case_id' => $entity->get('case_id')->target_id,
      'assigned_to' => $entity->get('assigned_to')->target_id,
      'alert_days_before' => (int) $entity->get('alert_days_before')->value,
      'created' => date('c', (int) $entity->get('created')->value),
    ];
  }

  /**
   * Serializa CourtHearing para API response.
   */
  protected function serializeHearing($entity): array {
    return [
      'id' => (int) $entity->id(),
      'uuid' => $entity->uuid(),
      'title' => $entity->label(),
      'hearing_type' => $entity->get('hearing_type')->value,
      'court' => $entity->get('court')->value,
      'courtroom' => $entity->get('courtroom')->value,
      'scheduled_at' => $entity->get('scheduled_at')->value,
      'estimated_duration_minutes' => $entity->get('estimated_duration_minutes')->value,
      'is_virtual' => (bool) $entity->get('is_virtual')->value,
      'virtual_url' => $entity->get('virtual_url')->value,
      'status' => $entity->get('status')->value,
      'case_id' => $entity->get('case_id')->target_id,
      'created' => date('c', (int) $entity->get('created')->value),
    ];
  }

}
